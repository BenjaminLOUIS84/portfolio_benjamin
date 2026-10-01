<?php
session_start();

// Connexion BDD
try {
    $bdd = new PDO('mysql:host=localhost;dbname=' . $dbname . ';charset=utf8mb4', $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

$commande_id = (int)($_GET['commande_id'] ?? 0);

if ($commande_id <= 0) {
    die("ID de commande invalide.");
}

// 1. Récupération des données client
$stmt = $bdd->prepare("
    SELECT c.*, p.*
    FROM commandes c
    JOIN prospects_configurations p ON c.id = p.commande_id
    WHERE c.id = ?
");
$stmt->execute([$commande_id]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    die("Configuration introuvable pour cette commande.");
}

// 2. LOGIQUE D'AUTO-DÉPLOIEMENT (Exemple : Génération du sous-dossier ou de la BDD client)
// Ici tu exécutes la création des tables / dossiers de la boutique du client

// ... (Ton code d'instanciation de la boutique) ...

// 3. Mise à jour du statut
$update = $bdd->prepare("UPDATE prospects_configurations SET statut_deploiement = 'deploye' WHERE commande_id = ?");
$update->execute([$commande_id]);

// 4. Envoi du mail d'accès au client
$to = $config['email_admin'];
$subject = "Votre boutique est prête ! 🚀";
$message = "Bonjour " . htmlspecialchars($config['nom_entreprise']) . ",\n\n";
$message .= "Votre boutique en ligne a été déployée avec succès !\n\n";
$message .= "Accès Espace Administration :\n";
$message .= "E-mail : " . $config['email_admin'] . "\n\n";
$message .= "Merci pour votre confiance.\nBenjamin Louis";

$headers = "From: boutique@benjaminlouis.eu\r\nContent-Type: text/plain; charset=UTF-8";
mail($to, $subject, $message, $headers);

// Redirection vers l'admin avec confirmation
header('Location: admin.php?deploiement=succes');
exit;

 