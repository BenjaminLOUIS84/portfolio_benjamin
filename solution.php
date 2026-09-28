<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Solution E-commerce Clé en Main — 990€ HT | Benjamin Louis</title>
    <meta name="description" content="Obtenez votre boutique en ligne professionnelle clé en main pour 990€ HT. Déploiement rapide et paiement sécurisé.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="canonical" href="https://benjaminlouis.eu/">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "name": "Création de Boutique E-commerce Clé en Main",
      "provider": {
        "@type": "Organization",
        "name": "<?= SITE_NAME ?>",
        "url": "https://benjaminlouis.eu"
      },
      "offers": {
        "@type": "Offer",
        "price": "990.00",
        "priceCurrency": "EUR"
      }
    }
    </script>
</head>
<body>
<div id="wrapper">
    <header class="site-header">
        <div class="burger-menu">
            <a href="menu.html">
                <img src="./images/burger.png" alt="Menu" class="burger-img">
            </a>
        </div>
        <nav class="menu">
            <a href="index.php">Accueil</a>
            <a href="https://benjaminlouis.eu/a-propos.html">À propos</a>
        </nav>
    </header>

    <main class="main-content">
        <section class="boutique-intro">
            <h1>Votre Boutique E-commerce Clé en Main</h1>
            <p class="subtitle">
                Une solution complète et prête à vendre pour <strong>990 € HT</strong>.
            </p>
        </section>

        <!-- Zone de commande autonome -->
        <section class="commande-card">
            <h2>Commander votre solution</h2>
           
            <form action="checkout.php" method="POST" class="commande-form">
                <div class="form-group">
                    <label for="client_nom">Nom complet ou Société *</label>
                    <input type="text" id="client_nom" name="client_nom" required placeholder="Ex: Jean Dupont">
                </div>

                <div class="form-group">
                    <label for="client_email">Adresse E-mail *</label>
                    <input type="email" id="client_email" name="client_email" required placeholder="jean@exemple.fr">
                </div>

                <div class="form-group">
                    <label for="domaine_souhaite">Nom de domaine souhaité</label>
                    <input type="text" id="domaine_souhaite" name="domaine_souhaite" placeholder="maboutique.com">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="cgv_acceptees" value="1" required>
                        <span>
                            J'accepte les <a href="cgv.php" target="_blank">Conditions Générales de Vente</a> et je demande l'exécution immédiate du service, renonçant expressément à mon droit de rétractation.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    Procéder au paiement (990 € HT)
                </button>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-icons">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
            <div class="footer-links">
                <a href="mentions.php">Mentions Légales</a>
                <a href="cgv.php">Conditions Générales de Vente</a>
            </div>
        </div>
    </footer>
</div>

<script src="js/script.js"></script>
</body>
</html>
