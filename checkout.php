<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_nom = trim($_POST['client_nom'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $domaine_souhaite = trim($_POST['domaine_souhaite'] ?? '');

    if (empty($client_nom) || empty($client_email) || empty($domaine_souhaite)) {
        die('Veuillez remplir tous les champs obligatoires.');
    }

    // 1. Insertion optionnelle en base de données si $pdo existe
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO commandes (client_nom, client_email, domaine_souhaite, montant_ht, statut, date_creation) VALUES (:nom, :email, :domaine, 990.00, 'en_attente', NOW())");
            $stmt->execute([
                ':nom'     => $client_nom,
                ':email'   => $client_email,
                ':domaine' => $domaine_souhaite
            ]);
        } catch (\PDOException $e) {
            error_log('Erreur BDD checkout : ' . $e->getMessage());
        }
    }

    // 2. Création de la session Stripe Checkout
    try {
        Stripe::setApiKey(STRIPE_SECRET_KEY);

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Solution E-commerce Clé en Main',
                        'description' => 'Domaine : ' . $domaine_souhaite,
                    ],
                    'unit_amount' => 118800, // 990 € HT + 20% TVA = 1188 € TTC (en centimes)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'customer_email' => $client_email,
            'metadata' => [
                'client_nom' => $client_nom,
                'client_email' => $client_email,
                'domaine_souhaite' => $domaine_souhaite,
            ],
            'success_url' => 'https://benjaminlouis.eu/succes.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://benjaminlouis.eu/solution.php',
        ]);

        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit();

    } catch (\Exception $e) {
        error_log('Erreur Stripe Checkout : ' . $e->getMessage());
        echo "Une erreur s'est produite lors de la redirection vers le paiement : " . htmlspecialchars($e->getMessage());
    }
} else {
    header('Location: solution.php');
    exit();
}

 
