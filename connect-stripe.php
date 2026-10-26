<?php

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

$commande_id = (int)($_GET['commande_id'] ?? 0);

// --- SÉCURITÉ BOUTIQUE DÉMO / PRODUCTION ---
if (strpos($_SERVER['HTTP_HOST'], 'boutique.benjaminlouis.eu') !== false) {
    // Clé de TEST pour la démo
    $active_stripe_key = STRIPE_SECRET_KEY;
} else {
    // Clé LIVE (production) pour tes vrais clients
    $active_stripe_key = STRIPE_SECRET_KEY_LIVE;
}

if ($commande_id <= 0) {
    die("Commande invalide.");
}

try {
    // Connexion BDD
    $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

  

    // Fonction utilitaire pour les appels cURL v2
    function stripeV2Request($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_USERPWD, $active_stripe_key . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Stripe-Version: 2025-04-30.preview'
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    // Récupération des infos du prospect/boutique
    $stmt = $bdd->prepare("SELECT * FROM prospects_configurations WHERE commande_id = ?");
    $stmt->execute([$commande_id]);
    $prospect = $stmt->fetch();

    if (!$prospect) {
        die("Configuration de boutique introuvable.");
    }

    $stripe_account_id = $prospect['stripe_account_id'] ?? null;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    
    // $domainName = $_SERVER['HTTP_HOST'];
    // On reconstruit proprement le domaine du client à partir de ce qui est enregistré en BDD (ex: eurllinx -> eurllinx.benjaminlouis.eu)
    $clientDomain = $prospect['subdomain'] . ".benjaminlouis.eu";

    $refresh_url = $protocol . $clientDomain . "/admin.php?commande_id=" . $commande_id;
    $return_url  = $protocol . $clientDomain . "/admin.php?commande_id=" . $commande_id . "&stripe_success=1";


    // 1. Création du token de compte (obligatoire pour les plateformes FR)
    function stripeV1Request($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $active_stripe_key . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2025-04-30.preview'
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }


    if (empty($stripe_account_id)) {
    \Stripe\Stripe::setApiKey($active_stripe_key);
    
    $account = \Stripe\Account::create([
        'controller' => [
            'requirement_collection' => 'stripe',
            'stripe_dashboard' => ['type' => 'express'],
            'fees' => ['payer' => 'application'],
            'losses' => ['payments' => 'application'],
        ],
        'country' => 'FR',
        'email' => $prospect['email_admin'] ?? null,
      
    ]);

    if (isset($account->error)) {
        die('Erreur création compte Stripe : ' . $account->error->message);
    }

    $stripe_account_id = $account->id;

    $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
    $updateStmt->execute([$stripe_account_id, $commande_id]);
}

    // Définition de la clé API
    \Stripe\Stripe::setApiKey($active_stripe_key);

    // 2. Création du lien d'onboarding
    $accountLink = \Stripe\AccountLink::create([
        'account' => $stripe_account_id,
        'refresh_url' => $refresh_url,
        'return_url' => $return_url,
        'type' => 'account_onboarding',
    ]);

    // 3. Redirection
    header("Location: " . $accountLink->url);
    exit();

} catch (Exception $e) {
    die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
}
