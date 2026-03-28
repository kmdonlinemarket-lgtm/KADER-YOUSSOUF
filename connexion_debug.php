<?php
require_once 'config/database.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

echo "<h1>🔧 Test de connexion</h1>";

// Récupérer l'admin
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
$stmt->execute(['email' => 'kaderyoussoufelmi@gmail.com']);
$user = $stmt->fetch();

if ($user) {
    echo "<p>✅ Utilisateur trouvé !</p>";
    echo "<ul>";
    echo "<li>ID: " . $user['id'] . "</li>";
    echo "<li>Email: " . $user['email'] . "</li>";
    echo "<li>Nom: " . $user['nom'] . "</li>";
    echo "<li>Prénom: " . $user['prenom'] . "</li>";
    echo "<li>Rôle: " . $user['role'] . "</li>";
    echo "<li>Actif: " . ($user['est_actif'] ? 'Oui' : 'Non') . "</li>";
    echo "<li>Hash stocké: " . $user['mot_de_passe'] . "</li>";
    echo "</ul>";
    
    // Tester le mot de passe
    $test_password = '20232024';
    if (password_verify($test_password, $user['mot_de_passe'])) {
        echo "<p style='color: green;'>✅ Le mot de passe '20232024' est correct !</p>";
        
        // Connexion manuelle
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['role'] = $user['role'];
        
        echo "<p style='color: green;'>✅ Session créée !</p>";
        echo "<a href='pages/admin/dashboard.php' style='display: inline-block; padding: 10px 20px; background: #1a56ff; color: white; text-decoration: none; border-radius: 5px;'>Accéder au dashboard admin</a>";
    } else {
        echo "<p style='color: red;'>❌ Le mot de passe '20232024' est incorrect</p>";
        
        // Générer un nouveau hash
        $new_hash = password_hash('20232024', PASSWORD_DEFAULT);
        echo "<p>Nouveau hash à utiliser: <code>" . $new_hash . "</code></p>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='update'>";
        echo "<button type='submit' style='padding: 8px 16px; background: #f59e0b; color: white; border: none; border-radius: 5px; cursor: pointer;'>Mettre à jour le mot de passe</button>";
        echo "</form>";
    }
} else {
    echo "<p style='color: red;'>❌ Aucun utilisateur trouvé avec cet email</p>";
    echo "<a href='fix_admin.php' style='display: inline-block; padding: 10px 20px; background: #1a56ff; color: white; text-decoration: none; border-radius: 5px;'>Créer l'administrateur</a>";
}

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $new_hash = password_hash('20232024', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = :password WHERE email = 'kaderyoussoufelmi@gmail.com'");
    $stmt->execute(['password' => $new_hash]);
    echo "<p style='color: green;'>✅ Mot de passe mis à jour ! <a href='connexion_debug.php'>Rafraîchir</a></p>";
}
?>