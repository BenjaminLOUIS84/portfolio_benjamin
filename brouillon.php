<?php
$lang = $_GET['lang'] ?? 'fr';
$lang = in_array($lang, ['fr', 'en']) ? $lang : 'fr';

$texts = [
    'fr' => [
        'title' => 'Conditions Générales de Vente — Benjamin Louis',
        'h1' => 'Conditions Générales de Vente (CGV)',
        'switch_lang' => 'English version',
        'switch_link' => '?lang=en',
        's1_title' => '1. Objet et champ d\'application',
        's1_text' => 'Les présentes CGV régissent les ventes de prestations de services de création et configuration technique de sites e-commerce réalisées par Benjamin Louis (EI) auprès de clients professionnels (B2B). Toute commande implique l\'acceptation sans réserve des présentes conditions.',
        's2_title' => '2. Prestations et Options',
        's2_text' => 'La prestation de base comprend la livraison d\'une solution e-commerce clé en main en version mono-langue (français) au tarif de 990 € HT (1 188 € TTC). Des options complémentaires, notamment la configuration multilingue, peuvent être souscrites lors de la commande ou ultérieurement.',
        's3_title' => '3. Tarifs et Modalités de paiement',
        's3_text' => 'Les prix sont indiqués en Euros HT et TTC. Le paiement s\'effectue au comptant lors de la commande par carte bancaire via la plateforme sécurisée Stripe. L\'exécution de la prestation débute dès la validation du paiement.',
        's4_title' => '4. Droit de rétractation et Exécution immédiate',
        's4_text' => 'Conformément aux dispositions du Code de la consommation applicables entre professionnels (B2B), et compte tenu du démarrage immédiat de la prestation à la demande expresse du client lors de la validation du formulaire, le client renonce expressément à son droit de rétractation.',
        's5_title' => '5. Obligations du client et Livraison',
        's5_text' => 'Le client s\'engage à fournir l\'ensemble des éléments nécessaires à la réalisation du projet (textes, logos, visuels, accès nom de domaine) dans un délai raisonnable. Benjamin Louis s\'engage à contacter le client sous 24h ouvrées suivant la commande pour initier le déploiement.',
        's6_title' => '6. Responsabilité et Propriété',
        's6_text' => 'Benjamin Louis est tenu à une obligation de moyens. La propriété des livrables et des accès est transférée au client à compter du paiement intégral de la commande.',
        's7_title' => '7. Droit applicable et Juridiction',
        's7_text' => 'Les présentes CGV sont soumises au droit français. Tout litige relatif à leur interprétation ou leur exécution sera de la compétence exclusive des tribunaux du siège social de l\'éditeur.',
        'back' => '← Retour au site'
    ],
    'en' => [
        'title' => 'General Terms of Sale — Benjamin Louis',
        'h1' => 'General Terms of Sale (CGV)',
        'switch_lang' => 'Version française',
        'switch_link' => '?lang=fr',
        's1_title' => '1. Purpose and Scope',
        's1_text' => 'These General Terms govern the sale of e-commerce creation and technical setup services provided by Benjamin Louis (EI) to professional clients (B2B). Ordering implies unreserved acceptance of these terms.',
        's2_title' => '2. Services and Options',
        's2_text' => 'The base service includes the delivery of a turn-key single-language (French) e-commerce solution at €990 excl. VAT (€1,188 incl. VAT). Additional options, including multi-language configuration, can be purchased during checkout or later.',
        's3_title' => '3. Pricing and Payment Terms',
        's3_text' => 'Prices are stated in Euros (excl. and incl. VAT). Payment is due in full at checkout via credit card through the secure Stripe platform. Service execution starts immediately upon payment validation.',
        's4_title' => '4. Right of Withdrawal and Immediate Execution',
        's4_text' => 'In accordance with consumer and commercial laws applicable to B2B transactions, and given that service execution begins immediately upon the client\'s express request at checkout, the client expressly waives any right of withdrawal.',
        's5_title' => '5. Client Obligations and Delivery',
        's5_text' => 'The client agrees to provide all necessary assets (texts, logos, images, domain access) in a timely manner. Benjamin Louis commits to contacting the client within 24 business hours following the order to initiate deployment.',
        's6_title' => '6. Liability and Ownership',
        's6_text' => 'Benjamin Louis operates under an obligation of means. Ownership of deliverables and credentials is transferred to the client upon full payment.',
        's7_title' => '7. Applicable Law and Jurisdiction',
        's7_text' => 'These terms are governed by French law. Any dispute shall fall under the exclusive jurisdiction of the competent courts of the publisher\'s registered address.',
        'back' => '← Back to website'
    ]
];

$t = $texts[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #2d3748; max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .lang-switch { text-align: right; margin-bottom: 20px; }
        .lang-switch a { background: #edf2f7; padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; font-weight: bold; color: #2b6cb0; text-decoration: none; }
        h1 { color: #1a202c; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        h2 { color: #2b6cb0; margin-top: 30px; }
        a { color: #2b6cb0; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="lang-switch">
        <a href="<?= $t['switch_link'] ?>"><?= $t['switch_lang'] ?></a>
    </div>

    <h1><?= $t['h1'] ?></h1>

    <h2><?= $t['s1_title'] ?></h2>
    <p><?= $t['s1_text'] ?></p>

    <h2><?= $t['s2_title'] ?></h2>
    <p><?= $t['s2_text'] ?></p>

    <h2><?= $t['s3_title'] ?></h2>
    <p><?= $t['s3_text'] ?></p>

    <h2><?= $t['s4_title'] ?></h2>
    <p><?= $t['s4_text'] ?></p>

    <h2><?= $t['s5_title'] ?></h2>
    <p><?= $t['s5_text'] ?></p>

    <h2><?= $t['s6_title'] ?></h2>
    <p><?= $t['s6_text'] ?></p>

    <h2><?= $t['s7_title'] ?></h2>
    <p><?= $t['s7_text'] ?></p>

    <p style="margin-top: 40px;"><a href="solution.php"><?= $t['back'] ?></a></p>

</body>
</html>