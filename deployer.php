<?php
// Activation de l'affichage des erreurs pour le débogage
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//ini_set('display_errors', 0);
//error_reporting(0);
session_start();

require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (file_exists(__DIR__ . '/facture.php')) {
    require_once __DIR__ . '/facture.php';
}


// Connexion BDD Landing Page
try {
    $bdd = new PDO('mysql:host=localhost;dbname=' . $dbname . ';charset=utf8mb4', $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

// $commande_id = (int)($_GET['commande_id'] ?? 0);
//if ($commande_id <= 0) {
//    die("ID de commande invalide.");
//}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Token de configuration invalide ou absent.");
}

// 1. Récupération des données client
$stmt = $bdd->prepare("
    SELECT c.*, p.*
    FROM commandes c
    JOIN prospects_configurations p ON c.id = p.commande_id
    WHERE p.token = ?
");
$stmt->execute([$token]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    die("Configuration introuvable pour cette commande.");
}

// =========================================================================
// AJOUT : Inscription des accès du client dans la BDD de la boutique
// =========================================================================
try {
    // Connexion à la BDD de la boutique
    $bdd_boutique = new PDO('mysql:host=localhost;dbname=toso8422_boutique;charset=utf8mb4', $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // // Insertion du compte client s'il n'existe pas déjà
    // $stmt_user = $bdd_boutique->prepare("
    //     INSERT INTO admin_users (username, password_hash)
    //     VALUES (?, ?)
    //     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
    // ");
    // $stmt_user->execute([
    //     $config['email_admin'],
    //     $config['mot_de_passe_hash']
    // ]);

    // On extrait le sous-domaine à partir du nom d'entreprise
    // $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $config['nom_entreprise']));
    $subdomain = $config['subdomain'];

    // Insertion / Mise à jour de l'utilisateur avec son sous-domaine
    $check_stmt = $bdd_boutique->prepare("SELECT id FROM admin_users WHERE username = ?");
    $check_stmt->execute([$config['email_admin']]);

    $user_exists = $check_stmt->fetch();
    if ($user_exists) {
        $stmt = $bdd_boutique->prepare("UPDATE admin_users SET password_hash = ?, subdomain = ? WHERE username = ?");
        $stmt->execute([$config['mot_de_passe_hash'], $subdomain, $config['email_admin']]);
    } else {
        $stmt = $bdd_boutique->prepare("INSERT INTO admin_users (username, password_hash, subdomain) VALUES (?, ?, ?)");
        $stmt->execute([$config['email_admin'], $config['mot_de_passe_hash'], $subdomain]);
    } 



} catch (Exception $e) {
    die('Erreur lors de la création du compte boutique : ' . $e->getMessage());
}
// =========================================================================


// 2. LOGIQUE D'AUTO-DÉPLOIEMENT
// ... (Ton code d'instanciation de la boutique) ...


// 3. Mise à jour du statut
$update = $bdd->prepare("UPDATE prospects_configurations SET statut_deploiement = 'deploye' WHERE token = ?");
$update->execute([$token]);

$updateCmd = $bdd->prepare("UPDATE commandes SET statut = 'deploye' WHERE id = ?");
$updateCmd->execute([$commande_id]);



// 4. Envoi du mail d'accès au client
/////////////////////////////////////////////////////
$to = $config['email_admin'];

// Génération de l'URL dédiée au client (basée sur son sous-domaine / nom d'entreprise)
$url_admin_client = get_client_admin_url($config['nom_entreprise']);

$charset = 'UTF-8';
$subject = mb_encode_mimeheader("Votre boutique est prête ! 🚀", $charset);

$message = "Bonjour " . htmlspecialchars($config['nom_entreprise']) . ",\n\n";
$message .= "Votre boutique en ligne a été déployée avec succès !\n\n";

$message .= "👉 Cliquez sur le lien ci-dessous pour accéder directement à votre espace d'administration :\n";
$message .= $url_admin_client . "\n\n";
$message .= "----------------------------------------\n";
$message .= "Rappel de vos identifiants de connexion :\n";
$message .= "• Identifiant (E-mail) : " . $config['email_admin'] . "\n";
$message .= "• Mot de passe : (celui défini lors de votre configuration)\n";
$message .= "----------------------------------------\n\n";
$message .= "Merci pour votre confiance.\nBenjamin Louis";
$headers = "From: boutique@benjaminlouis.eu\r\nContent-Type: text/plain; charset=UTF-8";
mail($to, $subject, $message, $headers);
//////////////////////////////////////////////////////


// Redirection vers l'admin avec confirmation
header('Location: admin.php?deploiement=succes');
exit;

 