<?php
// Activation de l'affichage des erreurs pour le débogage (si besoin)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// 1. Connexion à la base de données
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/config.php';

try {
    $bdd = new PDO("mysql:host=localhost;dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('Erreur de connexion à la base de données.');
}

// Détection du sous-domaine depuis l'URL ou le paramètre GET
$host = $_SERVER['HTTP_HOST'] ?? '';
$subdomain_from_url = explode('.', $host)[0];
$token = trim($_GET['token'] ?? $_GET['token'] ?? '');
$slug = trim($_GET['slug'] ?? $_GET['subdomain'] ?? $subdomain_from_url);
$commande_id_get = (int)($_GET['id'] ?? 0);

$erreur = '';
$succes = false;

// 1. Passage par token (lien client)
if (!empty($token)) {
    $stmtToken = $bdd->prepare("SELECT id, statut FROM commandes WHERE config_token = ?");
    $stmtToken->execute([$token]);
    $commande = $stmtToken->fetch();
}
// 2. Passage par slug / sous-domaine (ex: sarllouis)
elseif (!empty($slug) && $slug !== 'www' && $slug !== 'benjaminlouis') {
    $stmtSlug = $bdd->prepare("
        SELECT c.id, c.statut, p.subdomain
        FROM commandes c
        JOIN prospects_configurations p ON p.commande_id = c.id
        WHERE LOWER(p.subdomain) = LOWER(?)
    ");
    $stmtSlug->execute([$slug]);
    $commande = $stmtSlug->fetch();
}
// 3. Passage par ID (fallback admin)
elseif ($commande_id_get > 0) {
    $stmtCmd = $bdd->prepare("SELECT id, statut FROM commandes WHERE id = ?");
    $stmtCmd->execute([$commande_id_get]);
    $commande = $stmtCmd->fetch();
}

// REDIRECTION AUTOMATIQUE SI BOUTIQUE INTROUVABLE
if (!$commande) {
    header('Location: https://www.benjaminlouis.eu/');
    exit();
}

$commande_id = (int)$commande['id'];

// Récupération de la configuration existante
$stmtConfig = $bdd->prepare("SELECT * FROM prospects_configurations WHERE commande_id = ?");
$stmtConfig->execute([$commande_id]);
$prospect = $stmtConfig->fetch();

// Vérification si la configuration a déjà été effectuée
if (in_array($commande['statut'], ['configure', 'deploye']) && empty($token) && $commande_id_get === 0) {
    header("Location: https://www.benjaminlouis.eu/");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des champs de base
    $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise'] ?? ''), ENT_QUOTES, 'UTF-8');
    $siret = preg_replace('/\s+/', '', $_POST['siret'] ?? '');
    $adresse = htmlspecialchars(trim($_POST['adresse_postale'] ?? ''), ENT_QUOTES, 'UTF-8');

    $rcs = htmlspecialchars(trim($_POST['rcs'] ?? ''), ENT_QUOTES, 'UTF-8');

    $email_admin = filter_var(trim($_POST['email_admin'] ?? ''), FILTER_SANITIZE_EMAIL);
   
    $pass = $_POST['mot_de_passe_hash'] ?? '';
    $pass_confirm = $_POST['mot_de_passe_hash_confirm'] ?? '';
   
    // Nettoyage des nouveaux champs
    $subdomain = strtolower(preg_replace('/[^a-z0-9-]/', '', trim($_POST['subdomain'] ?? '')));
    $capital_social = htmlspecialchars(trim($_POST['capital_social'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tva_intra = htmlspecialchars(trim($_POST['tva_intra'] ?? ''), ENT_QUOTES, 'UTF-8');
    $telephone = htmlspecialchars(trim($_POST['telephone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $delai_retractation = htmlspecialchars(trim($_POST['delai_retractation'] ?? ''), ENT_QUOTES, 'UTF-8');
    $delai_livraison = htmlspecialchars(trim($_POST['delai_livraison'] ?? ''), ENT_QUOTES, 'UTF-8');
    $frais_port = htmlspecialchars(trim($_POST['frais_port'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tribunal_competent = htmlspecialchars(trim($_POST['tribunal_competent'] ?? ''), ENT_QUOTES, 'UTF-8');
    $lien_facebook = filter_var(trim($_POST['lien_facebook'] ?? ''), FILTER_SANITIZE_URL);
    $lien_instagram = filter_var(trim($_POST['lien_instagram'] ?? ''), FILTER_SANITIZE_URL);
    $google_maps_iframe = trim($_POST['google_maps_iframe'] ?? '');
    $couleur_principale = $_POST['couleur_principale'] ?? '#e5829b';
    $couleur_secondaire = $_POST['couleur_secondaire'] ?? '#dde9e9';
    $couleur_texte = $_POST['couleur_texte'] ?? '#212529';

    $est_creation = empty($prospect);

    // Validations
    if (empty($nom_entreprise) || empty($siret) || empty($adresse) || empty($email_admin) || empty($subdomain) || ($est_creation && empty($pass))) {
        $erreur = "Veuillez remplir tous les champs obligatoires (*).";
    } elseif (!filter_var($email_admin, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail renseignée n'est pas valide.";
    } elseif (!ctype_digit($siret) || strlen($siret) !== 14) {
        $erreur = "Le numéro SIRET doit contenir exactement 14 chiffres.";
    } elseif (!empty($pass) && $pass !== $pass_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (!empty($pass) && !preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    } else {
        // --- GESTION DU TÉLÉVERSEMENT DES FICHIERS ---
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Récupération des anciennes valeurs par défaut pour ne pas les perdre
        $image_fond_url = $prospect['image_fond_url'] ?? null;

        $logo_url = $prospect['logo_url'] ?? null;
        $image_mediateur_url = $prospect['image_mediateur_url'] ?? null;

        $video_fond_url = $prospect['video_fond_url'] ?? null;


        // Upload Image
        if (isset($_FILES['image_fond_file']) && $_FILES['image_fond_file']['error'] === UPLOAD_ERR_OK && $_FILES['image_fond_file']['size'] > 0) {
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

        // Upload Logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK && $_FILES['logo']['size'] > 0) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['logo']['size'] <= 5 * 1024 * 1024) {
                $filename = 'logo_' . $subdomain . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
                    $logo_url = 'uploads/' . $filename;
                }
            } else {
                $erreur = "Le logo de l'entreprise doit être au format JPG, PNG ou WEBP et faire moins de 5 Mo.";
            }
        }

        // Upload Médiateur
        if (isset($_FILES['image_mediateur']) && $_FILES['image_mediateur']['error'] === UPLOAD_ERR_OK && $_FILES['image_mediateur']['size'] > 0) {
            $ext = strtolower(pathinfo($_FILES['image_mediateur']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['image_mediateur']['size'] <= 5 * 1024 * 1024) {
                $filename = 'mediateur_' . $subdomain . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_mediateur']['tmp_name'], $upload_dir . $filename)) {
                    $image_mediateur_url = 'uploads/' . $filename;
                }
            } else {
                $erreur = "Le logo du médiateur doit être au format JPG, PNG ou WEBP et faire moins de 5 Mo.";
            }
        }


        // Upload Vidéo
        if (empty($erreur) && isset($_FILES['video_fond_file']) && $_FILES['video_fond_file']['error'] === UPLOAD_ERR_OK && $_FILES['video_fond_file']['size'] > 0) {
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
            // Gestion robuste du mot de passe (si vide lors d'une maj, on garde l'existant)
            if (!empty($pass)) {
                $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
            } else {
                $pass_hash = $prospect['mot_de_passe_hash'] ?? '';
            }

            $stmt = $bdd->prepare("
                INSERT INTO prospects_configurations
                (commande_id, token, nom_entreprise, siret, rcs, adresse_postale, email_admin, mot_de_passe_hash,
                subdomain, capital_social, tva_intra, telephone, delai_retractation, delai_livraison,
                frais_port, tribunal_competent, lien_facebook, lien_instagram, google_maps_iframe,
                couleur_principale, couleur_secondaire, couleur_texte, image_fond_url, image_mediateur_url, logo_url, video_fond_url, statut_deploiement)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
                ON DUPLICATE KEY UPDATE
                nom_entreprise = VALUES(nom_entreprise),
                siret = VALUES(siret),
                rcs = VALUES(rcs),
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
                image_mediateur_url = IFNULL(VALUES(image_mediateur_url), image_mediateur_url),
                logo_url = IFNULL(VALUES(logo_url), logo_url),
                video_fond_url = IFNULL(VALUES(video_fond_url), video_fond_url)
            ");
           
      
            $token_a_enregistrer = $_POST['token'] ?? $_GET['token'] ?? ($token ?? ($prospect['token'] ?? ''));
            if (empty($token_a_enregistrer)) {
                $token_a_enregistrer = bin2hex(random_bytes(16));
            }

                $stmt->execute([
                $commande_id, $token_a_enregistrer, $nom_entreprise, $siret, $rcs, $adresse, $email_admin, $pass_hash,
                $subdomain, $capital_social, $tva_intra, $telephone, $delai_retractation, $delai_livraison,
                $frais_port, $tribunal_competent, $lien_facebook, $lien_instagram, $google_maps_iframe,
                $couleur_principale, $couleur_secondaire, $couleur_texte, $image_fond_url, $image_mediateur_url, $logo_url, $video_fond_url
            ]);

            // Synchronisation automatique et robuste dans toso8422_boutique.admin_users
            try {
                if (!empty($email_admin) && !empty($pass_hash)) {
                    $email_cible = trim($email_admin);
                    $stmt_sync = $bdd->prepare("
                        UPDATE toso8422_boutique.admin_users
                        SET password_hash = :pass_hash
                        WHERE username = :email
                    ");
                    $stmt_sync->execute([
                        ':pass_hash' => $pass_hash,
                        ':email' => $email_cible
                    ]);
                }
            } catch (Exception $e) {
                // Ignore ou gère l'erreur de sync silently si la base distante varie
            }

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
        input[type="text"], input[type="email"], input[type="password"], input[type="tel"], input[type="url"], textarea {
            width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; box-sizing: border-box;
        }
        input:focus, textarea:focus { border-color: #2563eb; outline: none; }
        button[type="submit"] { background: #2563eb; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: bold; width: 100%; }
        button[type="submit"]:hover { background: #1d4ed8; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.95rem; }
        .alert-success { background: #dcfce7; color: #166534; padding: 20px; border-radius: 6px; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <h1>Configuration de votre boutique</h1>
    <?php if ($succes): ?>
        <div class="alert-success">
            <h3>Informations enregistrées avec succès !</h3>
            <p>Nous préparons le déploiement de votre boutique. Vous recevrez un e-mail dès qu'elle sera prête à l'emploi.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($erreur)): ?>
            <div class="alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
       
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="commande_id" value="<?= $commande_id ?>">
           <input type="hidden" name="token" value="<?php echo htmlspecialchars($_POST['token'] ?? $token ?? $prospect['token'] ?? ''); ?>">
           
            <h3>1. Informations Légales</h3>
            <div class="form-group">
                <label for="nom_entreprise">Nom de l'entreprise *</label>
                <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?= htmlspecialchars($_POST['nom_entreprise'] ?? $prospect['nom_entreprise'] ?? '') ?>" required placeholder="ex: Nom de votre société">
            </div>
            <div class="form-group">
                <label for="subdomain">Sous-domaine souhaité *</label>
                <input type="text" id="subdomain" name="subdomain" value="<?= htmlspecialchars($_POST['subdomain'] ?? $prospect['subdomain'] ?? '') ?>" required placeholder="ex: ma-boutique (sans espace)">
            </div>
            <div class="form-group">
                <label for="siret">Numéro SIRET (14 chiffres) *</label>
                <input type="text" id="siret" name="siret" value="<?= htmlspecialchars($_POST['siret'] ?? $prospect['siret'] ?? '') ?>" required placeholder="Ex: 123 456 789 00012">
            </div>

            <div class="form-group">
                <label for="rcs">RCS *</label>
                <input type="text" id="rcs" name="rcs" value="<?= htmlspecialchars($_POST['rcs'] ?? $prospect['rcs'] ?? '') ?>" required placeholder="Ex: Guéret">
            </div>

            <div class="form-group">
                <label for="capital_social">Capital Social</label>
                <input type="text" id="capital_social" name="capital_social" value="<?= htmlspecialchars($_POST['capital_social'] ?? $prospect['capital_social'] ?? '') ?>" placeholder="Ex: 1 000 €">
            </div>
            <div class="form-group">
                <label for="tva_intra">N° TVA Intracommunautaire</label>
                <input type="text" id="tva_intra" name="tva_intra" value="<?= htmlspecialchars($_POST['tva_intra'] ?? $prospect['tva_intra'] ?? '') ?>" placeholder="Ex: FR12345678901 ou Non assujetti">
            </div>
            <div class="form-group">
                <label for="adresse_postale">Adresse du siège social *</label>
                <input type="text" id="adresse_postale" name="adresse_postale" value="<?= htmlspecialchars($_POST['adresse_postale'] ?? $prospect['adresse_postale'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="telephone">Téléphone de contact</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? $prospect['telephone'] ?? '') ?>" placeholder="Ex: 01 02 03 04 05">
            </div>
            <div class="form-group">
                <label for="tribunal_competent">Tribunal compétent</label>
                <input type="text" id="tribunal_competent" name="tribunal_competent" value="<?= htmlspecialchars($_POST['tribunal_competent'] ?? $prospect['tribunal_competent'] ?? '') ?>" placeholder="Ex: Tribunal de Commerce de Paris">
            </div>

            <h3>3. Réseaux & Localisation</h3>
            <div class="form-group">
                <label for="lien_facebook">Lien Facebook</label>
                <input type="url" id="lien_facebook" name="lien_facebook" value="<?= htmlspecialchars($_POST['lien_facebook'] ?? $prospect['lien_facebook'] ?? '') ?>" placeholder="https://facebook.com/votrepage">
            </div>
            <div class="form-group">
                <label for="lien_instagram">Lien Instagram</label>
                <input type="url" id="lien_instagram" name="lien_instagram" value="<?= htmlspecialchars($_POST['lien_instagram'] ?? $prospect['lien_instagram'] ?? '') ?>" placeholder="https://instagram.com/votrecompte">
            </div>
            <div class="form-group">
                <label for="google_maps_iframe">Google Maps</label>
                <textarea id="google_maps_iframe" name="google_maps_iframe" rows="2" placeholder="Partager votre localisation"><?= htmlspecialchars($_POST['google_maps_iframe'] ?? $prospect['google_maps_iframe'] ?? '') ?></textarea>
            </div>

            <h3>4. Charte Graphique & Médias</h3>
            <div class="form-group">
                <label for="couleur_principale">Couleur Principale</label>
                <input type="color" id="couleur_principale" name="couleur_principale" value="<?= htmlspecialchars($_POST['couleur_principale'] ?? $prospect['couleur_principale'] ?? '#e5829b') ?>">
            </div>
            <div class="form-group">
                <label for="couleur_secondaire">Couleur Secondaire</label>
                <input type="color" id="couleur_secondaire" name="couleur_secondaire" value="<?= htmlspecialchars($_POST['couleur_secondaire'] ?? $prospect['couleur_secondaire'] ?? '#dde9e9') ?>">
            </div>
            <div class="form-group">
                <label for="couleur_texte">Couleur Texte</label>
                <input type="color" id="couleur_texte" name="couleur_texte" value="<?= htmlspecialchars($_POST['couleur_texte'] ?? $prospect['couleur_texte'] ?? '#212529') ?>">
            </div>

            <div class="form-group">
                <label for="image_fond_file">Image de fond (JPG, PNG, WEBP — Max 5 Mo)</label>
                <?php if (!empty($prospect['image_fond_url'])): ?>
                    <small style="display: block; color: #666; margin-bottom: 5px;">Fichier actuel : <?= htmlspecialchars($prospect['image_fond_url']) ?></small>
                <?php endif; ?>
                <input type="file" id="image_fond_file" name="image_fond_file" accept=".jpg, .jpeg, .png, .webp">
            </div>

            <div class="form-group">
                <label for="logo">Votre Logo (JPG, PNG, WEBP — Max 5 Mo)</label>
                <?php if (!empty($prospect['logo_url'])): ?>
                    <small style="display: block; color: #666; margin-bottom: 5px;">Fichier actuel : <?= htmlspecialchars($prospect['logo_url']) ?></small>
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept=".jpg, .jpeg, .png, .webp">
            </div>

            <div class="form-group">
                <label for="image_mediateur">Logo ou attestation du médiateur (JPG, PNG, WEBP — Max 5 Mo)</label>
                <?php if (!empty($prospect['image_mediateur_url'])): ?>
                    <small style="display: block; color: #666; margin-bottom: 5px;">Fichier actuel : <?= htmlspecialchars($prospect['image_mediateur_url']) ?></small>
                <?php endif; ?>
                <input type="file" id="image_mediateur" name="image_mediateur" accept=".jpg, .jpeg, .png, .webp">
            </div>

            <div class="form-group">
                <label for="video_fond_file">Vidéo de fond (MP4, WEBM — Max 20 Mo)</label>
                <?php if (!empty($prospect['video_fond_url'])): ?>
                    <small style="display: block; color: #666; margin-bottom: 5px;">Fichier actuel : <?= htmlspecialchars($prospect['video_fond_url']) ?></small>
                <?php endif; ?>
                <input type="file" id="video_fond_file" name="video_fond_file" accept=".mp4, .webm">
            </div>

            <h3>5. Identifiants Administrateur</h3>
            <div class="form-group">
                <label for="email_admin">E-mail administrateur *</label>
                <input type="email" id="email_admin" name="email_admin" value="<?= htmlspecialchars($_POST['email_admin'] ?? $prospect['email_admin'] ?? '') ?>" required placeholder="admin@votre-boutique.com">
            </div>
            <div class="form-group">
                <label for="mot_de_passe_hash">Mot de passe <?= $est_creation ? '*' : '(Laisser vide pour ne pas modifier)' ?></label>
                <input type="password" id="mot_de_passe_hash" name="mot_de_passe_hash" placeholder=".........."
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}"
                       title="Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.">
            </div>
            <div class="form-group">
                <label for="mot_de_passe_hash_confirm">Confirmer le mot de passe <?= $est_creation ? '*' : '' ?></label>
                <input type="password" id="mot_de_passe_hash_confirm" name="mot_de_passe_hash_confirm" placeholder=".........."
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
                <div id="prev-title" style="font-weight: bold; font-size: 15px; color: #333;"  value="<?= htmlspecialchars($prospect['nom_entreprise'] ?? '') ?>" required placeholder="Votre entreprise"></div>
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
                    <i class="fab fa-facebook"></i>
                    <i class="fab fa-youtube"></i>
                    <i class="fas fa-map-marker-alt"></i>
                </div>
            
                <!-- Liens Légaux -->
                <div style="font-size: 11px; line-height: 1.4; font-weight: 500; color: #e0e0e0;">
                    <span>Mentions Légales</span> &nbsp; <span>Conditions Générales de Vente</span>
                </div>
            </div>

            </div> <!-- Fin de la maquette smartphone -->
            </div> <!-- Fin du conteneur d'aperçu -->

            <button type="submit" style="margin-top: 20px;">Valider et enregistrer</button>
        </form>
    <?php endif; ?>
</div>


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

 
