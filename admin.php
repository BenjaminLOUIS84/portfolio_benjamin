<?php
session_start();
require_once 'config.php';

// Traitement de la déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_subdomain']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Traitement de la connexion
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password) && isset($bdd)) {
        $stmt = $bdd->prepare("SELECT * FROM admin_users WHERE username = :user LIMIT 1");
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification sécurisée du mot de passe haché
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged']    = true;
            $_SESSION['admin_user']      = $user['username'];
            $_SESSION['admin_subdomain'] = $user['subdomain'] ?? null; // Stockage du sous-domaine
            header('Location: admin.php');
            exit;
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Si NON connecté : Affichage du formulaire de connexion
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 340px; }
        h2 { margin-top: 0; font-size: 20px; text-align: center; color: #2d3748; }
        .field { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 14px; color: #4a5568; }
        input { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3182ce; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #2b6cb0; }
        .err { background: #fed7d7; color: #9b2c2c; padding: 10px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Administration</h2>
        <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Se connecter</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
endif;

// --- ZONE CONNECTÉE : Tableau de bord ---

$user_subdomain = $_SESSION['admin_subdomain'] ?? null;
$is_super_admin = ($_SESSION['admin_user'] === 'admin'); // Votre compte principal voit tout

// Traitement de l'archivage
if (isset($_GET['action']) && $_GET['action'] === 'archiver' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($is_super_admin) {
        $stmt = $bdd->prepare("UPDATE commandes SET statut = 'Archivée' WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $bdd->prepare("UPDATE commandes SET statut = 'Archivée' WHERE id = ? AND subdomain = ?");
        $stmt->execute([$id, $user_subdomain]);
    }
    header('Location: admin.php#section-commandes');
    exit;
}

// Traitement de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($is_super_admin) {
        $stmt = $bdd->prepare("DELETE FROM commandes WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $bdd->prepare("DELETE FROM commandes WHERE id = ? AND subdomain = ?");
        $stmt->execute([$id, $user_subdomain]);
    }
    header('Location: admin.php#section-commandes');
    exit;
}

// Récupération des commandes avec filtrage par sous-domaine
$search = trim($_GET['search_commande'] ?? '');
$commandes = [];

if (isset($bdd)) {
    $sql = "SELECT c.*, p.statut_deploiement, p.id AS config_id
            FROM commandes c
            LEFT JOIN prospects_configurations p ON c.id = p.commande_id
            WHERE 1=1";
    $params = [];

    // Si ce n'est PAS le super-admin global, on filtre la liste par sous-domaine
    if (!$is_super_admin) {
        $sql .= " AND c.subdomain = :subdomain";
        $params[':subdomain'] = $user_subdomain;
    }

    if (!empty($search)) {
        $sql .= " AND (c.client_nom LIKE :search OR c.client_email LIKE :search OR c.domaine_souhaite LIKE :search OR c.id = :id_exact)";
        $params[':search'] = '%' . $search . '%';
        $params[':id_exact'] = is_numeric($search) ? (int)$search : 0;
    }

    $sql .= " ORDER BY c.date_commande DESC";

    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Admin - Commandes</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .top { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { margin: 0; font-size: 20px; }
        .btn-out { color: #ef4444; text-decoration: none; padding: 8px 14px; border: 1px solid #ef4444; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .btn-out:hover { background: #ef4444; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f1f5f9; color: #475569; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-weight: 600; font-size: 12px; }
        .badge-payee { background: #dcfce7; color: #166534; }
        .badge-attente { background: #fef3c7; color: #92400e; }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .top {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="top">
            <h1>Commandes benjaminlouis.eu</h1>
            <div>
                <span style="margin-right: 15px; font-size: 14px; color: #64748b;">Connecté : <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
                <a href="admin.php?action=logout" class="btn-out">Déconnexion 🚪</a>
            </div>
        </div>
       
        <div class="table-responsive">

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <form method="GET" action="admin.php" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="search_commande" placeholder="Rechercher par nom, email, domaine..."
                        value="<?= htmlspecialchars($_GET['search_commande'] ?? '') ?>"
                        style="padding: 8px 12px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
               
                    <button type="submit" style="padding: 8px 15px; background-color: #3182ce; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        🔍 Rechercher
                    </button>
               
                    <?php if (!empty($_GET['search_commande'])): ?>
                        <a href="admin.php" style="padding: 8px 12px; background-color: #e2e8f0; color: #2d3748; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">
                            ✖ Réinitialiser
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Nom / Client</th>
                        <th>E-mail</th>
                        <th>Domaine</th>
                        <th>Option Int.</th>
                        <th>Montant HT</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                        <tr><td colspan="9" style="text-align:center; padding: 30px; color: #64748b;">Aucune commande pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><strong>#<?= $cmd['id'] ?></strong></td>
                                <td><?= htmlspecialchars($cmd['client_nom']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($cmd['client_email']) ?>"><?= htmlspecialchars($cmd['client_email']) ?></a></td>
                                <td><code><?= htmlspecialchars($cmd['domaine_souhaite']) ?></code></td>
                                <td><?= $cmd['option_multilingue'] ? '✅ Oui' : '❌ Non' ?></td>
                                <td><strong><?= number_format($cmd['montant_ht'], 2, ',', ' ') ?> €</strong></td>
                                <td>
                                    <?php if ($cmd['statut'] === 'paye'): ?>
                                        <span class="badge badge-paye">Payée</span>
                                    <?php else: ?>
                                        <span class="badge badge-attente">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>

                                <td style="display: flex; gap: 5px; align-items: center;">

                                    <a href="config-boutique.php?id=<?= $commande['id'] ?>" class="btn btn-warning btn-sm">Modifier Config</a>

                                    <a href="facture.php?slug=<?= $cmd['slug'] ?>" target="_blank" style="background-color: #2b6cb0; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                        📄 Facture PDF
                                    </a>


                                    <?php if (!empty($cmd['config_id'])): ?>
                                        <?php if ($cmd['statut_deploiement'] === 'en_attente'): ?>
                                            <a href="deployer.php?commande_id=<?= $cmd['id'] ?>" onclick="return confirm('Lancer le déploiement de cette boutique ?');" style="padding: 4px 8px; background-color: #38a169; color: white; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                                🚀 Déployer
                                            </a>
                                        <?php else: ?>
                                            <span style="padding: 4px 8px; background-color: #c6f6d5; color: #22543d; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                                ✅ Déployé
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="padding: 4px 8px; background-color: #edf2f7; color: #718096; border-radius: 4px; font-size: 0.85rem;" title="En attente des infos du client">
                                            ⏳ Attente config
                                        </span>
                                    <?php endif; ?>

                                    <!-- Lien pour ouvrir le site avec la config du client en mode prévisualisation -->
                                    <a href="https://boutique.benjaminlouis.eu/index.php?preview_subdomain=<?= urlencode($cmd['domaine_souhaite']) ?>" target="_blank" style="padding: 5px 10px; background-color: #17a2b8; color: white; border-radius: 4px; text-decoration: none;">
                                        👁️ Aperçu
                                    </a>
                                    

                                    <?php if (($cmd['statut'] ?? '') !== 'Archivée'): ?>
                                        <a href="admin.php?action=archiver&id=<?= $cmd['id'] ?>" onclick="return confirm('Archiver la commande #<?= $cmd['id'] ?> ?');" style="padding: 4px 8px; background-color: #718096; color: white; text-decoration: none; border-radius: 4px; font-size: 0.85rem;" title="Archiver">
                                            📦
                                        </a>
                                    <?php endif; ?>

                                    <a href="admin.php?action=supprimer&id=<?= $cmd['id'] ?>" onclick="return confirm('Supprimer définitivement la commande #<?= $cmd['id'] ?> ?');" style="padding: 4px 8px; background-color: #e53e3e; color: white; text-decoration: none; border-radius: 4px; font-size: 0.85rem;" title="Supprimer">
                                        🗑️
                                    </a>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>