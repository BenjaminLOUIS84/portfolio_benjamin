<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Envoi d'un message par formulaire</title>
</head>

<body>
    <?php

        // Pour filtrer les données et ainsi sécuriser le formulaire

        $message = valid_donnees($_POST["nom"]);
        $mail = valid_donnees($_POST["mail"]);
        $message = valid_donnees($_POST["message"]);

        function valid_donnees($donnees){
            $donnees = trim($donnees);              // Permet de supprimer les espaces inutiles
            $donnees = stripslashes($donnees);      // Permet de supprimer les antislashes
            $donnees = htmlspecialchars($donnees);  // Permet d'échapper les caractères spéciaux
            return $donnees;
        }
    
            
        if (isset($_POST['message']) 
           
            && preg_match("^[A-Za-z '-]+$",$nom)
            && preg_match("^[A-Za-z '-]+$",$message)
            && filter_var($mail, FILTER_VALIDATE_EMAIL)){
    
            $entete = 'MIME-Version:1.0' . "\r\n";
            $entete .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $entete .= 'Reply-to' . $_POST['email'];
    
            $message = '<h1>Message envoyé depuis la page Contact de benjaminlouis.eu</h1>
            <p><b>Email : </b>' . $_POST['email'] . '<br>
            <b>Message : </b>' . htmlspecialchars($_POST['message']) . '</p>';
    
            $retour = mail('benlouisdevweb@gmail.com', 'Envoi depuis la page Contact', $message, $entete);
            
            if ($retour)
                echo '<p>Votre message a bien été envoyé.</p>';
        }
    
    ?>
</body>
</html>
