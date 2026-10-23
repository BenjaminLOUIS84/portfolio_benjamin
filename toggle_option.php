<?php
session_start();
require_once __DIR__ . '/db_config.php';

// Optionnel : Ajouter la sécurité de session admin si nécessaire
// if (!isset($_SESSION['admin_logged'])) { header('Location: admin.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $current_opt = (int)($_POST['current_opt'] ?? 0);
   
    // Inverser l'état : si c'était 0 -> passe à 1, si c'était 1 -> passe à 0
    $new_opt = ($current_opt === 1) ? 0 : 1;

    $stmt = $bdd->prepare("UPDATE commandes SET option_multilingue = ? WHERE id = ?");
    $stmt->execute([$new_opt, $id]);
}

// Redirection directe vers le panneau admin
header('Location: admin.php');
exit;

 