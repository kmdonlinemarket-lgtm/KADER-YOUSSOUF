<?php
/**
 * Test d'envoi d'email
 * Accédez à : http://localhost/recrutement-djibouti/test_email.php
 */

require 'config/mail.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Email - Recrutement Djibouti</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; }
        .result { padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; font-size: 16px; }
        .success { background: #d4edda; border: 2px solid #27ae60; color: #155724; }
        .error { background: #f8d7da; border: 2px solid #dc3545; color: #721c24; }
        .info-box { background: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 5px; }
        input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; box-sizing: border-box; margin: 10px 0; }
        button { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background: #229954; }
        label { display: block; font-weight: bold; margin: 10px 0 5px 0; }
        .form-group { margin: 15px 0; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Test d'Envoi d'Email</h1>";

// Validation que PHPMailer est disponible
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<div class='result error'>
        <strong>❌ ERREUR</strong><br>
        PHPMailer n'est pas installé.<br>
        <br>
        Exécutez : <code>composer install</code>
    </div>";
} else {
    // Traitement du formulaire
    if ($_POST && isset($_POST['email']) && isset($_POST['action'])) {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $action = $_POST['action'];
        
        if (!$email) {
            echo "<div class='result error'>
                <strong>❌ Email invalide</strong>
            </div>";
        } else {
            $mailer = new Mailer();
            $success = false;
            
            if ($action == 'verification') {
                $code = rand(100000, 999999);
                $success = $mailer->envoyerCodeVerification($email, 'Test', $code);
                $message = "Code de vérification envoyé";
            } elseif ($action == 'bienvenue') {
                $success = $mailer->envoyerBienvenue($email, 'Test Candidat', 'candidat');
                $message = "Email de bienvenue envoyé";
            } elseif ($action == 'validation') {
                $success = $mailer->envoyerAttenteValidation($email, 'Test Recruteur', 'Test SARL');
                $message = "Email d'attente de validation envoyé";
            }
            
            if ($success) {
                echo "<div class='result success'>
                    <strong>✅ SUCCÈS</strong><br>
                    $message à $email<br><br>
                    Vérifiez votre boîte email !
                </div>";
            } else {
                echo "<div class='result error'>
                    <strong>❌ ERREUR</strong><br>
                    Impossible d'envoyer l'email.<br><br>
                    Vérifiez les identifiants SMTP dans <code>config/mail.php</code>
                </div>";
            }
        }
    }
    
    // Afficher le formulaire
    echo "
    <div class='info-box'>
        <strong>Comment tester :</strong>
        <ol>
            <li>Entrez votre adresse email</li>
            <li>Choisissez le type d'email à tester</li>
            <li>Cliquez sur « Envoyer »</li>
            <li>Vérifiez votre boîte email (et spam)</li>
        </ol>
    </div>
    
    <form method='POST'>
        <div class='form-group'>
            <label for='email'>📧 Votre adresse email :</label>
            <input type='email' id='email' name='email' required placeholder='exemple@gmail.com' autocomplete='off'>
        </div>
        
        <div class='form-group'>
            <label for='action'>📝 Type d'email à tester :</label>
            <select id='action' name='action' style='padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%;'>
                <option value='verification'>Code de vérification (Inscription)</option>
                <option value='bienvenue'>Email de bienvenue</option>
                <option value='validation'>Attente de validation (Recruteur)</option>
            </select>
        </div>
        
        <button type='submit'>📤 Envoyer l'email de test</button>
    </form>
    ";
}

echo "
    </div>
</body>
</html>";
?>
