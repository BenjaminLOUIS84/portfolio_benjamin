<?php
require_once 'config.php';

// Défins tes identifiants d'administration ici
$username = 'admin';
$password = 'ChangerCeMotDePasse2026!'; // <--- Ton mot de passe sécurisé

$hash = password_hash($password, PASSWORD_BCRYPT);

if (isset($bdd)) {
    try {
        $stmt = $bdd->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:user, :hash)");
        $stmt->execute([':user' => $username, ':hash' => $hash]);
        echo "<h2 style='color:green;'>Compte administrateur créé avec succès !</h2>";
        echo "<p>Identifiant : <strong>" . htmlspecialchars($username) . "</strong></p>";
        echo "<p>Pense à supprimer ce fichier <code>create_admin.php</code> du serveur.</p>";
    } catch (\PDOException $e) {
        echo "<h2 style='color:red;'>Erreur : " . $e->getMessage() . "</h2>";
    }
} else {
    echo "Erreur : Connexion BDD non disponible (\$bdd).";
}

 
