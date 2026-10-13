<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     var_dump($_POST);
//     die();
// }
////////////////////////////////////////////////////////CONFIGURATION DE BASE POUR CREER UN COMPTE CONNECTE
// require_once __DIR__ . '/db_config.php';
// require_once __DIR__ . '/config.php';
// require_once __DIR__ . '/vendor/autoload.php'; // Charge la bibliothèque stripe-php

// // Récupération de l'ID de commande depuis l'URL
// $commande_id = (int)($_GET['commande_id'] ?? 0);

// if ($commande_id <= 0) {
//     die("Commande invalide.");
// }

// try {
//     // Connexion BDD
//     $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
//         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
//     ]);

//     // Récupération des infos du prospect/boutique
//     $stmt = $bdd->prepare("SELECT * FROM prospects_configurations WHERE commande_id = ?");
//     $stmt->execute([$commande_id]);
//     $prospect = $stmt->fetch();

//     if (!$prospect) {
//         die("Configuration de boutique introuvable.");
//     }

//     // Définition de ta clé secrète Stripe (Mode Test)
//     // Remplace par ta vraie clé secrète sk_test_... ou stocke-la dans config.php
//     \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
//     \Stripe\Stripe::setApiVersion('2024-06-20');

//     $stripe_account_id = $prospect['stripe_account_id'] ?? null;

// // 1. Si le marchand n'a pas encore de compte associé, on en crée un via l'API v2 (cURL)
//     if (empty($stripe_account_id)) {
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v2/core/accounts');
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//         curl_setopt($ch, CURLOPT_POST, 1);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
//             'contact_email' => $prospect['email_admin'] ?? null
//         ]));
//         curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
//         curl_setopt($ch, CURLOPT_HTTPHEADER, [
//             'Content-Type: application/json',
//             'Stripe-Version: 2025-04-30.preview'
//         ]);

//         $result = curl_exec($ch);
//         curl_close($ch);

//         $account = json_decode($result, true);

//         if (isset($account['error'])) {
//             die('Erreur Stripe API v2 : ' . ($account['error']['message'] ?? json_encode($account['error'])));
//         }

//         $stripe_account_id = $account['id'];

//         // Sauvegarde immédiate en BDD
//         $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
//         $updateStmt->execute([$stripe_account_id, $commande_id]);
//     }


//     // 2. Génération du lien d'onboarding (Account Link) pour rediriger le marchand sur Stripe
//     $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//     $domainName = $_SERVER['HTTP_HOST'];

//     $refresh_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id;
//     $return_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id . "&stripe_success=1";

//     $accountLink = \Stripe\AccountLink::create([
//         'account' => $stripe_account_id,
//         'refresh_url' => $refresh_url,
//         'return_url' => $return_url,
//         'type' => 'account_onboarding',
//     ]);

//     // 3. Redirection vers l'interface Stripe Connect
//     header("Location: " . $accountLink->url);
//     exit();

 

// } catch (Exception $e) {
//     die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
// }
//////////////////////////////////////////////////////////////////////////////////////////PB POUR CREER LE LIENS ONBOARDING



///////////////////////////////////////////////////////////////////////////////////////////////// PROPOSITION PAR IA STRIPE

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

$commande_id = (int)($_POST['commande_id'] ?? $_GET['commande_id'] ?? 0);

if ($commande_id <= 0) {
    die("Commande invalide.");
}

