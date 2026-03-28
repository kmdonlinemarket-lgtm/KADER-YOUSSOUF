<?php
require_once 'config/database.php';
require_once 'config/mail.php';
session_start();

$db = new Database();
$conn = $db->getConnection();
$mailer = new Mailer();

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$email = '';
$code = '';
$user_id = null;

// ÉTAPE 1 : Demande de réinitialisation
if ($step == 1 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Veuillez saisir votre adresse email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        // Vérifier si l'email existe
        $stmt = $conn->prepare("SELECT id, email, prenom, nom FROM utilisateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Générer un code de réinitialisation
            $reset_code = rand(100000, 999999);
            $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Stocker le code dans la base de données
            $update = $conn->prepare("UPDATE utilisateurs SET token_reset = :code, code_expiration = :expiration WHERE id = :id");
            $update->execute([
                'code' => $reset_code,
                'expiration' => $expiration,
                'id' => $user['id']
            ]);
            
            // Envoyer l'email avec le code
            $mailer->envoyerCodeReinitialisation($user['email'], $user['prenom'], $reset_code);
            
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_user_id'] = $user['id'];
            
            $success = "Un code de réinitialisation a été envoyé à votre adresse email.";
            $step = 2;
        } else {
            $error = "Aucun compte trouvé avec cette adresse email.";
        }
    }
}

// ÉTAPE 2 : Vérification du code
if ($step == 2 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code']);
    $email = $_SESSION['reset_email'] ?? '';
    $user_id = $_SESSION['reset_user_id'] ?? null;
    
    if (empty($code)) {
        $error = "Veuillez saisir le code de vérification.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE id = :id AND token_reset = :code AND code_expiration > NOW()");
        $stmt->execute(['id' => $user_id, 'code' => $code]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['reset_code_verified'] = true;
            $step = 3;
        } else {
            $error = "Code invalide ou expiré. Veuillez réessayer.";
        }
    }
}

// ÉTAPE 3 : Nouveau mot de passe
if ($step == 3 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $user_id = $_SESSION['reset_user_id'] ?? null;
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = :password, token_reset = NULL, code_expiration = NULL WHERE id = :id");
        $result = $update->execute(['password' => $hashed_password, 'id' => $user_id]);
        
        if ($result) {
            // Nettoyer la session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_code_verified']);
            
            $_SESSION['success_message'] = "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.";
            header('Location: connexion.php');
            exit();
        } else {
            $error = "Erreur lors de la modification du mot de passe.";
        }
    }
}

