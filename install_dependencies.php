<?php
/**
 * Script de vérification de l'installation
 * Accédez à : http://localhost/recrutement-djibouti/install_dependencies.php
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Vérification Dépendances - Recrutement Djibouti</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; }
        .check { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #27ae60; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left: 4px solid #f39c12; color: #856404; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .info-box { background: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 5px; }
        button { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #229954; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Vérification de l'Installation</h1>";

// 1. Vérifier PHP
echo "<div class='check success'>
    <strong>✅ Version PHP</strong><br>
    PHP " . phpversion() . " détecté
</div>";

// 2. Vérifier Composer
echo "<div class='check '>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<div class='success'>
        <strong>✅ Composer</strong><br>
        PHPMailer est installé
    </div>";
} else {
    echo "<div class='error'>
        <strong>❌ Composer</strong><br>
        PHPMailer n'est pas installé
    </div>";
    echo "<div class='info-box'>
        <strong>Pour installer :</strong>
        <div class='code'>composer install</div>
        <p>Si Composer n'est pas installé : <a href='https://getcomposer.org/download/' target='_blank'>Télécharger Composer</a></p>
    </div>";
}
echo "</div>";

// 3. Vérifier extensions PHP
$extensions = ['openssl', 'curl', 'sockets'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    $class = extension_loaded($ext) ? 'success' : 'error';
    echo "<div class='check $class'>
        <strong>$status Extension $ext</strong>
    </div>";
}

// 4. Extensions de fichiers
echo "<div class='check '>";
echo "<strong>📁 Dossiers</strong><br>";
$folders = [
    'vendor/' => 'Dépendances Composer',
    'config/' => 'Configuration',
    'includes/' => 'Includes',
    'pages/' => 'Pages',
    'assets/' => 'Assets',
];

foreach ($folders as $folder => $desc) {
    $exists = is_dir(__DIR__ . '/' . $folder);
    $status = $exists ? '✅' : '❌';
    $class = $exists ? 'success' : 'error';
    echo '<div class="'.$class.'" style="margin: 5px 0; padding: 5px;'.$status.' ' . $desc . ' (' . $folder . ')</div>';
}
echo "</div>";

// 5. Vérifier fichier sensible
echo "<div class='check '>";
echo "<strong>🔑 Configuration</strong><br>";
$files = ['config/mail.php' => 'Mail', 'config/database.php' => 'Database'];
foreach ($files as $file => $name) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅' : '❌';
    $class = $exists ? 'success' : 'error';
    echo '<div class="'.$class.'" style="margin: 5px 0; padding: 5px;">'.$status.' ' . $name . ' (' . $file . ')</div>';
}
echo "</div>";

// 6. Instructions suivantes
echo "<div class='info-box'>
    <h3>📋 Prochaines étapes :</h3>
    <ol>
        <li>Exécutez <strong>composer install</strong> si PHPMailer n'est pas détecté</li>
        <li>Consultez <a href='INSTALLATION_EMAIL.md'>INSTALLATION_EMAIL.md</a> pour configurer les emails</li>
        <li>Configurez <strong>config/mail.php</strong> avec vos identifiants Gmail</li>
        <li>Testez un envoi d'email</li>
    </ol>
</div>";

// 7. Lien de test
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<div style='text-align: center; margin-top: 30px;'>
        <a href='test_email.php'><button>🧪 Tester l'envoi d'email</button></a>
    </div>";
}

echo "
    </div>
</body>
</html>";
?>
