<?php
require_once 'config/database.php';
require_once 'config/mail.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$mailer = new Mailer();

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$user_data = $_SESSION['temp_user'] ?? [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
        $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
        $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
        $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validation des champs communs
        if (empty($email) || empty($nom) || empty($prenom) || empty($mot_de_passe)) {
            $error = "Tous les champs obligatoires doivent être remplis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } elseif ($mot_de_passe !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($mot_de_passe) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            // Validation spécifique pour recruteur
            if ($role == 'recruteur') {
                $nom_entreprise = isset($_POST['nom_entreprise']) ? trim($_POST['nom_entreprise']) : '';
                $secteur = isset($_POST['secteur']) ? trim($_POST['secteur']) : '';
                $adresse_entreprise = isset($_POST['adresse_entreprise']) ? trim($_POST['adresse_entreprise']) : '';
                $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';
                $statut_juridique = isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : '';
                $nombre_employes = isset($_POST['nombre_employes']) ? $_POST['nombre_employes'] : '';
                
                if (empty($nom_entreprise)) {
                    $error = "Le nom de l'entreprise est obligatoire.";
                } elseif (empty($secteur)) {
                    $error = "Le secteur d'activité est obligatoire.";
                } elseif (empty($adresse_entreprise)) {
                    $error = "L'adresse de l'entreprise est obligatoire.";
                } elseif (empty($nif)) {
                    $error = "Le NIF / identifiant fiscal est obligatoire.";
                } elseif (empty($statut_juridique)) {
                    $error = "Le statut juridique est obligatoire.";
                } elseif (empty($nombre_employes)) {
                    $error = "Le nombre d'employés est obligatoire.";
                }
            }
            
            if (empty($error)) {
                $check = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $error = "Cette adresse email est déjà utilisée.";
                } else {
                    $code_verification = rand(100000, 999999);
                    
                    // Stockage des données de base
                    $_SESSION['temp_user'] = [
                        'email' => $email,
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'telephone' => $telephone,
                        'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
                        'role' => $role,
                        'code_verification' => $code_verification,
                        'code_expiration' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                    ];
                    
                    // Si recruteur, stocker toutes les données supplémentaires
                    if ($role == 'recruteur') {
                        $_SESSION['temp_user']['nom_entreprise'] = isset($_POST['nom_entreprise']) ? trim($_POST['nom_entreprise']) : '';
                        $_SESSION['temp_user']['secteur'] = isset($_POST['secteur']) ? trim($_POST['secteur']) : '';
                        $_SESSION['temp_user']['adresse_entreprise'] = isset($_POST['adresse_entreprise']) ? trim($_POST['adresse_entreprise']) : '';
                        $_SESSION['temp_user']['site_web'] = isset($_POST['site_web']) ? trim($_POST['site_web']) : '';
                        $_SESSION['temp_user']['nif'] = isset($_POST['nif']) ? trim($_POST['nif']) : '';
                        $_SESSION['temp_user']['statut_juridique'] = isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : '';
                        $_SESSION['temp_user']['nombre_employes'] = isset($_POST['nombre_employes']) ? $_POST['nombre_employes'] : '';
                        $_SESSION['temp_user']['abonnement'] = isset($_POST['abonnement']) ? $_POST['abonnement'] : '';
                        
                        // Gestion des fichiers uploadés (temporaire)
                        $upload_dir = 'uploads/temp/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $documents = [];
                        $doc_fields = ['cni', 'registre_commerce', 'attestation_fiscale', 'kbis'];
                        $required_docs = ['cni', 'registre_commerce', 'attestation_fiscale'];
                        
                        foreach ($doc_fields as $field) {
                            if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                                $filename = time() . '_' . $field . '_' . uniqid() . '.' . $ext;
                                $target = $upload_dir . $filename;
                                
                                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                                if (in_array(strtolower($ext), $allowed)) {
                                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                                        $documents[$field] = $target;
                                    } else {
                                        $error = "Erreur lors de l'upload du document: " . $field;
                                        break;
                                    }
                                } else {
                                    $error = "Format de fichier non autorisé pour " . $field . ". Formats acceptés: PDF, JPG, PNG";
                                    break;
                                }
                            } elseif (in_array($field, $required_docs) && (!isset($_FILES[$field]) || $_FILES[$field]['error'] == 4)) {
                                $error = "Le document " . getDocName($field) . " est obligatoire.";
                                break;
                            }
                        }
                        
                        if (empty($error)) {
                            $_SESSION['temp_user']['documents'] = $documents;
                        }
                    }
                    
                    if (empty($error)) {
                        $mailer->envoyerCodeVerification($email, $prenom, $code_verification);
                        $success = "Un code de vérification a été envoyé à votre adresse email.";
                        $step = 2;
                    }
                }
            }
        }
    } elseif ($step == 2) {
        $code = isset($_POST['code_verification']) ? trim($_POST['code_verification']) : '';
        $user_data = $_SESSION['temp_user'] ?? [];
        
        if (empty($user_data)) { 
            header('Location: inscription.php'); 
            exit(); 
        }
        
        if ($code == $user_data['code_verification'] && strtotime($user_data['code_expiration']) > time()) {
            try {
                $conn->beginTransaction();
                
                $est_actif = ($user_data['role'] == 'candidat') ? TRUE : FALSE;
                
                $query = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, telephone, role, est_actif, code_verification, code_expiration) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $user_data['email'], 
                    $user_data['mot_de_passe'], 
                    $user_data['nom'], 
                    $user_data['prenom'], 
                    $user_data['telephone'], 
                    $user_data['role'], 
                    $est_actif,
                    $user_data['code_verification'], 
                    $user_data['code_expiration']
                ]);
                $user_id = $conn->lastInsertId();
                
                if ($user_data['role'] == 'candidat') {
                    $conn->prepare("INSERT INTO profils_candidats (utilisateur_id) VALUES (?)")->execute([$user_id]);
                    $conn->commit();
                    $mailer->envoyerBienvenue($user_data['email'], $user_data['prenom'], $user_data['role']);
                    unset($_SESSION['temp_user']);
                    $_SESSION['success_message'] = "Inscription réussie ! Veuillez vous connecter.";
                    header('Location: connexion.php');
                    exit();
                    
                } elseif ($user_data['role'] == 'recruteur') {
                    $limites = [
                        'gratuit' => ['offres' => 3, 'cv' => 50],
                        'pro' => ['offres' => 20, 'cv' => 200],
                        'entreprise' => ['offres' => 999, 'cv' => 999]
                    ];
                    $abonnement = $user_data['abonnement'];
                    $date_fin = null;
                    if ($abonnement == 'pro') {
                        $date_fin = date('Y-m-d H:i:s', strtotime('+1 month'));
                    } elseif ($abonnement == 'entreprise') {
                        $date_fin = date('Y-m-d H:i:s', strtotime('+3 months'));
                    }
                    
                    $final_cni = null;
                    $final_registre = null;
                    $final_fiscale = null;
                    $final_kbis = null;
                    
                    $final_dir = 'uploads/documents/' . $user_id . '/';
                    if (!is_dir($final_dir)) {
                        mkdir($final_dir, 0777, true);
                    }
                    
                    if (!empty($user_data['documents'])) {
                        foreach ($user_data['documents'] as $type => $temp_path) {
                            $new_path = $final_dir . $type . '_' . basename($temp_path);
                            if (file_exists($temp_path)) {
                                rename($temp_path, $new_path);
                            }
                            
                            switch($type) {
                                case 'cni': $final_cni = $new_path; break;
                                case 'registre_commerce': $final_registre = $new_path; break;
                                case 'attestation_fiscale': $final_fiscale = $new_path; break;
                                case 'kbis': $final_kbis = $new_path; break;
                            }
                        }
                    }
                    
                    $query_entreprise = "INSERT INTO entreprises (
                        utilisateur_id, nom_entreprise, secteur, adresse, site_web, 
                        nif, statut_juridique, nombre_employes,
                        chemin_cni, chemin_registre, chemin_fiscale, chemin_kbis,
                        abonnement_type, date_debut_abonnement, date_fin_abonnement, 
                        limite_offres_mois, limite_cv_par_offre, validation_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, 'en_attente')";
                    
                    $stmt_entreprise = $conn->prepare($query_entreprise);
                    $stmt_entreprise->execute([
                        $user_id,
                        $user_data['nom_entreprise'],
                        $user_data['secteur'],
                        $user_data['adresse_entreprise'],
                        $user_data['site_web'],
                        $user_data['nif'],
                        $user_data['statut_juridique'],
                        $user_data['nombre_employes'],
                        $final_cni,
                        $final_registre,
                        $final_fiscale,
                        $final_kbis,
                        $abonnement,
                        $date_fin,
                        $limites[$abonnement]['offres'],
                        $limites[$abonnement]['cv']
                    ]);
                    
                    // ========== NOTIFICATION POUR LES ADMINISTRATEURS ==========
                    $admins = $conn->prepare("SELECT id, email, prenom, nom FROM utilisateurs WHERE role = 'admin'");
                    $admins->execute();
                    $list_admins = $admins->fetchAll();
                    
                    foreach ($list_admins as $admin) {
                        $notif_query = "INSERT INTO notifications (utilisateur_id, type, categorie, titre, message, lien, est_lu, date_creation, priorite, icone) 
                                        VALUES (:user_id, 'both', 'compte', :titre, :message, :lien, 0, NOW(), 'haute', 'fa-building')";
                        $notif_stmt = $conn->prepare($notif_query);
                        $notif_stmt->execute([
                            ':user_id' => $admin['id'],
                            ':titre' => '📋 Nouveau recruteur en attente de validation',
                            ':message' => "L'entreprise " . $user_data['nom_entreprise'] . " s'est inscrite et attend la validation de ses documents.",
                            ':lien' => '/pages/admin/valider_recruteurs.php'
                        ]);
                        
                        $mailer->envoyerNouveauRecruteurAdmin(
                            $admin['email'],
                            $admin['prenom'],
                            $user_data['nom_entreprise'],
                            $user_data['email'],
                            $user_data['prenom'] . ' ' . $user_data['nom']
                        );
                    }
                    
                    $conn->commit();
                    
                    $mailer->envoyerAttenteValidation($user_data['email'], $user_data['prenom'], $user_data['nom_entreprise']);
                    
                    unset($_SESSION['temp_user']);
                    $_SESSION['success_message'] = "Inscription réussie ! Votre compte recruteur est en attente de validation par un administrateur. Vous recevrez un email dès son activation.";
                    header('Location: connexion.php');
                    exit();
                }
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Erreur lors de l'inscription : " . $e->getMessage();
                error_log("Erreur inscription: " . $e->getMessage());
            }
        } else {
            $error = "Code de vérification invalide ou expiré.";
        }
    }
}

