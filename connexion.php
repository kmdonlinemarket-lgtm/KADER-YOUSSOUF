<?php
require_once 'config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$error = '';
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    if (empty($email) || empty($mot_de_passe)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $query = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            if (!$user['est_actif']) {
                $error = "Votre compte n'est pas encore activé. Vérifiez votre email pour le code d'activation.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['role'];
                
                $update = "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id";
                $upd_stmt = $conn->prepare($update);
                $upd_stmt->execute(['id' => $user['id']]);
                
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : null;
                unset($_SESSION['redirect_after_login']);
                
                if ($redirect) {
                    header('Location: ' . $redirect);
                } elseif ($user['role'] == 'candidat') {
                    header('Location: pages/candidat/dashboard.php');
                } elseif ($user['role'] == 'recruteur') {
                    header('Location: pages/recruteur/dashboard.php');
                } elseif ($user['role'] == 'admin') {
                    header('Location: pages/admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Connexion — Recrutement Djibouti</title>
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
            --success: #10b981;
            --success-pale: #ecfdf5;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow-lg: 0 24px 80px rgba(10,15,30,0.18);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            overflow: hidden;
        }

        /* ─── LEFT PANEL ─── */
        .left-panel {
            background: linear-gradient(145deg, var(--ink) 0%, var(--ink-soft) 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 25% 35%, rgba(26,86,255,0.45) 0%, transparent 55%),
                radial-gradient(circle at 80% 75%, rgba(240,165,0,0.2) 0%, transparent 45%);
        }

        .left-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .left-top {
            position: relative;
            z-index: 2;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--azure);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: white;
            letter-spacing: -0.3px;
        }

        .brand-text span { color: var(--azure-light); }

        .left-center {
            position: relative;
            z-index: 2;
        }

        .left-kicker {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--azure-light);
            margin-bottom: 16px;
        }

        .left-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(2rem, 2.8vw, 3rem);
            font-weight: 800;
            color: white;
            line-height: 1.12;
            letter-spacing: -1.2px;
            margin-bottom: 20px;
        }

        .left-title span {
            background: linear-gradient(135deg, var(--azure-light), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .left-desc {
            font-size: 15px;
            line-height: 1.7;
            color: rgba(255,255,255,0.5);
            max-width: 380px;
            margin-bottom: 40px;
        }

        .preview-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .preview-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius);
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            animation: floatCard 6s ease-in-out infinite;
        }

        .preview-card:nth-child(2) {
            margin-left: 24px;
            animation-delay: -3s;
        }

        @keyframes floatCard {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .preview-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(26,86,255,0.25);
            border: 1px solid rgba(26,86,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #88aaff;
            font-size: 16px;
            flex-shrink: 0;
        }

        .preview-card-icon.gold {
            background: rgba(240,165,0,0.2);
            border-color: rgba(240,165,0,0.4);
            color: var(--gold-light);
        }

        .preview-card-title {
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }

        .preview-card-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 2px;
        }

        .left-bottom {
            position: relative;
            z-index: 2;
        }

        .left-footer-text {
            font-size: 13px;
            color: rgba(255,255,255,0.3);
        }

        /* ─── RIGHT PANEL ─── */
        .right-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            overflow-y: auto;
            background: var(--white);
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            animation: fadeSlideUp 0.6s ease both;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-back {
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

        .login-back:hover { color: var(--azure); }
        .login-back i { font-size: 12px; }

        .login-greeting {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--azure);
            margin-bottom: 10px;
        }

        .login-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.8px;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 15px;
            color: var(--muted);
        }

        /* ─── ALERTS ─── */
        .alert {
            border-radius: var(--radius);
            padding: 14px 18px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            border: 1.5px solid;
            font-weight: 500;
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

        .alert i { font-size: 15px; flex-shrink: 0; }

        /* ─── FORM ─── */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            letter-spacing: 0.1px;
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
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            padding: 13px 16px 13px 46px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            transition: all 0.2s;
            outline: none;
            appearance: none;
        }

        .form-input::placeholder { color: rgba(107,122,153,0.6); }

        .form-input:focus {
            border-color: var(--azure);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(26,86,255,0.1);
        }

        .form-input:focus ~ .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--azure); }

        /* Password toggle */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 15px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: var(--azure); }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            margin-top: -4px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 5px;
            border: 1.5px solid var(--border);
            accent-color: var(--azure);
            cursor: pointer;
        }

        .remember-wrap span {
            font-size: 13px;
            color: var(--muted);
            font-weight: 400;
        }

        .forgot-link {
            font-size: 13px;
            color: var(--azure);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .forgot-link:hover { opacity: 0.75; }

        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 13px;
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
            box-shadow: 0 6px 28px rgba(26,86,255,0.3);
            letter-spacing: 0.1px;
        }

        .btn-submit:hover {
            background: var(--azure-light);
            box-shadow: 0 8px 36px rgba(26,86,255,0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 28px 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
        }

        /* Register link */
        .register-prompt {
            text-align: center;
        }

        .register-prompt p {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .btn-register {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-register:hover {
            border-color: var(--azure);
            color: var(--azure);
            background: var(--azure-pale);
        }

        /* ─── RESPONSIVE MOBILE ─── */
        @media (max-width: 900px) {
            body { 
                grid-template-columns: 1fr; 
                overflow: auto; 
                min-height: 100vh;
            }
            .left-panel { 
                display: none; 
            }
            .right-panel { 
                padding: 32px 20px; 
                min-height: 100vh;
                align-items: flex-start;
            }
            .login-box {
                max-width: 100%;
            }
            .login-title {
                font-size: 1.8rem;
            }
            .form-input {
                font-size: 16px; /* Évite le zoom automatique sur iOS */
                padding: 14px 16px 14px 46px;
            }
            .btn-submit {
                padding: 16px;
                font-size: 16px;
            }
            .btn-register {
                padding: 12px 20px;
                width: 100%;
                justify-content: center;
            }
            .divider span {
                font-size: 11px;
            }
            .login-back {
                margin-bottom: 20px;
            }
            .login-header {
                margin-bottom: 28px;
            }
        }

        /* Pour les très petits écrans (moins de 480px) */
        @media (max-width: 480px) {
            .right-panel {
                padding: 24px 16px;
            }
            .login-title {
                font-size: 1.5rem;
            }
            .login-subtitle {
                font-size: 13px;
            }
            .form-input {
                padding: 12px 14px 12px 42px;
                font-size: 15px;
            }
            .input-icon {
                left: 12px;
                font-size: 14px;
            }
            .btn-submit {
                padding: 14px;
                font-size: 15px;
            }
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .alert {
                padding: 12px 14px;
                font-size: 13px;
            }
            .login-greeting {
                font-size: 11px;
            }
        }

        /* Pour les tablettes en mode portrait */
        @media (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
            .right-panel {
                padding: 40px 32px;
            }
            .login-box {
                max-width: 380px;
            }
        }

        /* Pour les écrans très hauts */
        @media (min-height: 800px) {
            .right-panel {
                align-items: center;
            }
        }

        /* Amélioration du tap target sur mobile */
        @media (max-width: 768px) {
            .btn-submit, 
            .btn-register,
            .toggle-password,
            .forgot-link,
            .remember-wrap {
                cursor: pointer;
                -webkit-tap-highlight-color: transparent;
            }
            .btn-submit:active {
                transform: scale(0.98);
            }
        }
    </style>
</head>
<body>

    <!-- ─── LEFT PANEL ─── -->
    <div class="left-panel">
        <div class="left-top">
            <a href="index.php" class="brand-link">
                <div class="brand-icon"><i class="fas fa-briefcase"></i></div>
                <div class="brand-text">Recrutement <span>Djibouti</span></div>
            </a>
        </div>

        <div class="left-center">
            <div class="left-kicker">Bienvenue</div>
            <h1 class="left-title">Votre carrière<br>commence <span>ici</span></h1>
            <p class="left-desc">
                Connectez-vous pour accéder aux meilleures opportunités d'emploi à Djibouti et gérer vos candidatures.
            </p>
            <div class="preview-cards">
                <div class="preview-card">
                    <div class="preview-card-icon"><i class="fas fa-bell"></i></div>
                    <div>
                        <div class="preview-card-title">Nouvelle candidature reçue</div>
                        <div class="preview-card-sub">Port de Djibouti · il y a 2 minutes</div>
                    </div>
                </div>
                <div class="preview-card">
                    <div class="preview-card-icon gold"><i class="fas fa-star"></i></div>
                    <div>
                        <div class="preview-card-title">Profil vu par 12 recruteurs</div>
                        <div class="preview-card-sub">Cette semaine · Mise à jour recommandée</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="left-bottom">
            <p class="left-footer-text">© 2024 Recrutement Djibouti 🇩🇯</p>
        </div>
    </div>

    <!-- ─── RIGHT PANEL ─── -->
    <div class="right-panel">
        <div class="login-box">

            <div class="login-header">
                <a href="index.php" class="login-back">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
                <div class="login-greeting">Connexion</div>
                <h1 class="login-title">Content de vous<br>revoir 👋</h1>
                <p class="login-subtitle">Entrez vos identifiants pour accéder à votre espace.</p>
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

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Adresse email</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="votre@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mot_de_passe">Mot de passe</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="mot_de_passe"
                            name="mot_de_passe"
                            class="form-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                            style="padding-right: 46px;"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()" tabindex="-1" id="toggle-btn">
                            <i class="fas fa-eye" id="toggle-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-wrap">
                        <input type="checkbox" name="remember">
                        <span>Se souvenir de moi</span>
                    </label>
                    <a href="oubli_mot_de_passe.php" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-arrow-right-to-bracket"></i> Se connecter
                </button>
            </form>

            <div class="divider"><span>Vous n'avez pas de compte ?</span></div>

            <div class="register-prompt">
                <a href="inscription.php" class="btn-register">
                    <i class="fas fa-user-plus"></i> Créer un compte gratuitement
                </a>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('mot_de_passe');
            const icon = document.getElementById('toggle-icon');
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
    </script>
</body>
</html>