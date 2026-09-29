<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

// 1. Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: solution.php');
    exit;
}

// 2. Nettoyage et récupération des champs
$client_nom      = trim($_POST['client_nom'] ?? '');
$client_email    = filter_var(trim($_POST['client_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$domaine_souhaite = trim($_POST['domaine_souhaite'] ?? '');
$cgv_acceptees   = isset($_POST['cgv_acceptees']);

// 3.1 Contrôle des données obligatoires
if (empty($client_nom) || !$client_email || !$cgv_acceptees) {
    die('Erreur : Veuillez remplir tous les champs obligatoires et accepter les CGV.');
}

//3.2 Enregistrement de la commande en BDD avant la redirection Stripe
try {
    $stmt = $pdo->prepare("INSERT INTO commandes (client_nom, client_email, domaine_souhaite, montant_ht, statut, date_creation) VALUES (:nom, :email, :domaine, 990.00, 'en_attente', NOW())");
    $stmt->execute([
        ':nom'     => $client_nom,
        ':email'   => $client_email,
        ':domaine' => $domaine_souhaite
    ]);
    $commande_id = $pdo->lastInsertId();
} catch (\PDOException $e) {
    error_log('Erreur BDD checkout : ' . $e->getMessage());
}

// 5. Configuration de Stripe
Stripe::setApiKey(STRIPE_SECRET_KEY);

 



// 4. Configuration de Stripe
Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Calcul du montant TTC (990 € HT + 20% TVA = 1188 € TTC -> transmis en centimes à Stripe)
    $montant_ttc_centimes = 118800;

    $checkout_session = Session::create([
        'payment_method_types' => ['card'],
        'customer_email'       => $client_email,
        'line_items' => [[
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name'        => 'Solution E-commerce Clé en Main',
                    'description' => 'Déploiement complet de votre boutique en ligne (Domaine souhaité : ' . ($domaine_souhaite ?: 'Non renseigné') . ')',
                ],
                'unit_amount' => $montant_ttc_centimes,
            ],
            'quantity' => 1,
        ]],
        'mode'        => 'payment',
        'success_url' => 'https://benjaminlouis.eu/succes.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'https://benjaminlouis.eu/solution.php',
        'metadata'    => [
            'client_nom'       => $client_nom,
            'client_email'     => $client_email,
            'domaine_souhaite' => $domaine_souhaite,
        ],
    ]);

    // Redirection vers la page de paiement Stripe
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit;

} catch (\Exception $e) {
    error_log('Erreur Stripe Checkout : ' . $e->getMessage());
    die('Une erreur est survenue lors de l\'initialisation du paiement. Veuillez réessayer.');
}
