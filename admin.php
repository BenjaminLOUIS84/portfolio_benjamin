<?php
session_start();
require_once 'config.php';

// Importation des classes Stripe
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

// 1. Authentification
// $mot_de_passe_admin = ADMIN_PASSWORD; // Mot de passe d'accès

if (isset($_GET['action']) && $_GET['action'] === 'deconnexion') {
    unset($_SESSION['admin_logged_in']);
    $_SESSION = array();
    session_destroy();
    header('Location: admin.php');
    exit;
}


// Déconnexion rapide si demandée (?logout=1)
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    session_destroy();
    header('Location: admin.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $mot_de_passe_admin) {
        session_regenerate_id(true); // Sécurisation de la session
        $_SESSION['admin_logged_in'] = true;
    } else {
        $erreur_auth = "Mot de passe incorrect.";
    }
}
// Traitement du formulaire de connexion
$erreur_login = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $user = $_POST['admin_user'] ?? '';
    $pass = $_POST['admin_pass'] ?? '';

    if ($user === ADMIN_USER && password_verify($pass, ADMIN_HASH)) {
        $_SESSION['admin_logged'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $erreur_login = "Identifiant ou mot de passe incorrect.";
    }
}

// Si non connecté, afficher uniquement le formulaire de connexion
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
if (empty($_SESSION['admin_logged'])) {
    ?>


    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion Administrateur</title>
         <style>
        body { font-family: sans-serif; background: #f4f6f8; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 320px; }
        .login-card h2 { margin-top: 0; font-size: 1.25rem; text-align: center; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .4rem; font-size: .9rem; }
        .form-group input { width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: .6rem; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error { color: #d9534f; font-size: .85rem; margin-bottom: 1rem; text-align: center; }
    </style>
    </head>
    <body class="admin-body-login">
        <div class="login-card">
            <h2>Connexion Administrateur</h2>

            <?php if ($erreur_login): ?>
                <p class="admin-error"><?= htmlspecialchars($erreur_login)?></p>
            <?php endif; ?>

            <!--<form method="POST">
                <input type="password" name="password" placeholder="Mot de passe" required class="admin-input"><br>
                <button type="submit" class="admin-btn-primary">Se connecter</button>
            </form>-->
             <form method="POST">
            <div class="form-group">
                <label for="admin_user">Identifiant</label>
                <input type="text" id="admin_user" name="admin_user" required autofocus>
            </div>
            <div class="form-group">
                <label for="admin_pass">Mot de passe</label>
                <input type="password" id="admin_pass" name="admin_pass" required>
            </div>
            <button type="submit" name="login_submit">Se connecter</button>
        </form>
    </div>
        </div>
    </body>
    </html>
<?php
    exit;
}
         

// 2. Traitement du Catalogue
require_once 'db_config.php';
require_once 'vendor/autoload.php';

Stripe::setApiKey(STRIPE_SECRET_KEY);

$message = '';

// --- ACTION SUPPRESSION PRODUIT ---
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_del = (int)$_GET['id'];
   
    // Récupérer le produit pour supprimer l'image et désactiver sur Stripe
    $stmt = $bdd->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([$id_del]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        // 1. Suppression du fichier image local s'il existe et n'est pas l'image par défaut
        if (!empty($prod['image']) && file_exists($prod['image']) && $prod['image'] !== 'images/image.jpg') {
            unlink($prod['image']);
        }
       
        // 2. Désactivation du tarif sur Stripe
        if (!empty($prod['stripe_price_id'])) {
            try {
                Price::update($prod['stripe_price_id'], ['active' => false]);
            } catch (Exception $e) {
                // Ignore l'erreur Stripe si la clé n'existe plus
            }
        }

        // 3. Suppression en BDD
        $stmtDel = $bdd->prepare('DELETE FROM produits WHERE id = ?');
        $stmtDel->execute([$id_del]);

        header('Location: admin.php?msg=supprime');
        exit;
    }
}

// Traitement suppression / archivage commande
if (isset($_GET['action_commande']) && isset($_GET['id_cmd'])) {
    $id_cmd = (int)$_GET['id_cmd'];
    $action = $_GET['action_commande'];

    if ($action === 'archiver') {
        $stmt = $bdd->prepare("UPDATE commandes SET archivee = 1 WHERE id = ?");
        $stmt->execute([$id_cmd]);
    } elseif ($action === 'desarchiver') {
        $stmt = $bdd->prepare("UPDATE commandes SET archivee = 0 WHERE id = ?");
        $stmt->execute([$id_cmd]);
    } elseif ($action === 'supprimer') {
        $stmt = $bdd->prepare("DELETE FROM commandes WHERE id = ?");
        $stmt->execute([$id_cmd]);
    }

    header('Location: admin.php?msg=cmd_ok');
    exit;
}


// --- TRAITEMENT MISE À JOUR STATUT LIVRAISON ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_statut_commande'])) {
    $id_commande = (int)$_POST['commande_id'];
    $nouveau_statut = trim($_POST['statut_livraison']);

    $stmtStatut = $bdd->prepare('UPDATE commandes SET statut_livraison = ? WHERE id = ?');
    $stmtStatut->execute([$nouveau_statut, $id_commande]);

    header('Location: admin.php?msg=statut_updated');
    exit;
}

// Récupération des commandes triées par date récente
// $commandes = $bdd->query('SELECT * FROM commandes ORDER BY date_commande DESC')->fetchAll(PDO::FETCH_ASSOC);

// Récupérer uniquement les commandes non archivées
$commandes = $bdd->query("SELECT * FROM commandes WHERE archivee = 0 ORDER BY id DESC")->fetchAll();

// Optionnel : Récupérer les archivées séparément
$commandes_archivees = $bdd->query("SELECT * FROM commandes WHERE archivee = 1 ORDER BY id DESC")->fetchAll();


// --- RECUPERATION DU PRODUIT A EDITER ---
$produit_a_editer = null;
if (isset($_GET['action']) && $_GET['action'] === 'editer' && isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $stmtEdit = $bdd->prepare('SELECT * FROM produits WHERE id = ?');
    $stmtEdit->execute([$id_edit]);
    $produit_a_editer = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}

// --- TRAITEMENT DU FORMULAIRE (AJOUT OU MODIFICATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['password'])) {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = (float)($_POST['prix'] ?? 0);
    $taux_tva = (float)($_POST['taux_tva'] ?? 20.00);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
 
    if (!empty($nom) && $prix > 0) {
        try {
            if ($product_id) {
                // --- UPDATE D'UN PRODUIT EXISTANT ---
                $stmtCurr = $bdd->prepare('SELECT * FROM produits WHERE id = ?');
                $stmtCurr->execute([$product_id]);
                $currentProd = $stmtCurr->fetch(PDO::FETCH_ASSOC);

                $image = $currentProd['image'];

                // Traitement d'une nouvelle image envoyée
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['image_file']['tmp_name'];
                    $fileName = $_FILES['image_file']['name'];
                    $fileSize = $_FILES['image_file']['size'];

                    if ($fileSize > 5 * 1024 * 1024) {
                        throw new Exception("L'image est trop lourde (5 Mo max).");
                    }

                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                        throw new Exception("Extension d'image non autorisée.");
                    }

                    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                    $uploadFileDir = 'images/';
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        if (!empty($currentProd['image']) && file_exists($currentProd['image']) && $currentProd['image'] !== 'images/image.jpg') {
                            unlink($currentProd['image']);
                        }
                        $image = $dest_path;
                    }
                }

                // Si le prix change, créer un nouveau tarif dans Stripe
                $stripePriceId = $currentProd['stripe_price_id'];
                if (round($prix * 100) !== round((float)$currentProd['prix'] * 100)) {
                    // Récupérer l'ID du produit Stripe depuis l'ancien Price
                    $oldPrice = Price::retrieve($currentProd['stripe_price_id']);
                    $stripeProduct_id = $oldPrice->product;

                    // Création du nouveau tarif
                    $newPrice = Price::create([
                        'unit_amount' => round($prix * 100),
                        'currency' => 'eur',
                        'product' => $stripeProduct_id,
                    ]);

                    // Désactiver l'ancien tarif
                    Price::update($currentProd['stripe_price_id'], ['active' => false]);
                    $stripePriceId = $newPrice->id;
                }

                // Mettre à jour la BDD
                $stmtUp = $bdd->prepare('UPDATE produits SET nom = ?, description = ?, prix = ?, taux_tva = ?, stock = ?, image = ?, stripe_price_id = ? WHERE id = ?');
                $stmtUp->execute([$nom, $description, $prix, $taux_tva, $stock, $image, $stripePriceId, $product_id]);

                header('Location: admin.php?msg=modifie');
                exit;

            } else {
                // --- CRÉATION D'UN NOUVEAU PRODUIT ---
                $image = 'images/image.jpg';

                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['image_file']['tmp_name'];
                    $fileName = $_FILES['image_file']['name'];
                    $fileSize = $_FILES['image_file']['size'];

                    if ($fileSize > 5 * 1024 * 1024) {
                        throw new Exception("L'image est trop lourde (5 Mo max).");
                    }

                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                        throw new Exception("Extension d'image non autorisée.");
                    }

                    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                    $uploadFileDir = 'images/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $image = $dest_path;
                    }
                }

                $stripeProduct = Product::create([
                    'name' => $nom,
                    'description' => $description,
                ]);

                $stripePrice = Price::create([
                    'unit_amount' => round($prix * 100),
                    'currency' => 'eur',
                    'product' => $stripeProduct->id,
                ]);

                $stmt = $bdd->prepare('INSERT INTO produits (nom, description, prix, taux_tva, stock, image, stripe_price_id) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nom, $description, $prix, $taux_tva, $stock, $image, $stripePrice->id]);

                $message = '<p class="admin-success">Produit ajouté avec succès et synchronisé avec Stripe !</p>';
            }
        } catch (Exception $e) {
            $message = '<p class="admin-error">Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    } else {
        $message = '<p class="admin-error">Veuillez remplir correctement le nom et le prix.</p>';
    }
}

