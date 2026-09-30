<?php
require_once 'config.php';
// Vérifie que l'autoloader composer ou l'accès au SDK Stripe est bien chargé
require_once 'vendor/autoload.php';

use Stripe\Stripe;


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
}

// Récupère l'option (vaut true si la case est cochée)
$opt_multilingue = isset($_POST['opt_multilingue']) && $_POST['opt_multilingue'] == '1';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// 1. Récupération des champs POST du formulaire
$nom_client      = $_POST['nom_client'] ?? '';
$email_client    = $_POST['email'] ?? '';
$domaine         = $_POST['domaine'] ?? '';

// Calcul du tarif côté serveur (sécurité)
    $prix_base_ht = 990;
    $prix_option_ht = $opt_multilingue ? 290 : 0;
    $total_ht = $prix_base_ht + $prix_option_ht;

    // Montant total TTC en centimes pour Stripe
    $total_ttc_cents = round($total_ht * 1.20 * 100);

    // Description pour la facture Stripe
    $description = "Solution E-commerce Clé en Main" . ($opt_multilingue ? " + Option Multilingue (FR/EN)" : "");

    // Enregistrement dans les métadonnées Stripe et la BDD
    // (Permet d'activer automatiquement l'option lors du déploiement)

    
    
    
// 2. Définition des articles de la commande (Prix HT en centimes)
$line_items = [
    [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'Solution E-commerce Clé en Main',
                'description' => 'Domaine : ' . $domaine,
            ],
            'unit_amount' => 99000, // 990,00 € HT
        ],
        'quantity' => 1,
    ]
];

// 3. Ajout dynamique de l'option multilingue si cochée
if ($opt_multilingue) {
    $line_items[] = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'Option Pack International (FR/EN)',
                'description' => 'Configuration multilingue complète',
            ],
            'unit_amount' => 29000, // 290,00 € HT
        ],
        'quantity' => 1,
    ];
}

// 4. Création de la session Stripe Checkout
try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'customer_email'       => $client_email,
        'line_items'           => $line_items,
        'mode'                 => 'payment',
        'automatic_tax'        => ['enabled' => true], // Calcule les 20% de TVA
        'success_url'          => 'https://benjaminlouis.eu/merci.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'           => 'https://benjaminlouis.eu/solution.php',
        'metadata'             => [
            'client_nom'       => $client_nom,
            'client_email'       => $client_email,
            'domaine_souhaite'          => $domaine_souhaite,
            'opt_multilingue'  => $opt_multilingue ? 'Oui' : 'Non'
        ]
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $session->url);
    exit;

} catch (\Exception $e) {
    echo "Erreur lors de l'initialisation du paiement : " . $e->getMessage();
}
