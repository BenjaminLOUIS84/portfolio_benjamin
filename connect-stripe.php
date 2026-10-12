<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // Charge la bibliothèque stripe-php

// Récupération de l'ID de commande depuis l'URL
$commande_id = (int)($_GET['commande_id'] ?? 0);

if ($commande_id <= 0) {
    die("Commande invalide.");
}

try {
    // Connexion BDD
    $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Récupération des infos du prospect/boutique
    $stmt = $bdd->prepare("SELECT * FROM prospects_configurations WHERE commande_id = ?");
    $stmt->execute([$commande_id]);
    $prospect = $stmt->fetch();

    if (!$prospect) {
        die("Configuration de boutique introuvable.");
    }

    // Définition de ta clé secrète Stripe (Mode Test)
    // Remplace par ta vraie clé secrète sk_test_... ou stocke-la dans config.php
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $stripe_account_id = $prospect['stripe_account_id'] ?? null;

   // 1. Si le marchand n'a pas encore de compte Stripe Connect associé, on en crée un via l'API v2
    if (empty($stripe_account_id)) {
        $account = \Stripe\V2\Core\Accounts::create([
            'type' => 'express',
            'contact_email' => $prospect['email_admin'] ?? null,
        ]);

        $stripe_account_id = $account->id;

        // On sauvegarde immédiatement le stripe_account_id en base de données
        $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
        $updateStmt->execute([$stripe_account_id, $commande_id]);
    }


    // 2. Génération du lien d'onboarding (Account Link) pour rediriger le marchand sur Stripe
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
   
    // URL de retour en cas de succès ou d'annulation de l'onboarding
    $refresh_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id;
    $return_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id . "&stripe_success=1";

    $accountLink = \Stripe\AccountLink::create([
        'account' => $stripe_account_id,
        'refresh_url' => $refresh_url,
        'return_url' => $return_url,
        'type' => 'account_onboarding',
    ]);

    // 3. Redirection vers l'interface Stripe Connect
    header('Location: ' . $accountLink->url);
    exit();

} catch (Exception $e) {
    die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
}