// Notifications via GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'supprime') {
        $message = '<p class="admin-success">Produit supprimé avec succès.</p>';
    } elseif ($_GET['msg'] === 'modifie') {
        $message = '<p class="admin-success">Produit mis à jour avec succès !</p>';
    }
}

// AFFICHAGE Récupération de la liste des produits
// $produits = $bdd->query('SELECT * FROM produits ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
// --- CONFIGURATION DE LA PAGINATION DES PRODUITS ---
$produitsParPage = 1; // Nombre de produits affichés par page (modifiable)

// Récupérer le numéro de page actuelle depuis l'URL (ex: admin.php?p_page=2)
$pageActuelleProduits = isset($_GET['p_page']) && is_numeric($_GET['p_page']) ? (int)$_GET['p_page'] : 1;
if ($pageActuelleProduits < 1) {
    $pageActuelleProduits = 1;
}

// 1. Compter le nombre total de produits en BDD
$stmtTotal = $bdd->query("SELECT COUNT(*) FROM produits");
$totalProduits = $stmtTotal->fetchColumn();

// Calculer le nombre total de pages
$totalPagesProduits = ceil($totalProduits / $produitsParPage);
if ($totalPagesProduits < 1) {
    $totalPagesProduits = 1;
}
if ($pageActuelleProduits > $totalPagesProduits) {
    $pageActuelleProduits = $totalPagesProduits;
}

