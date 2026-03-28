<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Mot de passe à utiliser
$password = '20232024';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>🔧 Création de l'administrateur</h2>";
echo "<p><strong>Email :</strong> kaderyoussoufelmi@gmail.com</p>";
echo "<p><strong>Mot de passe :</strong> 20232024</p>";
echo "<p><strong>Hash généré :</strong> <code>" . $hashed_password . "</code></p><hr>";

// Supprimer tous les admins existants
$conn->exec("DELETE FROM utilisateurs WHERE role = 'admin'");
echo "<p>✅ Anciens administrateurs supprimés</p>";

// Créer le nouvel admin
$sql = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, est_actif, date_creation) 
        VALUES (:email, :password, :nom, :prenom, 'admin', 1, NOW())";

$stmt = $conn->prepare($sql);
$result = $stmt->execute([
    ':email' => 'kaderyoussoufelmi@gmail.com',
    ':password' => $hashed_password,
    ':nom' => 'Youssouf',
    ':prenom' => 'Kader'
]);

if ($result) {
    echo "<p style='color: green;'>✅ Administrateur créé avec succès !</p>";
} else {
    echo "<p style='color: red;'>❌ Erreur lors de la création</p>";
}

// Vérification
$check = $conn->prepare("SELECT id, email, role, est_actif FROM utilisateurs WHERE role = 'admin'");
$check->execute();
$admin = $check->fetch();

if ($admin) {
    echo "<hr><h3>✅ Vérification :</h3>";
    echo "<ul>";
    echo "<li>ID: " . $admin['id'] . "</li>";
    echo "<li>Email: " . $admin['email'] . "</li>";
    echo "<li>Rôle: " . $admin['role'] . "</li>";
    echo "<li>Actif: " . ($admin['est_actif'] ? 'Oui' : 'Non') . "</li>";
    echo "</ul>";
    
    // Tester la vérification du mot de passe
    echo "<h3>🔐 Test de vérification :</h3>";
    if (password_verify('20232024', $hashed_password)) {
        echo "<p style='color: green;'>✅ Le mot de passe '20232024' est valide !</p>";
    } else {
        echo "<p style='color: red;'>❌ Problème avec le hash du mot de passe</p>";
    }
}

echo "<hr>";
echo "<a href='connexion.php' style='display: inline-block; padding: 10px 20px; background: #1a56ff; color: white; text-decoration: none; border-radius: 5px;'>Aller à la page de connexion</a>";
?>