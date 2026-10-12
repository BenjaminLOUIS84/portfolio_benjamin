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

// 1. Si le marchand n'a pas encore de compte Stripe Connect associé, on en crée un via cURL
    if (empty($stripe_account_id)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/accounts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'type' => 'express',
            'email' => $prospect['email_admin'] ?? null,
            'capabilities' => [
                'card_payments' => ['requested' => 'true'],
                'transfers' => ['requested' => 'true'],
            ],
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Stripe-Version: 2023-10-16' // Version forcée pour accepter v1
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $account = json_decode($result, true);

        if (isset($account['error'])) {
            die('Erreur Stripe : ' . $account['error']['message']);
        }

        $stripe_account_id = $account['id'];

        // Sauvegarde immediate en BDD
        $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
        $updateStmt->execute([$stripe_account_id, $commande_id]);
    }

    // 2. Génération du lien d'onboarding avec le SDK classique
    $accountLink = \Stripe\AccountLink::create([
        'account' => $stripe_account_id,
        'refresh_url' => $refresh_url,
        'return_url' => $return_url,
        'type' => 'account_onboarding',
    ]);

    // Redirection vers Stripe
    header("Location: " . $accountLink->url);
    exit;


} catch (Exception $e) {
    die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
}
