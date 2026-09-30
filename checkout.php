<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: solution.php');
    exit;
}

// 1. Récupération des données POST
$client_nom       = trim($_POST['client_nom'] ?? '');
$client_email     = trim($_POST['client_email'] ?? '');
$domaine_souhaite = trim($_POST['domaine_souhaite'] ?? '');
$opt_multilingue  = isset($_POST['opt_multilingue']) && $_POST['opt_multilingue'] == '1';

if (empty($client_nom) || empty($client_email) || empty($domaine_souhaite)) {
    die('Veuillez remplir tous les champs obligatoires.');
}

// 2. Calcul du tarif HT dynamique
$prix_base_ht   = 990.00;
$prix_option_ht = $opt_multilingue ? 290.00 : 0.00;
$total_ht       = $prix_base_ht + $prix_option_ht;

// 3. Insertion en Base de Données
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO commandes
            (client_nom, client_email, domaine_souhaite, option_multilingue, montant_ht, statut, date_commande)
            VALUES (:nom, :email, :domaine, :opt, :montant, 'en_attente', NOW())");
       
        $stmt->execute([
            ':nom'     => $client_nom,
            ':email'   => $client_email,
            ':domaine' => $domaine_souhaite,
            ':opt'     => $opt_multilingue ? 1 : 0,
            ':montant' => $total_ht
        ]);
    } catch (\PDOException $e) {
        error_log('Erreur BDD checkout : ' . $e->getMessage());
    }
}

// 4. Configuration Stripe Checkout
Stripe::setApiKey(STRIPE_SECRET_KEY);

$line_items = [
    [
        'price_data' => [
            'currency'     => 'eur',
            'product_data' => [
                'name'        => 'Solution E-commerce Clé en Main',
                'description' => 'Domaine : ' . $domaine_souhaite,
            ],
            'unit_amount'  => 99000, // 990,00 € HT
        ],
        'quantity'   => 1,
    ]
];

if ($opt_multilingue) {
    $line_items[] = [
        'price_data' => [
            'currency'     => 'eur',
            'product_data' => [
                'name'        => 'Option Pack International (FR/EN)',
                'description' => 'Configuration multilingue complète',
            ],
            'unit_amount'  => 29000, // 290,00 € HT
        ],
        'quantity'   => 1,
    ];
}

try {
    $session = Session::create([
        'payment_method_types'       => ['card'],
        'customer_email'             => $client_email,
        'line_items'                 => $line_items,
        'mode'                       => 'payment',
        'automatic_tax'              => ['enabled' => true],
        'billing_address_collection' => 'required', // Requis pour calcul automatique de la TVA par pays
        'success_url'                => 'https://benjaminlouis.eu/merci.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'                 => 'https://benjaminlouis.eu/solution.php',
        'metadata'                   => [
            'client_nom'       => $client_nom,
            'client_email'     => $client_email,
            'domaine_souhaite' => $domaine_souhaite,
            'opt_multilingue'  => $opt_multilingue ? 'Oui' : 'Non'
        ]
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $session->url);
    exit;

} catch (\Exception $e) {
    echo "Erreur lors de l'initialisation du paiement : " . $e->getMessage();
}

 
