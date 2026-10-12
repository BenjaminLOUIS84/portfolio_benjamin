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
    \Stripe\Stripe::setApiVersion('2023-10-16');

    $stripe_account_id = $prospect['stripe_account_id'] ?? null;

    // 1. Si le marchand n'a pas encore de compte associé, on en crée un via l'API v2 (cURL)
    if (empty($stripe_account_id)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v2/core/accounts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'contact_email' => $prospect['email_admin'] ?? null
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Stripe-Version: 2025-04-30.preview'
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $account = json_decode($result, true);

        if (isset($account['error'])) {
            die('Erreur Stripe API v2 : ' . ($account['error']['message'] ?? json_encode($account['error'])));
        }

        $stripe_account_id = $account['id'];

        // Sauvegarde immédiate en BDD
        $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
        $updateStmt->execute([$stripe_account_id, $commande_id]);
    }

    // 2. Génération du lien d'onboarding (Account Link) via l'API v2 (cURL)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];

    $refresh_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id;
    $return_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id . "&stripe_success=1";

    $chLink = curl_init();
    curl_setopt($chLink, CURLOPT_URL, 'https://api.stripe.com/v2/core/account_links');
    curl_setopt($chLink, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($chLink, CURLOPT_POST, 1);
    curl_setopt($chLink, CURLOPT_POSTFIELDS, json_encode([
        'account' => $stripe_account_id,
        'refresh_url' => $refresh_url,
        'return_url' => $return_url,
        'type' => 'account_onboarding'
    ]));
    curl_setopt($chLink, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($chLink, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Stripe-Version: 2025-04-30.preview'
    ]);

    $linkResult = curl_exec($chLink);
    curl_close($chLink);

    $accountLinkData = json_decode($linkResult, true);

    if (isset($accountLinkData['error'])) {
        die('Erreur Stripe Account Link v2 : ' . ($accountLinkData['error']['message'] ?? json_encode($accountLinkData['error'])));
    }

    $redirect_url = $accountLinkData['url'] ?? null;

    if (!$redirect_url) {
        die("Erreur : Impossible de récupérer l'URL de redirection Stripe.");
    }

    // 3. Redirection vers l'interface Stripe Connect
    header("Location: " . $redirect_url);
    exit();

 

} catch (Exception $e) {
    die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
}
