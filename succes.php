<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (file_exists(__DIR__ . '/facture.php')) {
    require_once __DIR__ . '/facture.php';
}

use Stripe\Stripe;
use Stripe\Checkout\Session;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

$session_id = $_GET['session_id'] ?? '';
$customer_email = '';
$client_nom = '';
$paiement_valide = false;
$commande = null;

if (!empty($session_id)) {
    try {
        Stripe::setApiKey(STRIPE_SECRET_KEY);
        $session = Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {
            $paiement_valide = true;
            $customer_email = $session->customer_details->email ?? $session->metadata->client_email ?? '';
            $client_nom     = $session->metadata->client_nom ?? 'Client';
            $domaine_souhaite     = $session->metadata->domaine_souhaite ?? '';

            // Connexion BDD ($bdd, $pdo ou $db)
            $connexion = $bdd ?? $pdo ?? $db ?? null;

            if ($connexion && !empty($customer_email)) {
                try {
                    // 1. Mise à jour du statut en BDD
                    $stmt_update = $connexion->prepare("UPDATE commandes SET statut = 'paye' WHERE client_email = :email AND statut = 'en_attente' ORDER BY id DESC LIMIT 1");
                    $stmt_update->execute([':email' => $customer_email]);

                    // 2. Récupération des données de la commande
                    $stmt_get = $connexion->prepare("SELECT * FROM commandes WHERE client_email = :email ORDER BY id DESC LIMIT 1");
                    $stmt_get->execute([':email' => $customer_email]);
                    $commande = $stmt_get->fetch(PDO::FETCH_ASSOC);
                } catch (\PDOException $e) {
                    error_log('Erreur BDD succes.php : ' . $e->getMessage());
                }
            }

            // --- ENVOI DU MAIL DE CONFIRMATION AVEC FACTURE PDF VIA PHPMAILER ---
            if (!empty($customer_email)) {
                $mail = new PHPMailer(true);

                try {

                    // Configuration SMTP
                    $mail->isSMTP();
                    $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'mail.benjaminlouis.eu';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = defined('SMTP_USER') ? SMTP_USER : 'contact@benjaminlouis.eu';
                    $mail->Password   = defined('SMTP_PASS') ? SMTP_PASS : 'TON_MOT_DE_PASSE';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ou ENCRYPTION_STARTTLS
                    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 465; // ou 587

                    // Informations d'expédition
                    $mail->CharSet = 'UTF-8';
                    // On utilise l'adresse pro créer sur O2Swich
                    $mail->setFrom('contact@benjaminlouis.eu', 'SARL Louis');
                    $mail->addReplyTo('benlouisdevweb@gmail.com', 'SARL Louis'); // Les réponses arriveront toujours sur ton Gmail
                    $mail->addAddress($customer_email, $client_nom);
                    $mail->addReplyTo('benlouisdevweb@gmail.com', 'SARL Louis');

                    $lien_facture = '';
                    $pdf_content = null;

                    // Si la commande existe, on génère le PDF et le lien
                    if ($commande && function_exists('genererHtmlFacture')) {
                        $options = new Options();
                        $options->set('isRemoteEnabled', true);
                        $dompdf = new Dompdf($options);

                        $dompdf->loadHtml(genererHtmlFacture($commande));
                        $dompdf->setPaper('A4', 'portrait');
                        $dompdf->render();

                        $pdf_content = $dompdf->output();
                        $num_fac = 'FAC-' . str_pad($commande['id'], 5, '0', STR_PAD_LEFT);

                        // $lien_facture = "https://benjaminlouis.eu/facture.php?id=" . $commande['id'];

                        // Pièce jointe PDF
                        $mail->addStringAttachment($pdf_content, "Facture_{$num_fac}.pdf", 'base64', 'application/pdf');
                    }

                    $mail->isHTML(true);
                    $mail->Subject = 'Confirmation de votre commande — SARL Louis';

                    $body = '<h2>Merci pour votre commande, ' . htmlspecialchars($client_nom) . ' !</h2>';
                    $body .= '<p>Votre paiement pour la <strong>Solution E-commerce Clé en Main</strong> a bien été validé avec succès.</p>';

                    if (!empty($lien_facture)) {
                        $body .= '<p>Votre facture acquittée est disponible en <strong>pièce jointe</strong> à cet e-mail.</p>';
                        // $body .= '<p>Vous pouvez également la télécharger à tout moment via ce lien :<br>';
                        // $body .= '<a href="' . $lien_facture . '" target="_blank">' . $lien_facture . '</a></p>';
                    }

                    $body .= '<p>Je prends contact avec vous sous 24h ouvrées pour faire le point sur votre projet et démarrer la configuration.</p>';
                    $body .= '<br><p>Cordialement,<br><strong>SARL Louis</strong><br>Benjamin Louis</p>';

                    $mail->Body = $body;
                    
                    // Activer ou Désactiver le débogage SMTP (Affiche tout à l'écran)
                    // $mail->SMTPDebug = 2;

                    $mail->send();

                } catch (\Exception $e) {
                    echo "<div style='background:#fee2e2;color:#b91c1c;padding:15px;margin:20px;'>";
                    echo "<strong>Erreur d'envoi mail :</strong> " . htmlspecialchars($mail->ErrorInfo) . "<br>";
                    echo "<strong>Exception :</strong> " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
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
    <title>Commande Confirmée — SARL Louis</title>
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
                <div style="background: #f7fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; font-size: 0.95rem; color: #2d3748;">
                    Un e-mail de confirmation avec votre facture en pièce jointe a été envoyé à <strong><?= htmlspecialchars($customer_email) ?></strong>.
                </div>
            <?php endif; ?>

            <?php if ($commande): ?>
                <div style="margin-bottom: 25px;">
                    <a href="facture.php?id=<?= $commande['id'] ?>" target="_blank" style="display: inline-block; padding: 10px 20px; background: #38a169; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 6px; font-size: 0.95rem;">
                        📄 Télécharger votre facture PDF
                    </a>
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

 