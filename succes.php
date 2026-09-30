<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

$session_id = $_GET['session_id'] ?? '';
$customer_email = '';
$client_nom = '';
$paiement_valide = false;

if (!empty($session_id)) {
    try {
        Stripe::setApiKey(STRIPE_SECRET_KEY);
        $session = Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {
            $paiement_valide = true;
            $customer_email = $session->customer_details->email ?? $session->metadata->client_email ?? '';
            $client_nom     = $session->metadata->client_nom ?? 'Client';

            // Mise à jour du statut en BDD si $pdo est configuré
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->prepare("UPDATE commandes SET statut = 'paye' WHERE client_email = :email AND statut = 'en_attente' ORDER BY id DESC LIMIT 1");
                    $stmt->execute([':email' => $customer_email]);
                } catch (\PDOException $e) {
                    error_log('Erreur BDD update succes : ' . $e->getMessage());
                }
            }
            // --- ENVOI DU MAIL DE CONFIRMATION ---
            if (!empty($customer_email)) {
                $entete  = 'MIME-Version: 1.0' . "\r\n";
                $entete .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $entete .= 'From: benjaminlouis.eu <benlouisdevweb@gmail.com>' . "\r\n";
                $entete .= 'Reply-To: benlouisdevweb@gmail.com' . "\r\n";

                $sujet = 'Confirmation de votre commande — benjaminlouis.eu';

                $message = '
                <h2>Merci pour votre commande, ' . htmlspecialchars($client_nom) . ' !</h2>
                <p>Votre paiement pour la <strong>Solution E-commerce Clé en Main</strong> a bien été validé.</p>
                <p>Je prends contact avec vous sous 24h ouvrées pour faire le point sur votre projet et démarrer la configuration.</p>
                <br>
                <p>Cordialement,<br>Benjamin Louis</p>';

                @mail($customer_email, $sujet, $message, $entete);
            } 
        }
    } catch (\Exception $e) {
        error_log('Erreur verification session Stripe : ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr" style="height: auto !important; overflow-y: auto !important; background-color: #f4f6f9 !important;">
<head>
    <meta charset="utf-8">
    <title>Commande Confirmée — Benjamin Louis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="height: auto !important; overflow-y: auto !important; background-color: #f4f6f9 !important; color: #1a202c !important; font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0;">

<div style="max-width: 600px; margin: 50px auto; padding: 20px; box-sizing: border-box;">
    <div style="background: #ffffff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center; border: 1px solid #e2e8f0;">
       
        <?php if ($paiement_valide): ?>
            <div style="color: #38a169; font-size: 3.5rem; margin-bottom: 15px;">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1 style="color: #1a202c; font-size: 1.8rem; margin: 0 0 15px 0;">Merci pour votre commande !</h1>

            <p style="color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 20px;">
                Votre paiement pour la <strong>Solution E-commerce Clé en Main</strong> a bien été validé.
            </p>

            <?php if (!empty($customer_email)): ?>
                <div style="background: #f7fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px; font-size: 0.95rem; color: #2d3748;">
                    Un e-mail de confirmation a été envoyé à <strong><?= htmlspecialchars($customer_email) ?></strong>.
                </div>
            <?php endif; ?>

            <p style="color: #718096; font-size: 0.95rem; line-height: 1.5; margin-bottom: 30px;">
                Je prends contact avec vous sous 24h ouvrées pour faire le point sur votre projet et démarrer la configuration.
            </p>
        <?php else: ?>
            <div style="color: #e53e3e; font-size: 3.5rem; margin-bottom: 15px;">
                <i class="fas fa-exclamation-circle"></i>
            </div>

            <h1 style="color: #1a202c; font-size: 1.8rem; margin: 0 0 15px 0;">Confirmation introuvable</h1>

            <p style="color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 30px;">
                Nous n'avons pas pu vérifier la validation de votre paiement. Si vous avez été débité, soyez rassuré : votre demande a bien été prise en compte.
            </p>
        <?php endif; ?>

        <a href="index.html" style="display: inline-block; padding: 12px 25px; background: #2b6cb0; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 6px;">
            Retour à l'accueil
        </a>
    </div>
</div>

</body>
</html>

 
