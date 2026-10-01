<?php
// 1. Connexion à la base de données
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/config.php';

try {
    $bdd = new PDO('mysql:host=localhost;dbname=' . $dbname . ';charset=utf8mb4', $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('Erreur de connexion à la base de données.');
}

$token = trim($_GET['token'] ?? '');
$erreur = '';
$succes = false;

// 2. Vérification de la présence du token
if (empty($token)) {
    die("Jeton de configuration manquant ou invalide.");
}

// 3. Traitement du formulaire à la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et désinfection des entrées (Anti-XSS & Injection)
    $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise'] ?? ''), ENT_QUOTES, 'UTF-8');
    $siret          = preg_replace('/\s+/', '', $_POST['siret'] ?? ''); // Supprime tous les espaces
    $adresse        = htmlspecialchars(trim($_POST['adresse_postale'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email_admin    = filter_var(trim($_POST['email_admin'] ?? ''), FILTER_SANITIZE_EMAIL);
    $pass           = $_POST['mot_de_passe'] ?? '';
    $pass_confirm   = $_POST['mot_de_passe_confirm'] ?? '';
    $commande_id    = (int)($_POST['commande_id'] ?? 0);

    // Validations strictes des données
    if (empty($nom_entreprise) || empty($siret) || empty($adresse) || empty($email_admin) || empty($pass)) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email_admin, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail renseignée n'est pas valide.";
    } elseif (!ctype_digit($siret) || strlen($siret) !== 14) {
        $erreur = "Le numéro SIRET doit contenir exactement 14 chiffres.";
    } elseif ($pass !== $pass_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($pass) < 10) {
        $erreur = "Le mot de passe doit faire au moins 10 caractères pour des raisons de sécurité.";
    } else {
        // Hashage sécurisé du mot de passe
        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);

        // Insertion sécurisée en BDD via requête préparée
        $stmt = $bdd->prepare("
            INSERT INTO prospects_configurations
            (commande_id, token, nom_entreprise, siret, adresse_postale, email_admin, mot_de_passe_hash, statut_deploiement)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
            ON DUPLICATE KEY UPDATE
                nom_entreprise = VALUES(nom_entreprise),
                siret = VALUES(siret),
                adresse_postale = VALUES(adresse_postale),
                email_admin = VALUES(email_admin),
                mot_de_passe_hash = VALUES(mot_de_passe_hash)
        ");
        $stmt->execute([$commande_id, $token, $nom_entreprise, $siret, $adresse, $email_admin, $pass_hash]);

        $succes = true;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de votre boutique</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; padding: 20px; margin: 0; }
        .card { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h1 { margin-top: 0; font-size: 1.5rem; color: #0f172a; }
        h3 { margin-top: 20px; font-size: 1.1rem; color: #334155; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"], textarea {
            width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; box-sizing: border-box;
        }
        input:focus, textarea:focus { border-color: #2563eb; outline: none; }
        button { background: #2563eb; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: bold; width: 100%; }
        button:hover { background: #1d4ed8; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.95rem; }
        .alert-success { background: #dcfce7; color: #166534; padding: 20px; border-radius: 6px; text-align: center; }
    </style>
</head>
<body>

<div class="card">
    <h1>Configuration de votre boutique</h1>

    <?php if ($succes): ?>
        <div class="alert-success">
            <h3>🎉 Informations enregistrées avec succès !</h3>
            <p>Nous préparons le déploiement de votre boutique. Vous recevrez un e-mail dès qu'elle sera prête à l'emploi.</p>
        </div>
    <?php else: ?>

        <?php if (!empty($erreur)): ?>
            <div class="alert-error"><?= $erreur ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="commande_id" value="1">

            <div class="form-group">
                <label for="nom_entreprise">Nom de l'entreprise *</label>
                <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?= htmlspecialchars($_POST['nom_entreprise'] ?? '') ?>" required placeholder="Ex: Ma Société SAS">
            </div>

            <div class="form-group">
                <label for="siret">Numéro SIRET (14 chiffres) *</label>
                <input type="text" id="siret" name="siret" value="<?= htmlspecialchars($_POST['siret'] ?? '') ?>" maxlength="17" required placeholder="Ex: 123 456 789 00012">
            </div>

            <div class="form-group">
                <label for="adresse_postale">Adresse du siège social *</label>
                <textarea id="adresse_postale" name="adresse_postale" rows="3" required placeholder="Ex: 10 Rue du Commerce, 75001 Paris"><?= htmlspecialchars($_POST['adresse_postale'] ?? '') ?></textarea>
            </div>

            <hr style="margin: 25px 0; border: none; border-top: 1px solid #e2e8f0;">
            <h3>Identifiants d'administration</h3>

            <div class="form-group">
                <label for="email_admin">E-mail administrateur *</label>
                <input type="email" id="email_admin" name="email_admin" value="<?= htmlspecialchars($_POST['email_admin'] ?? '') ?>" required placeholder="admin@votre-boutique.com">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe (10 caractères min.) *</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder="••••••••••">
            </div>

            <div class="form-group">
                <label for="mot_de_passe_confirm">Confirmer le mot de passe *</label>
                <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required placeholder="••••••••••">
            </div>

            <button type="submit">Valider et enregistrer</button>
        </form>

    <?php endif; ?>
</div>

</body>
</html>

 