// 2. Calculer le décalage (OFFSET) pour la requête SQL
$offsetProduits = ($pageActuelleProduits - 1) * $produitsParPage;

// 3. Récupérer uniquement les produits de la page courante
$stmtProduits = $bdd->prepare("SELECT * FROM produits ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmtProduits->bindValue(':limit', $produitsParPage, PDO::PARAM_INT);
$stmtProduits->bindValue(':offset', $offsetProduits, PDO::PARAM_INT);
$stmtProduits->execute();
$produits = $stmtProduits->fetchAll(PDO::FETCH_ASSOC);

// AFFICHAGE Récupération de la liste des commandes
// --- CONFIGURATION DE LA PAGINATION DES COMMANDES ---
$commandesParPage = 1; // Nombre de commandes par page

$pageActuelleCommandes = isset($_GET['c_page']) && is_numeric($_GET['c_page']) ? (int)$_GET['c_page'] : 1;
if ($pageActuelleCommandes < 1) {
    $pageActuelleCommandes = 1;
}

// 1. Compter le nombre total de commandes
$stmtTotalCmd = $bdd->query("SELECT COUNT(*) FROM commandes");
$totalCommandes = $stmtTotalCmd->fetchColumn();

$totalPagesCommandes = ceil($totalCommandes / $commandesParPage);
if ($totalPagesCommandes < 1) {
    $totalPagesCommandes = 1;
}
if ($pageActuelleCommandes > $totalPagesCommandes) {
    $pageActuelleCommandes = $totalPagesCommandes;
}

// 2. Calcul du décalage (OFFSET)
$offsetCommandes = ($pageActuelleCommandes - 1) * $commandesParPage;

// 3. Récupérer uniquement les commandes de la page courante
$stmtCommandes = $bdd->prepare("SELECT * FROM commandes ORDER BY date_commande DESC LIMIT :limit OFFSET :offset");
$stmtCommandes->bindValue(':limit', $commandesParPage, PDO::PARAM_INT);
$stmtCommandes->bindValue(':offset', $offsetCommandes, PDO::PARAM_INT);
$stmtCommandes->execute();
$commandes = $stmtCommandes->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Catalogue Boutique</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

    <div class="admin-card-container">
        <div class="admin-header">
            <h1>Espace Administration</h1>
            <a href="admin.php?action=deconnexion" class="btn-logout">Se déconnecter</a>
        </div>
     
        <?= $message ?>

        <form method="POST" class="admin-form" enctype="multipart/form-data">
            <?php if ($produit_a_editer): ?>
                <input type="hidden" name="product_id" value="<?= $produit_a_editer['id'] ?>">
                <h3>Modifier le produit #<?= $produit_a_editer['id'] ?></h3>
            <?php else: ?>
                <h3>Ajouter un nouveau produit</h3>
            <?php endif; ?>
          
            <p>
                <label><strong>Nom de l'article :</strong></label>
                <input type="text" name="nom" value="<?= $produit_a_editer ? htmlspecialchars($produit_a_editer['nom']) : '' ?>" required class="admin-input">
            </p>
         
            <p>
                <label><strong>Description :</strong></label>
                <textarea name="description" rows="3" class="admin-input"><?= $produit_a_editer ? htmlspecialchars($produit_a_editer['description']) : '' ?></textarea>
            </p>
            <p>

                <label><strong>Prix (€) :</strong></label>
                <input type="number" step="0.01" name="prix" value="<?= $produit_a_editer ? $produit_a_editer['prix'] : '' ?>" required class="admin-input">
            </p>

            <p>
            <label for="taux_tva">Taux de TVA :</label>
                <select name="taux_tva" id="taux_tva" required>
                    <option value="20.00" <?php echo (isset($produit_a_editer) && $produit_a_editer['taux_tva'] == 20.00) ? 'selected' : ''; ?>>20 % (Standard)</option>
                    <option value="10.00" <?php echo (isset($produit_a_editer) && $produit_a_editer['taux_tva'] == 10.00) ? 'selected' : ''; ?>>10 % (Restauration / Transport)</option>
                    <option value="5.50"  <?php echo (isset($produit_a_editer) && $produit_a_editer['taux_tva'] == 5.50)  ? 'selected' : ''; ?>>5,5 % (Alimentaire / Livres)</option>
                    <option value="0.00"  <?php echo (isset($produit_a_editer) && $produit_a_editer['taux_tva'] == 0.00)  ? 'selected' : ''; ?>>0 % (Exonéré)</option>
                </select>
            </p>

            <p>
                <div class="form-group">
                    <label for="stock">Stock disponible :</label>
                    <input type="number" name="stock" id="stock" min="0" value="<?php echo isset($produit_a_edit) ? $produit_a_edit['stock'] : 0; ?>" required>
                </div>
            </p>

            <p>
                <label><strong>Photo du produit :</strong></label>
                <input type="file" name="image_file" accept="image/*" <?= $produit_a_editer ? '' : 'required' ?> class="admin-input" style="background:#fff; padding:6px;">
                <?php if ($produit_a_editer && !empty($produit_a_editer['image'])): ?>
                    <small>Image actuelle : <?= htmlspecialchars($produit_a_editer['image']) ?></small>
                <?php endif; ?>
            </p>
         
            <?php if ($produit_a_editer): ?>
                <button type="submit" class="admin-btn-primary" style="background-color: #28a745;">
                    💾 Enregistrer les modifications
                </button>
                <a href="admin.php" style="margin-left: 15px; text-decoration: none; color: #666;">Annuler</a>
            <?php else: ?>
                <button type="submit" class="admin-btn-primary">
                    ➕ Enregistrer le produit
                </button>
            <?php endif; ?>
        </form>

        <h2 id="section-produits">Produits actuellement en boutique</h2>

            <div class="table-responsive">
                <table id="table-produits" class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prix</th>
                            <th>TVA</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produits)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">Aucun produit dans le catalogue pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produits as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                                <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                                <!-- Affichage de la TVA (20.00 % par défaut si colonne vide) -->
                                <td><?php echo number_format($p['taux_tva'] ?? 20.00, 1, ',', ''); ?> %</td>
                                <td> <?php if ($p['stock'] > 0): ?>
                                    <span style="color: green; font-weight: bold;"><?php echo $p['stock']; ?></span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">0 (Rupture)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="admin.php?action=editer&id=<?= $p['id'] ?>" style="margin-right: 8px;">✏️ Modifier</a>
                                    <a href="admin.php?action=supprimer&id=<?= $p['id'] ?>" onclick="return confirm('Supprimer définitivement ce produit ?');" style="color: #dc3545;">🗑️ Supprimer</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div><br>

            <!-- Pagination des produits -->
            <?php if ($totalPagesProduits > 1): ?>
                <div class="pagination">
                    <?php if ($pageActuelleProduits > 1): ?>
                        <a href="admin.php?p_page=<?php echo $pageActuelleProduits - 1; ?>#section-produits" class="btn-page">&laquo; Précédent</a>
                    <?php endif; ?>

                    <?php if ($pageActuelleProduits < $totalPagesProduits): ?>
                        <a href="admin.php?p_page=<?php echo $pageActuelleProduits + 1; ?>#section-produits" class="btn-page">Suivant &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        
        <br><h2 id="section-commandes">Suivi des commandes</h2>

        <div class="table-responsive">
            <table id="table-commandes" class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Email Client</th>
                        <th>Montant</th>
                        <th>Paiement</th>
                        <th>Livraison</th>
                        <th>Facture</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Aucune commande enregistrée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $cmd): ?>
                        <tr>
                            <td><strong>#<?= $cmd['id'] ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                            <td><?= htmlspecialchars($cmd['email_client']) ?></td>
                            <td><strong><?= number_format($cmd['montant_total'], 2, ',', ' ') ?> €</strong></td>
                            <td>
                                <span class="badge badge-active">
                                    <?= htmlspecialchars($cmd['statut_paiement']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($cmd['statut_livraison'] ?? 'En préparation') ?></strong>
                            </td>
                            <td>
                               <!-- Bouton de téléchargement de la facture PDF -->
                                <a href="telecharger-facture.php?id=<?= $cmd['id'] ?>" target="_blank" 
                                tyle="text-decoration: none; font-size: 0.9rem;">📄 PDF
                                </a> 
                            </td>
                            <td>
                                <form method="POST" style="display:inline-flex; gap: 5px;">
                                    <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                                    <select name="statut_livraison" class="admin-input" style="padding: 2px 5px; font-size: 0.85rem;">
                                        <option value="En préparation" <?= ($cmd['statut_livraison'] ?? '') === 'En préparation' ? 'selected' : '' ?>>En préparation</option>
                                        <option value="Expédiée" <?= ($cmd['statut_livraison'] ?? '') === 'Expédiée' ? 'selected' : '' ?>>Expédiée</option>
                                        <option value="Livrée" <?= ($cmd['statut_livraison'] ?? '') === 'Livrée' ? 'selected' : '' ?>>Livrée</option>
                                    </select>
                                    <button type="submit" name="action_statut_commande" class="admin-btn-primary" style="padding: 2px 8px; font-size: 0.85rem;">Mettre à jour</button>
                                </form>
                            </td>
                            <td>
                                <!-- Bouton Archiver -->
                                <a href="admin.php?action_commande=archiver&id_cmd=<?php echo $cmd['id']; ?>"
                                onclick="return confirm('Archiver cette commande ?');"
                                style="color: #d97706; text-decoration: none; margin-right: 8px;">📦 Archiver</a>

                                <!-- Bouton Supprimer -->
                                <a href="admin.php?action_commande=supprimer&id_cmd=<?php echo $cmd['id']; ?>"
                                onclick="return confirm('Attention : supprimer définitivement cette commande ?');"
                                style="color: #dc2626; text-decoration: none;">🗑️ Supprimer</a>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination des commandes -->
        <?php if ($totalPagesCommandes > 1): ?>
            <div class="pagination">
                <?php if ($pageActuelleCommandes > 1): ?>
                    <a href="admin.php?c_page=<?php echo $pageActuelleCommandes - 1; ?><?php echo isset($_GET['p_page']) ? '&p_page='.$_GET['p_page'] : ''; ?>#section-commandes" class="btn-page">&laquo; Précédent</a>
                <?php endif; ?>

                <?php if ($pageActuelleCommandes < $totalPagesCommandes): ?>
                    <a href="admin.php?c_page=<?php echo $pageActuelleCommandes + 1; ?><?php echo isset($_GET['p_page']) ? '&p_page='.$_GET['p_page'] : ''; ?>#section-commandes" class="btn-page">Suivant &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
   
</body>
</html>