try {
    // Connexion BDD
    $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stripe_token = $_POST['stripe_token'] ?? null;

    if (empty($stripe_token)) {
        die("Token Stripe manquant.");
    }

    // Fonction utilitaire pour les appels cURL v2
    function stripeV2Request($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
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
    $domainName = $_SERVER['HTTP_HOST'];
    $refresh_url = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id;
    $return_url  = $protocol . $domainName . "/config-boutique.php?id=" . $commande_id . "&stripe_success=1";

    // Fonction utilitaire pour les appels cURL v2
    // function stripeV2Request($url, $data) {
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    //     curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Content-Type: application/json',
    //         'Stripe-Version: 2025-04-30.preview'
    //     ]);
    //     $result = curl_exec($ch);
    //     curl_close($ch);
    //     return json_decode($result, true);
    // }

    // // 1. Création du compte connecté v2 si inexistant
    // if (empty($stripe_account_id)) {
    //     $account = stripeV2Request('https://api.stripe.com/v2/core/accounts', [
    //         'contact_email' => $prospect['email_admin'] ?? null,
    //         'configuration' => [
    //             'merchant' => new \stdClass()
    //         ]
    //     ]);

    // 1. Création du token de compte (obligatoire pour les plateformes FR)
    function stripeV1Request($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2025-04-30.preview'
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    // if (empty($stripe_account_id)) {
    //     // Étape 1 : Créer un token de compte
    //     $token = stripeV1Request('https://api.stripe.com/v1/tokens', [
    //         'account[tos_shown_and_accepted]' => 'true',
    //     ]);

    //     if (isset($account['error'])) {
    //         die('Erreur création compte Stripe : ' . ($account['error']['message'] ?? json_encode($account['error'])));
    //     }

    //     $stripe_account_id = $account['id'];

    //     // Sauvegarde en BDD
    //     $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
    //     $updateStmt->execute([$stripe_account_id, $commande_id]);
    // }

    if (empty($stripe_account_id)) {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
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

    // 2. Création du lien d'onboarding v2
    $link = stripeV2Request('https://api.stripe.com/v2/core/account_links', [
        'account' => $stripe_account_id,
        'use_case' => ['type' => 'account_onboarding'],
        'refresh_url' => $refresh_url,
        'return_url'  => $return_url,
    ]);

    if (isset($link['error'])) {
        die('Erreur création lien onboarding : ' . ($link['error']['message'] ?? json_encode($link['error'])));
    }

    // 3. Redirection vers l'interface Stripe Connect
    header("Location: " . $link['url']);
    exit();

} catch (Exception $e) {
    die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// require_once __DIR__ . '/db_config.php';
// require_once __DIR__ . '/config.php';
// require_once __DIR__ . '/vendor/autoload.php'; // Charge la bibliothèque stripe-php

// // Récupération de l'ID de commande depuis l'URL
// $commande_id = (int)($_GET['commande_id'] ?? 0);

// if ($commande_id <= 0) {
//     die("Commande invalide.");
// }

// try {
//     // Connexion BDD
//     $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
//         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
//     ]);

//     // Récupération des infos du prospect/boutique
//     $stmt = $bdd->prepare("SELECT * FROM prospects_configurations WHERE commande_id = ?");
//     $stmt->execute([$commande_id]);
//     $prospect = $stmt->fetch();

//     if (!$prospect) {
//         die("Configuration de boutique introuvable.");
//     }

//     // Définition de ta clé secrète Stripe (Mode Test)
//     // Remplace par ta vraie clé secrète sk_test_... ou stocke-la dans config.php
//     \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
//     // \Stripe\Stripe::setApiVersion('2023-10-16');

//     $stripe_account_id = $prospect['stripe_account_id'] ?? null;


//     if (empty($stripe_account_id)) {
//         // 1. Création du compte v2
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v2/core/accounts');
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//         curl_setopt($ch, CURLOPT_POST, 1);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
//             'contact_email' => $prospect['email_admin'] ?? null,
//             'configuration' => [
//                 'merchant' => new \stdClass()
//             ]
//         ]));
//         curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
//         curl_setopt($ch, CURLOPT_HTTPHEADER, [
//             'Content-Type: application/json',
//             'Stripe-Version: 2025-04-30.preview'
//         ]);
//         $result = curl_exec($ch);
//         curl_close($ch);
//         $account = json_decode($result, true);
//         $stripe_account_id = $account['id'];

//         // Sauvegarde en BDD
//         $updateStmt = $bdd->prepare("UPDATE prospects_configurations SET stripe_account_id = ? WHERE commande_id = ?");
//         $updateStmt->execute([$stripe_account_id, $commande_id]);
//     }

//     // 2. Création du lien d'onboarding v2
//     $ch2 = curl_init();
//     curl_setopt($ch2, CURLOPT_URL, 'https://api.stripe.com/v2/core/account_links');
//     curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
//     curl_setopt($ch2, CURLOPT_POST, 1);
//     curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
//         'account' => $stripe_account_id,
//         'use_case' => ['type' => 'account_onboarding'],
//         'refresh_url' => $refresh_url,
//         'return_url' => $return_url,
//     ]));
//     curl_setopt($ch2, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
//     curl_setopt($ch2, CURLOPT_HTTPHEADER, [
//         'Content-Type: application/json',
//         'Stripe-Version: 2025-04-30.preview'
//     ]);
//     $result2 = curl_exec($ch2);
//     curl_close($ch2);
//     $link = json_decode($result2, true);

//     header("Location: " . $link['url']);
//     exit();

// } catch (Exception $e) {
//     die("Erreur Stripe Connect : " . htmlspecialchars($e->getMessage()));
// }