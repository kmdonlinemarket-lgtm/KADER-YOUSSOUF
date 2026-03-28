<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$password = '20232024';
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Supprimer les anciens admins
$conn->exec("DELETE FROM utilisateurs WHERE role = 'admin'");

// Créer l'admin avec vos identifiants
$sql = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, est_actif, date_creation) 
        VALUES ('kaderyoussoufelmi@gmail.com', :password, 'Youssouf', 'Kader', 'admin', 1, NOW())";

$stmt = $conn->prepare($sql);
$result = $stmt->execute(['password' => $hashed]);

if ($result) {
    echo "<h2 style='color: green;'>✅ Administrateur créé avec succès !</h2>";
    echo "<p><strong>Email :</strong> kaderyoussoufelmi@gmail.com</p>";
    echo "<p><strong>Mot de passe :</strong> 20232024</p>";
    echo "<hr>";
    echo "<a href='connexion.php' style='display: inline-block; padding: 10px 20px; background: #1a56ff; color: white; text-decoration: none; border-radius: 5px;'>Se connecter</a>";
} else {
    echo "<h2 style='color: red;'>❌ Erreur lors de la création</h2>";
}
?>