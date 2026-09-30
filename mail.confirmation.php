<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
</head>
<body>
    <?php
        $postData = $_POST;

        // Validation des données transmises lors de la commande
        if (empty($postData['email']) || !filter_var($postData['email'], FILTER_VALIDATE_EMAIL)
            || empty($postData['nom']) || trim($postData['nom']) === ''
            || empty($postData['numero_commande'])) {
          
            echo '<section class="accueil">
                <h2>Informations de commande manquantes ou invalides.</h2><br>
                <div class="button"><a href="index.html" class="ancre">Retour</a></div>
            </section>';
            return;
        }

        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $numeroCommande = filter_input(INPUT_POST, 'numero_commande', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($nom && $email && $numeroCommande) {

            $entete = 'MIME-Version: 1.0' . "\r\n";
            $entete .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $entete .= 'From: benjaminlouis.eu <benlouisdevweb@gmail.com>' . "\r\n";
            $entete .= 'Reply-To: benlouisdevweb@gmail.com' . "\r\n";

            $sujet = 'Confirmation de votre commande n° ' . $numeroCommande;

            $message = '
            <h1>Merci pour votre commande, ' . $nom . ' !</h1>
            <p>Nous avons bien enregistré votre commande <b>n° ' . $numeroCommande . '</b>.</p>
            <p>Un e-mail vous sera envoyé dès que votre commande sera expédiée.</p>
            <br>
            <p>À bientôt sur benjaminlouis.eu !</p>';

            $retour = mail($email, $sujet, $message, $entete);

            if ($retour) {
                echo '<section class="accueil">
                    <h2>Votre commande n° ' . $numeroCommande . ' a été confirmée par e-mail.</h2><br>
                    <div class="button"><a href="index.html" class="ancre">Retour</a></div>
                </section>';
            } else {
                echo '<section class="accueil">
                    <h2>Une erreur est survenue.</h2><br>
                    <div class="button"><a href="index.html" class="ancre">Retour</a></div>
                </section>';
            }
        }
    ?>
</body>
</html>

 