// Si on essaie d'accéder à l'étape 3 sans avoir vérifié le code
if ($step == 3 && (!isset($_SESSION['reset_code_verified']) || $_SESSION['reset_code_verified'] !== true)) {
    header('Location: oubli_mot_de_passe.php?step=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mot de passe oublié — Recrutement Djibouti</title>
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
            --surface: #f7f8fc;
            --white: #ffffff;
            --muted: #6b7a99;
            --border: #e2e6f0;
            --danger: #ef4444;
            --danger-pale: #fef2f2;
            --success: #10b981;
            --success-pale: #ecfdf5;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow-lg: 0 24px 80px rgba(10,15,30,0.18);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 450px;
            animation: fadeSlideUp 0.6s ease both;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--ink) 0%, var(--ink-soft) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .card-header p {
            font-size: 14px;
            opacity: 0.8;
        }

        .card-body {
            padding: 32px;
        }

        /* Stepper */
        .stepper {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--border);
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            z-index: 1;
            position: relative;
        }

        .step.active .step-circle {
            background: var(--azure);
            color: white;
            box-shadow: 0 0 0 4px rgba(26,86,255,0.2);
        }

        .step.completed .step-circle {
            background: var(--success);
            color: white;
        }

        .step-label {
            font-size: 11px;
            color: var(--muted);
            margin-top: 8px;
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--azure);
            font-weight: 600;
        }

        .step.completed .step-label {
            color: var(--success);
        }

        .step:not(:last-child):before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }

        .step.active:before, .step.completed:before {
            background: var(--azure);
        }

        /* Form */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 15px;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 13px 16px 13px 46px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 16px;
            transition: all 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--azure);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.1);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            background: none;
            border: none;
            cursor: pointer;
        }

        .code-input {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 12px;
            font-family: monospace;
            padding: 16px 10px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--azure);
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--azure-light);
            transform: translateY(-1px);
        }

        .btn-back {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            border-color: var(--azure);
            color: var(--azure);
        }

        .alert {
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            border: 1.5px solid;
        }

        .alert-error {
            background: var(--danger-pale);
            border-color: rgba(239,68,68,0.25);
            color: #b91c1c;
        }

        .alert-success {
            background: var(--success-pale);
            border-color: rgba(16,185,129,0.25);
            color: #047857;
        }

        .info-text {
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            margin-top: 16px;
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .login-link a {
            color: var(--azure);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card-body {
                padding: 24px;
            }
            .card-header {
                padding: 24px;
            }
            .code-input {
                font-size: 24px;
                letter-spacing: 8px;
            }
            .step-circle {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }
            .step-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-key me-2"></i>Mot de passe oublié</h1>
                <p><?php echo $step == 1 ? 'Entrez votre email pour réinitialiser votre mot de passe' : ($step == 2 ? 'Vérifiez votre boîte email' : 'Créez un nouveau mot de passe'); ?></p>
            </div>
            <div class="card-body">
                <!-- Stepper -->
                <div class="stepper">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                        <div class="step-circle"><?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?></div>
                        <div class="step-label">Email</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                        <div class="step-circle"><?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?></div>
                        <div class="step-label">Code</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label">Nouveau mot de passe</div>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <!-- ÉTAPE 1 : Demande d'email -->
                <?php if ($step == 1): ?>
                <form method="POST" action="?step=1">
                    <div class="form-group">
                        <label class="form-label">Adresse email</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-input" placeholder="votre@email.com" required autocomplete="email">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Envoyer le code
                    </button>
                    <div class="info-text">
                        <i class="fas fa-info-circle"></i> Un code de réinitialisation vous sera envoyé par email.
                    </div>
                </form>
                <?php endif; ?>

                <!-- ÉTAPE 2 : Vérification du code -->
                <?php if ($step == 2): ?>
                <form method="POST" action="?step=2">
                    <div class="form-group">
                        <label class="form-label">Code de vérification</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="text" name="code" class="form-input code-input" placeholder="000000" maxlength="6" required autocomplete="off" inputmode="numeric">
                        </div>
                        <div class="info-text" style="margin-top: 12px;">
                            <i class="fas fa-envelope"></i> Nous avons envoyé un code à <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check-circle"></i> Vérifier le code
                    </button>
                    <a href="oubli_mot_de_passe.php?step=1" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Modifier l'email
                    </a>
                </form>
                <?php endif; ?>

                <!-- ÉTAPE 3 : Nouveau mot de passe -->
                <?php if ($step == 3): ?>
                <form method="POST" action="?step=3">
                    <div class="form-group">
                        <label class="form-label">Nouveau mot de passe</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="new_password" name="new_password" class="form-input" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', 'icon_new')">
                                <i class="fas fa-eye" id="icon_new"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'icon_confirm')">
                                <i class="fas fa-eye" id="icon_confirm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="info-text">
                        <i class="fas fa-shield-alt"></i> Le mot de passe doit contenir au moins 6 caractères.
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-key"></i> Réinitialiser le mot de passe
                    </button>
                </form>
                <?php endif; ?>

                <div class="login-link">
                    <a href="connexion.php">
                        <i class="fas fa-arrow-left"></i> Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Auto-format code input
        const codeInput = document.querySelector('.code-input');
        if (codeInput) {
            codeInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>