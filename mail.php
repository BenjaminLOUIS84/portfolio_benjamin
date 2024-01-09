<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoi d'un message par formulaire</title>
</head>

<body>
    <?php

        // Pour filtrer les données et ainsi sécuriser le formulaire
            
        
        $postData = $_POST;

        if (!isset($postData['email'])
            || !filter_var($postData['email'], FILTER_VALIDATE_EMAIL)
            || empty($postData['nom'])
            || trim($postData['nom']) === ''
            || empty($postData['message'])
            || trim($postData['message']) === ''){
           
            echo
            '<section class="accueil">
                <h2>Il faut un email, un nom et un message valides pour soumettre le formulaire.</h2><br>
                <div class="button">
                        <a href="index.html" class="ancre">Retour</a>
                </div>
            </section>';

            return;
        }

        if (isset($_POST['message'])){

            $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_VALIDATE_EMAIL);
               
            if($nom && $message && $email){

                $entete = 'MIME-Version:1.0' . "\r\n";
                $entete .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $entete .= 'Reply-to' . $email;
                
                $message = '<h1>Message envoyé depuis la page Contact de benjaminlouis.eu</h1>
                <p><b>Email : </b>' . $email . '<br>
                <b>Message : </b>' . htmlspecialchars($message) . '</p>';
                
                $retour = mail('benlouisdevweb@gmail.com', 'Envoi depuis la page Contact', $message, $entete);
                
                if ($retour)
                echo 
                '<section class="accueil">
                    <h2>Votre message a bien été envoyé.</h2><br>
                    <div class="button">
                    <a href="index.html" class="ancre">Retour</a>
                    </div>
                </section>';
                
            }else{
                echo
                '<section class="accueil">
                    <h2>Il faut un email, un nom et un message valides pour soumettre le formulaire.</h2><br>
                    <div class="button">
                            <a href="index.html" class="ancre">Retour</a>
                    </div>
                </section>';
            }

        }
    
    ?>
</body>
</html>