function getDocName($field) {
    $names = [
        'cni' => 'CNI / Passeport',
        'registre_commerce' => 'Registre de commerce',
        'attestation_fiscale' => 'Attestation fiscale',
        'kbis' => 'KBIS'
    ];
    return $names[$field] ?? $field;
}

$abonnements = [];
$stmt_abos = $conn->prepare("SELECT * FROM parametres_abonnements ORDER BY prix_mensuel");
$stmt_abos->execute();
$abonnements = $stmt_abos->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Inscription — Recrutement Djibouti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ink: #0a0f1e;
            --ink-soft: #1e2840;
            --azure: #1a56ff;
            --azure-light: #3b74ff;
            --azure-pale: #e8eeff;
            --gold: #f0a500;
            --gold-light: #ffd066;
            --surface: #f7f8fc;
            --white: #ffffff;
            --muted: #6b7a99;
            --border: #e2e6f0;
            --danger: #ef4444;
            --danger-pale: #fef2f2;
            --success-color: #10b981;
            --success-pale: #ecfdf5;
            --radius: 14px;
            --radius-lg: 22px;
            --shadow-lg: 0 24px 80px rgba(10,15,30,0.16);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 400px 1fr;
            background: var(--surface);
        }

        /* LEFT PANEL */
        .left-panel {
            background: linear-gradient(145deg, var(--ink) 0%, var(--ink-soft) 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 48px;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(26,86,255,0.45) 0%, transparent 55%),
                radial-gradient(circle at 80% 80%, rgba(240,165,0,0.18) 0%, transparent 45%);
        }

        .left-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .left-top, .left-center, .left-bottom { position: relative; z-index: 2; }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: var(--azure);
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 17px;
        }

        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 17px;
            color: white; letter-spacing: -0.3px;
        }

        .brand-text span { color: var(--azure-light); }

        .left-kicker {
            font-size: 11px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--azure-light); margin-bottom: 14px;
        }

        .left-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.7rem, 2vw, 2.3rem);
            font-weight: 800; color: white;
            line-height: 1.13; letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .left-title span {
            background: linear-gradient(135deg, var(--azure-light), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .left-desc {
            font-size: 14px; line-height: 1.7;
            color: rgba(255,255,255,0.45);
            margin-bottom: 36px;
        }

        .left-perks { display: flex; flex-direction: column; gap: 14px; }

        .perk-item {
            display: flex; align-items: flex-start; gap: 14px;
        }

        .perk-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: rgba(26,86,255,0.18);
            border: 1px solid rgba(26,86,255,0.3);
            display: flex; align-items: center; justify-content: center;
            color: #88aaff; font-size: 14px; flex-shrink: 0;
        }

        .perk-title {
            font-family: 'Sora', sans-serif;
            font-size: 13px; font-weight: 600; color: white; margin-bottom: 2px;
        }

        .perk-desc { font-size: 12px; color: rgba(255,255,255,0.4); }

        .left-footer-text { font-size: 12px; color: rgba(255,255,255,0.28); }

        /* RIGHT PANEL */
        .right-panel {
            overflow-y: auto;
            padding: 48px 64px;
            background: var(--white);
        }

        .register-box {
            max-width: 720px;
            margin: 0 auto;
            animation: fadeSlideUp 0.6s ease both;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
            margin-bottom: 32px;
            transition: color 0.2s;
            font-weight: 500;
        }
        .back-link:hover { color: var(--azure); }

        .page-greeting {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: var(--azure);
            margin-bottom: 9px;
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.5rem, 5vw, 1.9rem);
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.7px;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .page-sub { font-size: 15px; color: var(--muted); margin-bottom: 32px; }

        /* STEPPER */
        .stepper {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 36px;
            flex-wrap: wrap;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 700;
            background: var(--border);
            color: var(--muted);
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .step-item.active .step-circle {
            background: var(--azure);
            color: white;
            box-shadow: 0 4px 16px rgba(26,86,255,0.35);
        }

        .step-item.done .step-circle {
            background: var(--success-color);
            color: white;
        }

        .step-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
        }

        .step-item.active .step-label { color: var(--azure); font-weight: 600; }
        .step-item.done .step-label { color: var(--success-color); }

        .step-connector {
            flex: 1;
            height: 2px;
            background: var(--border);
            margin: 0 16px;
            min-width: 40px;
        }

        .step-connector.done { background: var(--success-color); }

        /* ALERTS */
        .alert {
            border-radius: var(--radius);
            padding: 13px 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            border: 1.5px solid;
            font-weight: 500;
        }

        .alert-error { background: var(--danger-pale); border-color: rgba(239,68,68,0.25); color: #b91c1c; }
        .alert-success { background: var(--success-pale); border-color: rgba(16,185,129,0.25); color: #047857; }
        .alert i { font-size: 15px; flex-shrink: 0; }

        /* ROLE SELECTOR */
        .role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 28px; }

        .role-option { display: none; }

        .role-label {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1.5px solid var(--border);
            background: var(--surface);
            cursor: pointer;
            transition: all 0.2s;
        }

        .role-label:hover { border-color: var(--azure); background: var(--azure-pale); }

        .role-option:checked + .role-label {
            border-color: var(--azure);
            background: var(--azure-pale);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.08);
        }

        .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: var(--muted);
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .role-option:checked + .role-label .role-icon {
            background: var(--azure);
            color: white;
        }

        .role-name {
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink); margin-bottom: 2px;
        }

        .role-desc { font-size: 12px; color: var(--muted); }

        /* FORM SECTIONS */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 28px 0 22px;
        }

        .section-divider::before, .section-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .section-title-inline {
            font-size: 12px; font-weight: 700;
            letter-spacing: 1.2px; text-transform: uppercase;
            color: var(--muted); white-space: nowrap;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 7px;
            letter-spacing: 0.1px;
        }

        .form-label .required { color: var(--danger); margin-left: 2px; }
        .form-label .opt {
            font-size: 11px; font-weight: 400;
            color: var(--muted); margin-left: 4px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border-radius: 11px;
            border: 1.5px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;
            transition: all 0.2s;
            outline: none;
        }

        .form-input.no-icon { padding-left: 14px; }
        .form-input::placeholder { color: rgba(107,122,153,0.55); }

        .form-input:focus {
            border-color: var(--azure);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.09);
        }

        .form-input:focus ~ .input-icon { color: var(--azure); }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 14px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .form-hint { font-size: 12px; color: var(--muted); margin-top: 5px; }

        /* FILE UPLOAD */
        .file-upload-area {
            border: 1.5px dashed var(--border);
            border-radius: 12px;
            padding: 12px;
            background: var(--surface);
            transition: all 0.2s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--azure);
            background: var(--azure-pale);
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            margin: 0;
        }

        .file-upload-icon {
            width: 40px;
            height: 40px;
            background: var(--azure-pale);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--azure);
            font-size: 18px;
        }

        .file-upload-text {
            flex: 1;
        }

        .file-upload-text .file-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--ink);
        }

        .file-upload-text .file-hint {
            font-size: 11px;
            color: var(--muted);
        }

        /* PLAN CARDS */
        .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 4px; }

        .plan-radio { display: none; }

        .plan-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px 18px;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            background: var(--surface);
        }

        .plan-card:hover { border-color: var(--azure); background: var(--azure-pale); }

        .plan-radio:checked + .plan-card {
            border-color: var(--azure);
            background: var(--azure-pale);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.09);
        }

        .plan-badge-popular {
            position: absolute;
            top: -10px;
            right: 14px;
            background: var(--azure);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .plan-name {
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .plan-price {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--azure);
            line-height: 1;
            margin-bottom: 14px;
        }

        .plan-price span { font-size: 12px; font-weight: 400; color: var(--muted); }

        .plan-features { list-style: none; }

        .plan-features li {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 6px;
        }

        .plan-features li i { color: var(--success-color); font-size: 11px; }

        /* INFO BOX */
        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: rgba(26,86,255,0.05);
            border: 1.5px solid rgba(26,86,255,0.15);
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-top: 6px;
        }

        .info-box i { color: var(--azure); font-size: 15px; flex-shrink: 0; margin-top: 1px; }
        .info-box p { font-size: 13px; color: var(--ink-soft); line-height: 1.6; }

        /* SUBMIT */
        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--azure);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            box-shadow: 0 6px 24px rgba(26,86,255,0.28);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--azure-light);
            box-shadow: 0 8px 32px rgba(26,86,255,0.38);
            transform: translateY(-1px);
        }

        .btn-back {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            text-decoration: none;
        }

        .btn-back:hover { border-color: var(--azure); color: var(--azure); background: var(--azure-pale); }

        /* STEP 2 */
        .verify-center { text-align: center; padding: 20px 0 32px; }

        .verify-icon {
            width: 72px;
            height: 72px;
            background: var(--azure-pale);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: var(--azure);
        }

        .verify-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .verify-desc { font-size: 14px; color: var(--muted); line-height: 1.6; }
        .verify-email { color: var(--ink); font-weight: 600; }

        .code-input {
            width: 100%;
            padding: 18px 14px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 14px;
            text-align: center;
            outline: none;
        }

        .code-input:focus {
            border-color: var(--azure);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.09);
        }

        .login-prompt {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .login-prompt p { font-size: 14px; color: var(--muted); }
        .login-prompt a { color: var(--azure); font-weight: 600; text-decoration: none; }

        /* RESPONSIVE MOBILE */
        @media (max-width: 960px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 32px 20px; }
            .register-box { max-width: 100%; }
            .page-title { font-size: 1.6rem; }
            .form-input { font-size: 16px; }
            .btn-submit, .btn-back { padding: 14px; }
            .stepper { flex-direction: column; gap: 16px; }
            .step-connector { display: none; }
            .step-item { width: 100%; justify-content: space-between; }
            .plans-grid { grid-template-columns: 1fr; }
            .role-selector { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .form-row { grid-template-columns: 1fr; }
            .right-panel { padding: 24px 16px; }
            .page-title { font-size: 1.4rem; }
            .page-sub { font-size: 13px; }
            .form-input { padding: 12px 12px 12px 38px; font-size: 15px; }
            .input-icon { left: 10px; font-size: 13px; }
            .step-circle { width: 28px; height: 28px; font-size: 11px; }
            .step-label { font-size: 12px; }
            .btn-submit, .btn-back { font-size: 14px; padding: 12px; }
            .alert { font-size: 13px; padding: 10px 12px; }
        }

        /* Pour éviter le zoom sur iOS */
        @media (max-width: 768px) {
            input, select, textarea, button {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="left-panel">
        <div class="left-top">
            <a href="index.php" class="brand-link">
                <div class="brand-icon"><i class="fas fa-briefcase"></i></div>
                <div class="brand-text">Recrutement <span>Djibouti</span></div>
            </a>
        </div>
        <div class="left-center">
            <div class="left-kicker">Inscription sécurisée</div>
            <h1 class="left-title">Rejoignez la <span>communauté</span> professionnelle</h1>
            <p class="left-desc">Des milliers de candidats et d'entreprises font confiance à notre plateforme certifiée.</p>
            <div class="left-perks">
                <div class="perk-item">
                    <div class="perk-icon"><i class="fas fa-robot"></i></div>
                    <div>
                        <div class="perk-title">Matching par IA</div>
                        <div class="perk-desc">Offres personnalisées selon vos compétences</div>
                    </div>
                </div>
                <div class="perk-item">
                    <div class="perk-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <div class="perk-title">100% sécurisé</div>
                        <div class="perk-desc">Recruteurs vérifiés par documents officiels</div>
                    </div>
                </div>
                <div class="perk-item">
                    <div class="perk-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <div class="perk-title">Postulez en 1 clic</div>
                        <div class="perk-desc">Votre profil, toujours prêt</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="left-bottom">
            <p class="left-footer-text">© 2025 Recrutement Djibouti 🇩🇯</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="register-box">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>

            <div class="page-greeting">Créer un compte</div>
            <h1 class="page-title">Commencez votre<br>aventure professionnelle 🚀</h1>
            <p class="page-sub">Inscription gratuite, sans engagement.</p>

            <div class="stepper">
                <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'done' : ''; ?>">
                    <div class="step-circle"><?php echo $step > 1 ? '<i class="fas fa-check" style="font-size:12px;"></i>' : '1'; ?></div>
                    <div class="step-label">Informations</div>
                </div>
                <div class="step-connector <?php echo $step > 1 ? 'done' : ''; ?>"></div>
                <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <div class="step-circle">2</div>
                    <div class="step-label">Vérification</div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- ÉTAPE 1 -->
            <form method="POST" action="inscription.php?step=1" id="formStep1" style="display: <?php echo $step == 1 ? 'block' : 'none'; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="form-label">Je souhaite m'inscrire en tant que</div>
                    <div class="role-selector">
                        <input type="radio" name="role" id="role_candidat" value="candidat" class="role-option" checked>
                        <label for="role_candidat" class="role-label">
                            <div class="role-icon"><i class="fas fa-user-graduate"></i></div>
                            <div><div class="role-name">Candidat</div><div class="role-desc">Je cherche un emploi</div></div>
                        </label>
                        <input type="radio" name="role" id="role_recruteur" value="recruteur" class="role-option">
                        <label for="role_recruteur" class="role-label">
                            <div class="role-icon"><i class="fas fa-building"></i></div>
                            <div><div class="role-name">Recruteur</div><div class="role-desc">Je publie des offres</div></div>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="prenom">Prénom <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="prenom" name="prenom" class="form-input" placeholder="Votre prénom" required value="<?php echo isset($user_data['prenom']) ? htmlspecialchars($user_data['prenom']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nom">Nom <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="nom" name="nom" class="form-input" placeholder="Votre nom" required value="<?php echo isset($user_data['nom']) ? htmlspecialchars($user_data['nom']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Adresse email <span class="required">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-input" placeholder="votre@email.com" required value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>">
                    </div>
                    <div class="form-hint">Un code de vérification sera envoyé à cette adresse.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telephone">Téléphone <span class="opt">(optionnel)</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="telephone" name="telephone" class="form-input" placeholder="+253 77 00 00 00" value="<?php echo isset($user_data['telephone']) ? htmlspecialchars($user_data['telephone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="mot_de_passe">Mot de passe <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-input" placeholder="••••••••" required style="padding-right:42px;">
                            <button type="button" class="toggle-pw" onclick="togglePw('mot_de_passe','icon_pw1')"><i class="fas fa-eye" id="icon_pw1"></i></button>
                        </div>
                        <div class="form-hint">Minimum 6 caractères.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirmer <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required style="padding-right:42px;">
                            <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','icon_pw2')"><i class="fas fa-eye" id="icon_pw2"></i></button>
                        </div>
                    </div>
                </div>

                <!-- SECTION RECRUTEUR -->
                <div id="recruteurFields" style="display:none;">
                    <div class="section-divider"><span class="section-title-inline">📋 Informations entreprise</span></div>

                    <div class="form-group">
                        <label class="form-label" for="nom_entreprise">Nom de l'entreprise <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" id="nom_entreprise" name="nom_entreprise" class="form-input" placeholder="Nom officiel de l'entreprise" value="<?php echo isset($user_data['nom_entreprise']) ? htmlspecialchars($user_data['nom_entreprise']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="secteur">Secteur d'activité <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-industry input-icon"></i>
                            <select id="secteur" name="secteur" class="form-input">
                                <option value="">Sélectionner un secteur</option>
                                <?php $secteurs = ['Informatique & Télécoms','Banque & Finance','Commerce & Distribution','BTP & Construction','Transport & Logistique','Hôtellerie & Tourisme','Santé & Social','Éducation & Formation','Agriculture & Pêche','Énergie','Autre']; ?>
                                <?php foreach ($secteurs as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo (isset($user_data['secteur']) && $user_data['secteur'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="adresse_entreprise">Adresse complète <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" id="adresse_entreprise" name="adresse_entreprise" class="form-input" placeholder="Adresse, quartier, ville" value="<?php echo isset($user_data['adresse_entreprise']) ? htmlspecialchars($user_data['adresse_entreprise']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="site_web">Site web <span class="opt">(optionnel)</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-globe input-icon"></i>
                                <input type="url" id="site_web" name="site_web" class="form-input" placeholder="https://www.exemple.com" value="<?php echo isset($user_data['site_web']) ? htmlspecialchars($user_data['site_web']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nif">NIF / Identifiant fiscal <span class="required">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-file-invoice input-icon"></i>
                                <input type="text" id="nif" name="nif" class="form-input" placeholder="Numéro d'identification fiscale" value="<?php echo isset($user_data['nif']) ? htmlspecialchars($user_data['nif']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="statut_juridique">Statut juridique <span class="required">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-gavel input-icon"></i>
                                <select id="statut_juridique" name="statut_juridique" class="form-input">
                                    <option value="">Sélectionner</option>
                                    <option value="SARL" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'SARL') ? 'selected' : ''; ?>>SARL</option>
                                    <option value="SA" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'SA') ? 'selected' : ''; ?>>SA</option>
                                    <option value="EURL" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'EURL') ? 'selected' : ''; ?>>EURL</option>
                                    <option value="SAS" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'SAS') ? 'selected' : ''; ?>>SAS</option>
                                    <option value="EI" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'EI') ? 'selected' : ''; ?>>Entreprise Individuelle</option>
                                    <option value="Autre" <?php echo (isset($user_data['statut_juridique']) && $user_data['statut_juridique'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nombre_employes">Nombre d'employés <span class="required">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-users input-icon"></i>
                                <select id="nombre_employes" name="nombre_employes" class="form-input">
                                    <option value="">Sélectionner</option>
                                    <option value="1-5" <?php echo (isset($user_data['nombre_employes']) && $user_data['nombre_employes'] == '1-5') ? 'selected' : ''; ?>>1-5 employés</option>
                                    <option value="6-20" <?php echo (isset($user_data['nombre_employes']) && $user_data['nombre_employes'] == '6-20') ? 'selected' : ''; ?>>6-20 employés</option>
                                    <option value="21-50" <?php echo (isset($user_data['nombre_employes']) && $user_data['nombre_employes'] == '21-50') ? 'selected' : ''; ?>>21-50 employés</option>
                                    <option value="51-200" <?php echo (isset($user_data['nombre_employes']) && $user_data['nombre_employes'] == '51-200') ? 'selected' : ''; ?>>51-200 employés</option>
                                    <option value="201+" <?php echo (isset($user_data['nombre_employes']) && $user_data['nombre_employes'] == '201+') ? 'selected' : ''; ?>>Plus de 200 employés</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"><span class="section-title-inline">📎 Documents obligatoires</span></div>
                    <div class="info-box" style="margin-bottom: 20px;">
                        <i class="fas fa-shield-alt"></i>
                        <p><strong>Validation administrative obligatoire :</strong> Pour garantir la fiabilité de la plateforme, tous les recruteurs doivent fournir les documents ci-dessous. Votre compte sera activé après vérification par notre équipe (24-48h).</p>
                    </div>

                    <!-- CNI -->
                    <div class="form-group">
                        <label class="form-label">Carte d'identité nationale (CNI) ou Passeport <span class="required">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('cni').click()">
                            <div class="file-upload-label">
                                <div class="file-upload-icon"><i class="fas fa-id-card"></i></div>
                                <div class="file-upload-text">
                                    <div class="file-name" id="cni_name">Cliquez pour télécharger</div>
                                    <div class="file-hint">PDF, JPG ou PNG (max 5 Mo)</div>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="cni" name="cni" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'cni_name')">
                    </div>

                    <!-- Registre de commerce -->
                    <div class="form-group">
                        <label class="form-label">Registre de commerce / Extrait RCCM <span class="required">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('registre_commerce').click()">
                            <div class="file-upload-label">
                                <div class="file-upload-icon"><i class="fas fa-file-alt"></i></div>
                                <div class="file-upload-text">
                                    <div class="file-name" id="registre_name">Cliquez pour télécharger</div>
                                    <div class="file-hint">PDF, JPG ou PNG (max 5 Mo)</div>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="registre_commerce" name="registre_commerce" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'registre_name')">
                    </div>

                    <!-- Attestation fiscale -->
                    <div class="form-group">
                        <label class="form-label">Attestation fiscale / Patente <span class="required">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('attestation_fiscale').click()">
                            <div class="file-upload-label">
                                <div class="file-upload-icon"><i class="fas fa-receipt"></i></div>
                                <div class="file-upload-text">
                                    <div class="file-name" id="fiscale_name">Cliquez pour télécharger</div>
                                    <div class="file-hint">PDF, JPG ou PNG (max 5 Mo)</div>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="attestation_fiscale" name="attestation_fiscale" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'fiscale_name')">
                    </div>

                    <!-- KBIS (optionnel) -->
                    <div class="form-group">
                        <label class="form-label">KBIS (facultatif mais recommandé) <span class="opt">(optionnel)</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('kbis').click()">
                            <div class="file-upload-label">
                                <div class="file-upload-icon"><i class="fas fa-file-pdf"></i></div>
                                <div class="file-upload-text">
                                    <div class="file-name" id="kbis_name">Cliquez pour télécharger</div>
                                    <div class="file-hint">PDF, JPG ou PNG (max 5 Mo)</div>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="kbis" name="kbis" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'kbis_name')">
                    </div>

                    <div class="section-divider"><span class="section-title-inline">💰 Abonnement</span></div>

                    <div class="form-group">
                        <label class="form-label">Choisissez votre offre</label>
                        <div class="plans-grid">
                            <?php foreach ($abonnements as $abo): ?>
                            <input type="radio" name="abonnement" value="<?php echo $abo['type_abonnement']; ?>" id="plan_<?php echo $abo['type_abonnement']; ?>" class="plan-radio" <?php echo $abo['type_abonnement'] == 'gratuit' ? 'checked' : ''; ?>>
                            <label for="plan_<?php echo $abo['type_abonnement']; ?>" class="plan-card">
                                <?php if ($abo['type_abonnement'] == 'pro'): ?>
                                    <div class="plan-badge-popular">POPULAIRE</div>
                                <?php endif; ?>
                                <div class="plan-name"><?php echo ucfirst($abo['type_abonnement']); ?></div>
                                <div class="plan-price">
                                    <?php if ($abo['prix_mensuel'] == 0): ?>
                                        Gratuit
                                    <?php else: ?>
                                        <?php echo number_format($abo['prix_mensuel'], 0, ',', ' '); ?><span> FDJ/mois</span>
                                    <?php endif; ?>
                                </div>
                                <ul class="plan-features">
                                    <li><i class="fas fa-check"></i><?php echo $abo['limite_offres']; ?> offres/mois</li>
                                    <li><i class="fas fa-check"></i><?php echo $abo['limite_cv_par_offre']; ?> CV/offre</li>
                                    <?php if ($abo['mise_en_avant']): ?><li><i class="fas fa-check"></i>Mise en avant</li><?php endif; ?>
                                    <?php if ($abo['acces_analytique']): ?><li><i class="fas fa-check"></i>Statistiques avancées</li><?php endif; ?>
                                    <?php if ($abo['priorite_support']): ?><li><i class="fas fa-check"></i>Support prioritaire</li><?php endif; ?>
                                </ul>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-arrow-right"></i> Continuer — Vérification email</button>
            </form>

            <!-- ÉTAPE 2 -->
            <form method="POST" action="inscription.php?step=2" id="formStep2" style="display: <?php echo $step == 2 ? 'block' : 'none'; ?>">
                <div class="verify-center">
                    <div class="verify-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="verify-title">Vérifiez votre email</div>
                    <p class="verify-desc">Nous avons envoyé un code de vérification à<br><span class="verify-email"><?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?></span></p>
                </div>
                <div class="form-group">
                    <label class="form-label" style="text-align:center;display:block;">Code à 6 chiffres</label>
                    <input type="text" id="code_verification" name="code_verification" class="code-input" placeholder="000000" maxlength="6" required inputmode="numeric">
                    <div class="form-hint" style="text-align:center;margin-top:10px;">Code valable 24h · Vérifiez vos spams si vous ne le recevez pas.</div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Vérifier et créer mon compte</button>
                <a href="inscription.php?step=1" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
            </form>

            <div class="login-prompt">
                <p>Déjà inscrit ? <a href="connexion.php">Se connecter <i class="fas fa-arrow-right" style="font-size:11px;"></i></a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function updateFileName(input, spanId) {
            const span = document.getElementById(spanId);
            if (input.files && input.files[0]) {
                span.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + input.files[0].name;
                span.style.color = '#10b981';
            } else {
                span.innerHTML = 'Cliquez pour télécharger';
                span.style.color = '';
            }
        }

        const roleCandidat = document.getElementById('role_candidat');
        const roleRecruteur = document.getElementById('role_recruteur');
        const recruteurFields = document.getElementById('recruteurFields');

        function toggleRecruteurFields() {
            const isRecruteur = roleRecruteur.checked;
            recruteurFields.style.display = isRecruteur ? 'block' : 'none';
            
            if (isRecruteur) {
                document.getElementById('nom_entreprise').setAttribute('required', 'required');
                document.getElementById('secteur').setAttribute('required', 'required');
                document.getElementById('adresse_entreprise').setAttribute('required', 'required');
                document.getElementById('nif').setAttribute('required', 'required');
                document.getElementById('statut_juridique').setAttribute('required', 'required');
                document.getElementById('nombre_employes').setAttribute('required', 'required');
                document.getElementById('cni').setAttribute('required', 'required');
                document.getElementById('registre_commerce').setAttribute('required', 'required');
                document.getElementById('attestation_fiscale').setAttribute('required', 'required');
                document.getElementById('site_web').removeAttribute('required');
                document.getElementById('kbis').removeAttribute('required');
            } else {
                const allFields = recruteurFields.querySelectorAll('input, select');
                allFields.forEach(field => {
                    field.removeAttribute('required');
                });
            }
        }

        roleCandidat.addEventListener('change', toggleRecruteurFields);
        roleRecruteur.addEventListener('change', toggleRecruteurFields);
        toggleRecruteurFields();

        const codeInput = document.getElementById('code_verification');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>