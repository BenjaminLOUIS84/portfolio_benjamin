<?php
// Activation de l'affichage des erreurs PHP pour le debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclusion directe des fichiers PHPMailer depuis le dossier vendor
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Mode debug pour afficher toutes les étapes de connexion SMTP
    $mail->SMTPDebug = 2;

    // Configuration SMTP o2switch
    $mail->isSMTP();
    $mail->Host       = 'mail.benjaminlouis.eu'; // Ou 'mahonia.o2switch.net'
    $mail->SMTPAuth   = true;
   
    // Vos identifiants e-mail o2switch
    $mail->Username   = 'adresse mail'; // Remplacez par votre adresse e-mail o2switch
    $mail->Password   = 'mot de passe'; // Remplacez par votre mot de passe cPanel
   
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;                        // Port SSL o2switch

    // Expéditeur et Destinataire
    $mail->setFrom($mail->Username, 'Test Boutique');
    $mail->addAddress($mail->Username); // Envoi d'un mail à soi-même

    // Contenu du message
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Subject = 'Test d\'envoi SMTP o2switch';
    $mail->Body    = '<b>Bravo !</b> Le serveur SMTP d\'o2switch et PHPMailer fonctionnent parfaitement.';

    $mail->send();
    echo "<br><h2 style='color:green;'>✅ MAIL ENVOYÉ AVEC SUCCÈS !</h2>";
} catch (Exception $e) {
    echo "<br><h2 style='color:red;'>❌ ERREUR D'ENVOI :</h2> " . $mail->ErrorInfo;
}

 
