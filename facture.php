<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
* Génère le code HTML complet et légal pour la facture SARL Louis
*/
function genererHtmlFacture(array $commande): string {
    $date = date('d/m/Y', strtotime($commande['created_at'] ?? $commande['date_creation'] ?? 'now'));
    $numero = 'FAC-' . str_pad($commande['id'] ?? 1, 5, '0', STR_PAD_LEFT);
    $client_nom = htmlspecialchars($commande['client_nom'] ?? 'Client');
    $client_email = htmlspecialchars($commande['client_email'] ?? '');

    // Calculs HT / TVA 20% / TTC
    $montant_ttc = $commande['montant'] ?? 0;
    $montant_ht = $montant_ttc / 1.20;
    $tva = $montant_ttc - $montant_ht;

    $str_ht = number_format($montant_ht, 2, ',', ' ');
    $str_tva = number_format($tva, 2, ',', ' ');
    $str_ttc = number_format($montant_ttc, 2, ',', ' ');

    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #2d3748; margin: 0; padding: 0; }
            .header { width: 100%; border-bottom: 2px solid #2b6cb0; padding-bottom: 15px; margin-bottom: 25px; }
            .company { float: left; width: 55%; }
            .company h2 { margin: 0 0 5px 0; color: #2b6cb0; font-size: 18px; }
            .company p { margin: 2px 0; color: #4a5568; line-height: 1.4; }
            .invoice-details { float: right; width: 40%; text-align: right; }
            .invoice-details h1 { margin: 0 0 5px 0; font-size: 22px; color: #1a202c; }
            .clear { clear: both; }
            .client { background: #f7fafc; padding: 12px 15px; border-radius: 6px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background: #2b6cb0; color: #ffffff; padding: 8px 10px; text-align: left; font-size: 12px; }
            td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
            .totals-table { width: 45%; float: right; border-collapse: collapse; margin-top: 10px; }
            .totals-table td { padding: 5px 10px; border: none; text-align: right; }
            .totals-table .grand-total { font-weight: bold; font-size: 15px; border-top: 2px solid #2b6cb0; color: #1a202c; }
            .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='company'>
                <h2>SARL Louis</h2>
                <p>
                    <strong>Benjamin Louis</strong><br>
                    123 Rue de votre Adresse<br>
                    75000 Paris<br>
                    E-mail : benlouisdevweb@gmail.com<br>
                    SIRET : 000 000 000 00000<br>
                    N° TVA Intracommunautaire : FR 00 000000000
                </p>
            </div>
            <div class='invoice-details'>
                <h1>FACTURE ACQUITTÉE</h1>
                <p><strong>N° :</strong> {$numero}<br><strong>Date :</strong> {$date}</p>
            </div>
            <div class='clear'></div>
        </div>

        <div class='client'>
            <strong>Facturé à :</strong><br>
            {$client_nom}<br>
            {$client_email}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th style='text-align: right;'>Prix HT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Solution E-commerce Clé en Main</td>
                    <td style='text-align: right;'>{$str_ht} €</td>
                </tr>
            </tbody>
        </table>

        <table class='totals-table'>
            <tr>
                <td>Total HT :</td>
                <td>{$str_ht} €</td>
            </tr>
            <tr>
                <td>TVA (20 %) :</td>
                <td>{$str_tva} €</td>
            </tr>
            <tr class='grand-total'>
                <td>Total TTC réglé :</td>
                <td>{$str_ttc} €</td>
            </tr>
        </table>
        <div class='clear'></div>

        <div class='footer'>
            SARL Louis — Capital social de X XXX € — SIRET 000 000 000 00000 — RCS Paris<br>
            Facture payée en totalité. Merci pour votre confiance !
        </div>
    </body>
    </html>";
}

// Téléchargement / Affichage direct via URL (espace Admin)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $commande_id = $_GET['id'] ?? null;

    if (!$commande_id || !isset($bdd)) {
        die('Accès refusé ou commande introuvable.');
    }

    $stmt = $bdd->prepare("SELECT * FROM commandes WHERE id = :id");
    $stmt->execute([':id' => $commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        die('Commande introuvable.');
    }

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml(genererHtmlFacture($commande));
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $num_fac = 'FAC-' . str_pad($commande['id'], 5, '0', STR_PAD_LEFT);
    $dompdf->stream("Facture_{$num_fac}.pdf", ["Attachment" => false]);
}

// Traitement lors de l'accès direct via l'URL (facture.php?id=X)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $commande_id = $_GET['id'] ?? null;

    if (!$commande_id) {
        die('ID de commande manquant.');
    }

    // Inclusions nécessaires si elles ne sont pas déjà faites en haut du fichier
    require_once __DIR__ . '/config.php';

    // On identifie la variable de connexion PDO (gère $pdo, $db ou $bdd)
    $connexion = $pdo ?? $db ?? $bdd ?? null;

    if (!$connexion) {
        die('Erreur : Connexion à la base de données introuvable.');
    }

    $stmt = $connexion->prepare("SELECT * FROM commandes WHERE id = :id");
    $stmt->execute([':id' => $commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        die('Commande introuvable en base de données.');
    }

    // Génération du PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml(genererHtmlFacture($commande));
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $num_fac = 'FAC-' . str_pad($commande['id'], 5, '0', STR_PAD_LEFT);
    $dompdf->stream("Facture_{$num_fac}.pdf", ["Attachment" => false]);
}

 