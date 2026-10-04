<?php
// Activation de l'affichage des erreurs pour le débogage
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//ini_set('display_errors', 0);
//error_reporting(0);

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

// Récupération automatique de la commande liée à ce token
$stmtToken = $bdd->prepare("SELECT id, statut FROM commandes WHERE config_token = ?");
$stmtToken->execute([$token]);
$commande = $stmtToken->fetch();

if (!$commande) {
    die("Jeton invalide ou commande introuvable.");
}

$commande_id = (int)$commande['id'];

// Vérification si la configuration a déjà été effectuée
if (in_array($commande['statut'], ['configure', 'en_attente', 'deploye'])) {
    // Option A : Message clair et propre
    die('
        <div style="text-align:center; padding:50px; font-family:sans-serif;">
            <h2>Configuration déjà effectuée !</h2>
            <p>Votre boutique est en cours de création ou déjà déployée.</p>
            <p>Vous pouvez accéder à votre espace administration ou contacter le support.</p>
        </div>
    ');
   
    // Option B (alternative) : Redirection directe vers leur boutique ou dashboard
    header('Location: https://' . $commande['subdomain'] . '.benjaminlouis.eu/admin');
    exit;
}

// 3. Traitement du formulaire à la soumission

//////////////////////////////////////////////////////////////
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // Nettoyage et désinfection des entrées (Anti-XSS & Injection)
//     $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise'] ?? ''), ENT_QUOTES, 'UTF-8');
//     $siret          = preg_replace('/\s+/', '', $_POST['siret'] ?? ''); // Supprime tous les espaces
//     $adresse        = htmlspecialchars(trim($_POST['adresse_postale'] ?? ''), ENT_QUOTES, 'UTF-8');
//     $email_admin    = filter_var(trim($_POST['email_admin'] ?? ''), FILTER_SANITIZE_EMAIL);
//     $pass           = $_POST['mot_de_passe'] ?? '';
//     $pass_confirm   = $_POST['mot_de_passe_confirm'] ?? '';
//     $commande_id    = (int)($_POST['commande_id'] ?? 0);

//     // Validations strictes des données
//     if (empty($nom_entreprise) || empty($siret) || empty($adresse) || empty($email_admin) || empty($pass)) {
//         $erreur = "Veuillez remplir tous les champs obligatoires.";
//     } elseif (!filter_var($email_admin, FILTER_VALIDATE_EMAIL)) {
//         $erreur = "L'adresse e-mail renseignée n'est pas valide.";
//     } elseif (!ctype_digit($siret) || strlen($siret) !== 14) {
//         $erreur = "Le numéro SIRET doit contenir exactement 14 chiffres.";
//     } elseif ($pass !== $pass_confirm) {
//         $erreur = "Les mots de passe ne correspondent pas.";


//     // } elseif (strlen($pass) < 10) {
//     //     $erreur = "Le mot de passe doit faire au moins 10 caractères pour des raisons de sécurité.";
//     // } 
    
//     } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) {
//         $erreur = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).";
//     }
    
//     else {
//         // Hashage sécurisé du mot de passe
//         $pass_hash = password_hash($pass, PASSWORD_BCRYPT);

//         // Insertion sécurisée en BDD via requête préparée
//         $stmt = $bdd->prepare("
//             INSERT INTO prospects_configurations
//             (commande_id, token, nom_entreprise, siret, adresse_postale, email_admin, mot_de_passe_hash, statut_deploiement)
//             VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
//             ON DUPLICATE KEY UPDATE
//                 nom_entreprise = VALUES(nom_entreprise),
//                 siret = VALUES(siret),
//                 adresse_postale = VALUES(adresse_postale),
//                 email_admin = VALUES(email_admin),
//                 mot_de_passe_hash = VALUES(mot_de_passe_hash)
//         ");
//         $stmt->execute([$commande_id, $token, $nom_entreprise, $siret, $adresse, $email_admin, $pass_hash]);

//         $succes = true;
//     }
// }
///////////////////////////////////////////////////////

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des champs de base
    $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise'] ?? ''), ENT_QUOTES, 'UTF-8');
    $siret          = preg_replace('/\s+/', '', $_POST['siret'] ?? '');
    $adresse        = htmlspecialchars(trim($_POST['adresse_postale'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email_admin    = filter_var(trim($_POST['email_admin'] ?? ''), FILTER_SANITIZE_EMAIL);
    $pass           = $_POST['mot_de_passe'] ?? '';
    $pass_confirm   = $_POST['mot_de_passe_confirm'] ?? '';
    $commande_id    = (int)($_POST['commande_id'] ?? 0);

    // Nettoyage des nouveaux champs
    $subdomain          = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['subdomain'] ?? '')));
    $capital_social     = htmlspecialchars(trim($_POST['capital_social'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tva_intra          = htmlspecialchars(trim($_POST['tva_intra'] ?? ''), ENT_QUOTES, 'UTF-8');
    $telephone          = htmlspecialchars(trim($_POST['telephone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $delai_retractation = htmlspecialchars(trim($_POST['delai_retractation'] ?? ''), ENT_QUOTES, 'UTF-8');
    $delai_livraison    = htmlspecialchars(trim($_POST['delai_livraison'] ?? ''), ENT_QUOTES, 'UTF-8');
    $frais_port         = htmlspecialchars(trim($_POST['frais_port'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tribunal_competent = htmlspecialchars(trim($_POST['tribunal_competent'] ?? ''), ENT_QUOTES, 'UTF-8');
    $lien_facebook      = filter_var(trim($_POST['lien_facebook'] ?? ''), FILTER_SANITIZE_URL);
    $lien_instagram     = filter_var(trim($_POST['lien_instagram'] ?? ''), FILTER_SANITIZE_URL);
    $google_maps_iframe = trim($_POST['google_maps_iframe'] ?? '');
    $couleur_principale = $_POST['couleur_principale'] ?? '#007bff';
    $couleur_secondaire = $_POST['couleur_secondaire'] ?? '#6c757d';
    $couleur_texte      = $_POST['couleur_texte'] ?? '#212529';

    // Validations
    if (empty($nom_entreprise) || empty($siret) || empty($adresse) || empty($email_admin) || empty($pass) || empty($subdomain)) {
        $erreur = "Veuillez remplir tous les champs obligatoires (*).";
    } elseif (!filter_var($email_admin, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail renseignée n'est pas valide.";
    } elseif (!ctype_digit($siret) || strlen($siret) !== 14) {
        $erreur = "Le numéro SIRET doit contenir exactement 14 chiffres.";
    } elseif ($pass !== $pass_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    } else {

        // --- GESTION DU TÉLÉVERSEMENT DES FICHIERS ---
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $image_fond_url = null;
        $video_fond_url = null;

        // Upload Image
        if (isset($_FILES['image_fond_file']) && $_FILES['image_fond_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_fond_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['image_fond_file']['size'] <= 5 * 1024 * 1024) {
                $filename = 'img_' . $subdomain . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_fond_file']['tmp_name'], $upload_dir . $filename)) {
                    $image_fond_url = 'uploads/' . $filename;
                }
            } else {
                $erreur = "L'image de fond doit être au format JPG, PNG ou WEBP et faire moins de 5 Mo.";
            }
        }

        // Upload Vidéo
        if (empty($erreur) && isset($_FILES['video_fond_file']) && $_FILES['video_fond_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['video_fond_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm']) && $_FILES['video_fond_file']['size'] <= 20 * 1024 * 1024) {
                $filename = 'vid_' . $subdomain . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['video_fond_file']['tmp_name'], $upload_dir . $filename)) {
                    $video_fond_url = 'uploads/' . $filename;
                }
            } else {
                $erreur = "La vidéo doit être au format MP4 ou WEBM et faire moins de 20 Mo.";
            }
        }

        // Enregistrement en Base de Données
        if (empty($erreur)) {
            $pass_hash = password_hash($pass, PASSWORD_BCRYPT);

            $stmt = $bdd->prepare("
                INSERT INTO prospects_configurations
                (commande_id, token, nom_entreprise, siret, adresse_postale, email_admin, mot_de_passe_hash,
                 subdomain, capital_social, tva_intra, telephone, delai_retractation, delai_livraison,
                 frais_port, tribunal_competent, lien_facebook, lien_instagram, google_maps_iframe,
                 couleur_principale, couleur_secondaire, couleur_texte, image_fond_url, video_fond_url, statut_deploiement)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
                ON DUPLICATE KEY UPDATE
                    nom_entreprise = VALUES(nom_entreprise),
                    siret = VALUES(siret),
                    adresse_postale = VALUES(adresse_postale),
                    email_admin = VALUES(email_admin),
                    mot_de_passe_hash = VALUES(mot_de_passe_hash),
                    subdomain = VALUES(subdomain),
                    capital_social = VALUES(capital_social),
                    tva_intra = VALUES(tva_intra),
                    telephone = VALUES(telephone),
                    delai_retractation = VALUES(delai_retractation),
                    delai_livraison = VALUES(delai_livraison),
                    frais_port = VALUES(frais_port),
                    tribunal_competent = VALUES(tribunal_competent),
                    lien_facebook = VALUES(lien_facebook),
                    lien_instagram = VALUES(lien_instagram),
                    google_maps_iframe = VALUES(google_maps_iframe),
                    couleur_principale = VALUES(couleur_principale),
                    couleur_secondaire = VALUES(couleur_secondaire),
                    couleur_texte = VALUES(couleur_texte),
                    image_fond_url = IFNULL(VALUES(image_fond_url), image_fond_url),
                    video_fond_url = IFNULL(VALUES(video_fond_url), video_fond_url)
            ");

            $stmt->execute([
                $commande_id, $token, $nom_entreprise, $siret, $adresse, $email_admin, $pass_hash,
                $subdomain, $capital_social, $tva_intra, $telephone, $delai_retractation, $delai_livraison,
                $frais_port, $tribunal_competent, $lien_facebook, $lien_instagram, $google_maps_iframe,
                $couleur_principale, $couleur_secondaire, $couleur_texte, $image_fond_url, $video_fond_url
            ]);

            $succes = true;
        }
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

        #prev-hero {
            position: relative;
            width: 100%;
            height: 300px; /* Ajuste la hauteur selon ton besoin */
            overflow: hidden;
            background-size: cover;
            background-position: center;
        }

        #prev-hero video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1; /* La vidéo se place au-dessus du fond */
        }
    
    
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

        <form method="POST" enctype="multipart/form-data">
            <!--<input type="hidden" name="commande_id" value="1">-->
            <input type="hidden" name="commande_id" value="<?= $commande_id ?>">

            <h3>1. Informations Légales</h3>
            <div class="form-group">
                <label for="nom_entreprise">Nom de l'entreprise *</label>
                <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?= htmlspecialchars($_POST['nom_entreprise'] ?? '') ?>" required placeholder="Ex: Ma Société SAS">
            </div>

            <div class="form-group">
                <label for="subdomain">Sous-domaine souhaité *</label>
                <input type="text" id="subdomain" name="subdomain" value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>" required placeholder="ex: ma-boutique (sans espace)">
            </div>

            <div class="form-group">
                <label for="siret">Numéro SIRET (14 chiffres) *</label>
                <input type="text" id="siret" name="siret" value="<?= htmlspecialchars($_POST['siret'] ?? '') ?>" maxlength="17" required placeholder="Ex: 123 456 789 00012">
            </div>

            <div class="form-group">
                <label for="capital_social">Capital Social</label>
                <input type="text" id="capital_social" name="capital_social" value="<?= htmlspecialchars($_POST['capital_social'] ?? '') ?>" placeholder="Ex: 1 000 €">
            </div>

            <div class="form-group">
                <label for="tva_intra">N° TVA Intracommunautaire</label>
                <input type="text" id="tva_intra" name="tva_intra" value="<?= htmlspecialchars($_POST['tva_intra'] ?? '') ?>" placeholder="Ex: FR12345678901 ou Non assujetti">
            </div>

            <div class="form-group">
                <label for="adresse_postale">Adresse du siège social *</label>
                <textarea id="adresse_postale" name="adresse_postale" rows="2" required placeholder="Ex: 10 Rue du Commerce, 75001 Paris"><?= htmlspecialchars($_POST['adresse_postale'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone de contact</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" placeholder="Ex: 01 02 03 04 05">
            </div>

            <h3>2. CGV & Politiques</h3>
            <div class="form-group">
                <label for="delai_retractation">Délai de rétractation</label>
                <input type="text" id="delai_retractation" name="delai_retractation" value="<?= htmlspecialchars($_POST['delai_retractation'] ?? '14 jours') ?>">
            </div>

            <div class="form-group">
                <label for="delai_livraison">Délai de livraison</label>
                <input type="text" id="delai_livraison" name="delai_livraison" value="<?= htmlspecialchars($_POST['delai_livraison'] ?? '2 à 5 jours ouvrés') ?>">
            </div>

            <div class="form-group">
                <label for="frais_port">Frais de port</label>
                <input type="text" id="frais_port" name="frais_port" value="<?= htmlspecialchars($_POST['frais_port'] ?? 'Calculés lors de la commande') ?>">
            </div>

            <div class="form-group">
                <label for="tribunal_competent">Tribunal compétent</label>
                <input type="text" id="tribunal_competent" name="tribunal_competent" value="<?= htmlspecialchars($_POST['tribunal_competent'] ?? '') ?>" placeholder="Ex: Tribunal de Commerce de Paris">
            </div>

            <h3>3. Réseaux & Localisation</h3>
            <div class="form-group">
                <label for="lien_facebook">Lien Facebook</label>
                <input type="url" id="lien_facebook" name="lien_facebook" value="<?= htmlspecialchars($_POST['lien_facebook'] ?? '') ?>" placeholder="https://facebook.com/votrepage">
            </div>

            <div class="form-group">
                <label for="lien_instagram">Lien Instagram</label>
                <input type="url" id="lien_instagram" name="lien_instagram" value="<?= htmlspecialchars($_POST['lien_instagram'] ?? '') ?>" placeholder="https://instagram.com/votrecompte">
            </div>

            <div class="form-group">
                <label for="google_maps_iframe">Intégration Google Maps (Iframe)</label>
                <textarea id="google_maps_iframe" name="google_maps_iframe" rows="2" placeholder="Collez le code <iframe...> de Google Maps"><?= htmlspecialchars($_POST['google_maps_iframe'] ?? '') ?></textarea>
            </div>

            <h3>4. Charte Graphique & Médias</h3>
            <div class="color-group">
                <div class="form-group">
                    <label for="couleur_principale">Couleur Principale</label>
                    <input type="color" id="couleur_principale" name="couleur_principale" value="<?= htmlspecialchars($_POST['couleur_principale'] ?? '#007bff') ?>">
                </div>

                <div class="form-group">
                    <label for="couleur_secondaire">Couleur Secondaire</label>
                    <input type="color" id="couleur_secondaire" name="couleur_secondaire" value="<?= htmlspecialchars($_POST['couleur_secondaire'] ?? '#6c757d') ?>">
                </div>

                <div class="form-group">
                    <label for="couleur_texte">Couleur Texte</label>
                    <input type="color" id="couleur_texte" name="couleur_texte" value="<?= htmlspecialchars($_POST['couleur_texte'] ?? '#212529') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="image_fond_file">Image de fond (JPG, PNG, WEBP — Max 5 Mo)</label>
                <input type="file" id="image_fond_file" name="image_fond_file" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group">
                <label for="video_fond_file">Vidéo de fond (MP4, WEBM — Max 20 Mo)</label>
                <input type="file" id="video_fond_file" name="video_fond_file" accept="video/mp4,video/webm">
            </div>

            <h3>5. Identifiants Administrateur</h3>
            <div class="form-group">
                <label for="email_admin">E-mail administrateur *</label>
                <input type="email" id="email_admin" name="email_admin" value="<?= htmlspecialchars($_POST['email_admin'] ?? '') ?>" required placeholder="admin@votre-boutique.com">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe *</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder="••••••••••"
                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}"
                title="Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.">
            </div>

            <div class="form-group">
                <label for="mot_de_passe_confirm">Confirmer le mot de passe *</label>
                <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required placeholder="••••••••••"
                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}"
                title="Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.">
            </div>

           <div class="preview-container" style="margin-top: 30px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 12px; background: #fafafa;">
                <h3 style="margin-top:0; font-size:16px; color:#333; text-align:center;">Aperçu en temps réel</h3>
            
                <!-- Maquette Smartphone / Boutique -->
            <div id="shop-preview" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background-color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
        
            <!-- Header (Menu & Panier) -->
            <div id="prev-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background-color: #f8d7da;">
                <div style="font-weight: bold; font-size: 14px; color: #333;">🛒 (0)</div>
                <div id="prev-title" style="font-weight: bold; font-size: 15px; color: #333;">SARL LOUIS</div>
                <div style="font-size: 18px; color: #333;">☰</div>
            </div>

            <!-- 1. SECTION HERO (IMAGE BOUTIQUE) -->
            <div id="prev-hero" style="height: 200px; background-size: cover; background-position: center; position: relative;">
            </div>

           <!-- 2. GRAND CONTENEUR POUR LA VIDÉO DE FOND (PRODUITS + CONTACT) -->
            <div id="prev-video-wrapper" style="position: relative; overflow: hidden;">

                <!-- Le conteneur vidéo placé en arrière-plan -->
                <div id="prev-video-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;"></div>

                <!-- Contenu positionné au-dessus de la vidéo -->
                <div style="position: relative; z-index: 2;">

                    <!-- SECTION PRODUITS -->
                    <div id="prev-products-section" style="padding: 30px 15px; text-align: center;">
                        <h4 id="prev-text" style="margin: 0 0 15px 0; font-size: 18px; color: #111; font-weight: bold;">
                            Découvrir nos produits
                        </h4>
                        <button id="prev-btn" style="padding: 10px 20px; border: none; border-radius: 6px; background: linear-gradient(90deg, #6f42c1, #dc3545); color: #fff; font-weight: bold; font-size: 12px; text-transform: uppercase;">
                            Découvrir notre boutique
                        </button>
                    </div>

                    <!-- SECTION CONTACT (avec transparence) -->
                    <div id="prev-contact-section" style="background-color: rgba(229, 179, 189, 0.8); padding: 20px 15px; text-align: center;">
                        <div style="margin-bottom: 10px; color: #fff; font-weight: bold; font-size: 13px;">Votre nom</div>
                        <div style="background: #fff; height: 28px; border-radius: 4px; margin-bottom: 12px;"></div>

                        <div style="margin-bottom: 10px; color: #fff; font-weight: bold; font-size: 13px;">Votre email</div>
                        <div style="background: #fff; height: 28px; border-radius: 4px; margin-bottom: 12px;"></div>

                        <div style="margin-bottom: 10px; color: #fff; font-weight: bold; font-size: 13px;">Votre message</div>
                        <div style="background: #fff; height: 60px; border-radius: 4px; margin-bottom: 15px;"></div>

                        <button id="prev-contact-btn" style="padding: 8px 38px; border: none; border-radius: 6px; background: linear-gradient(90deg, #6f42c1, #dc3545); color: #fff; font-weight: bold; font-size: 12px;">
                            OK
                        </button>
                    </div>

                </div>
            </div>

            <!-- FOOTER NOIR (RÉSEAUX & MENTIONS) -->
            <div style="background-color: #1a1a1a; padding: 15px 10px; text-align: center; color: #fff;">
                <!-- Icônes Réseaux -->
                <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 12px; font-size: 16px;">
                    <span>🌐</span>
                    <span>▶</span>
                    <span>📍</span>
                </div>
            
                <!-- Liens Légaux -->
                <div style="font-size: 11px; line-height: 1.4; font-weight: 500; color: #e0e0e0;">
                    <span>Mentions Légales</span> &nbsp; <span>Conditions Générales de Vente</span>
                </div>
            </div>

            </div> <!-- Fin de la maquette smartphone -->
            </div> <!-- Fin du conteneur d'aperçu -->

            <button type="submit">Valider et enregistrer</button>
        </form>

    <?php endif; ?>
</div>

<!--<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Mise à jour du nom de l'entreprise
    const inputNom = document.querySelector('input[name="nom_entreprise"]');
    if (inputNom) {
        inputNom.addEventListener('input', (e) => {
            document.getElementById('prev-title').textContent = e.target.value || 'Nom de votre entreprise';
        });
    }

    // 2. Mise à jour dynamique des couleurs
    const colorCouleurPrincipale = document.querySelector('input[name="couleur_principale"]');
    if (colorCouleurPrincipale) {
        colorCouleurPrincipale.addEventListener('input', (e) => {
            document.getElementById('prev-header').style.backgroundColor = e.target.value;
        });
    }

    const colorCouleurTexte = document.querySelector('input[name="couleur_texte"]');
    if (colorCouleurTexte) {
        colorCouleurTexte.addEventListener('input', (e) => {
            document.getElementById('prev-text').style.color = e.target.value;
        });
    }

    // 3. Prévisualisation instantanée de l'image de fond
    // const inputImage = document.querySelector('input[name="image_fond_file"]');
    // if (inputImage) {
    //     inputImage.addEventListener('change', function(e) {
    //         const file = e.target.files[0];
    //         if (file) {
    //             const reader = new window.FileReader();
    //             reader.onload = function(event) {
    //                 document.getElementById('shop-preview').style.backgroundImage = `url('${event.target.result}')`;
    //             };
    //             reader.readAsDataURL(file);
    //         }
    //     });
    // }
    // Image de fond appliquée à la section Hero
const inputImage = document.querySelector('input[name="image_fond_file"]');
if (inputImage) {
    inputImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const imageUrl = URL.createObjectURL(file);
            document.getElementById('prev-hero').style.backgroundImage = `url('${imageUrl}')`;
        }
    });
}

    // 4. Prévisualisation instantanée de la vidéo
    const inputVideo = document.querySelector('input[name="video_fond_file"]');
    if (inputVideo) {
        inputVideo.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const container = document.getElementById('prev-video-container');
            container.innerHTML = ''; // Nettoyage
           
            if (file) {
                const videoUrl = URL.createObjectURL(file);
                const videoElem = document.createElement('video');
                videoElem.src = videoUrl;
                videoElem.autoplay = true;
                videoElem.loop = true;
                videoElem.muted = true;
                videoElem.style.width = '100%';
                videoElem.style.borderRadius = '4px';
                container.appendChild(videoElem);
            }
        });
    }
});
</script>-->

<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. Détection des éléments d'entrée (inputs HTML)
    const inputNom = document.querySelector('input[name="nom_entreprise"]');
    const colorCouleurPrincipale = document.querySelector('input[name="couleur_principale"]');
    const colorCouleurTexte = document.querySelector('input[name="couleur_texte"]');

    // Détection auto (gère les noms avec ou sans _file)
    const inputImage = document.querySelector('input[name="image_fond_file"]') || document.querySelector('input[name="image_fond"]');
    const inputVideo = document.querySelector('input[name="video_fond_file"]') || document.querySelector('input[name="video_fond"]');

    // 2. Gestion du nom de l'entreprise
    if (inputNom) {
        inputNom.addEventListener('input', function(e) {
            const prevTitle = document.getElementById('prev-title');
            if (prevTitle) prevTitle.textContent = e.target.value || 'SARL LOUIS';
        });
    }

    // 3. Gestion des Couleurs
    function mettreAJourCouleurs() {
        const prevHeader = document.getElementById('prev-header');
        const prevText = document.getElementById('prev-text');
        const btn1 = document.getElementById('prev-btn');
        const btn2 = document.getElementById('prev-contact-btn');

        const c1 = colorCouleurPrincipale ? colorCouleurPrincipale.value : '#e5b3bd';
        const c2 = colorCouleurTexte ? colorCouleurTexte.value : '#dc3545';

        if (prevHeader) prevHeader.style.backgroundColor = c1;
        if (prevText) prevText.style.color = c2;
       
        const gradient = `linear-gradient(90deg, ${c1}, ${c2})`;
        if (btn1) btn1.style.background = gradient;
        if (btn2) btn2.style.background = gradient;

        // Met à jour la couleur du fond du formulaire de contact avec transparence (80% d'opacité)
        if (sectionContact && colorCouleurPrincipale) {
            sectionContact.style.backgroundColor = colorCouleurPrincipale.value + 'CC'; // 'CC' correspond à ~80% d'opacité en hexadécimal
        }


    }

    if (colorCouleurPrincipale) colorCouleurPrincipale.addEventListener('input', mettreAJourCouleurs);
    if (colorCouleurTexte) colorCouleurTexte.addEventListener('input', mettreAJourCouleurs);

    // 4. Aperçu de l'IMAGE dans la zone Hero (#prev-hero)
    if (inputImage) {
        inputImage.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const prevHero = document.getElementById('prev-hero');
            if (file && prevHero) {
                const imageUrl = URL.createObjectURL(file);
                prevHero.style.backgroundImage = `url('${imageUrl}')`;
                prevHero.style.backgroundSize = 'cover';
                prevHero.style.backgroundPosition = 'center';
                prevHero.style.backgroundRepeat = 'no-repeat';
            }
        });
    }

    // 5. Aperçu de la VIDÉO sous le texte (#prev-video-container)
    if (inputVideo) {
        inputVideo.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const container = document.getElementById('prev-video-container');
            if (file && container) {
                container.innerHTML = ''; // Réinitialise
               
                const videoElem = document.createElement('video');
                videoElem.src = URL.createObjectURL(file);
                videoElem.autoplay = true;
                videoElem.loop = true;
                videoElem.muted = true;
                videoElem.playsInline = true; // Indispensable sur iPhone/Android
               
                videoElem.style.width = '100%';
                videoElem.style.height = '100%';
                videoElem.style.objectFit = 'cover';
               
                container.appendChild(videoElem);
               
                // Forcer le lancement de la lecture
                videoElem.play().catch(function(err) {
                    console.log("Lecture automatique bloquée par le navigateur", err);
                });
            }
        });
    }

});

</script>

 



</body>
</html>

 
