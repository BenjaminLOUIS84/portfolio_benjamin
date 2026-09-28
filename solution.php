<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr" style="height: auto !important; overflow-y: auto !important;">
<head>
    <meta charset="utf-8">
    <title>Solution E-commerce Clé en Main — 990€ HT | Benjamin Louis</title>
    <meta name="description" content="Obtenez votre boutique en ligne professionnelle clé en main pour 990€ HT. Déploiement rapide et paiement sécurisé.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<link rel="stylesheet" href="style.css">-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="canonical" href="https://benjaminlouis.eu/solution.php">

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
<body style="height: auto !important; overflow-y: auto !important; background-color: #f4f6f9 !important; color: #1a202c !important; font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0;">

<div style="max-width: 1200px; margin: 0 auto; padding: 15px;">

    <!-- En-tête -->
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
        
        <div class="lang-switch">
            <button id="lang-btn" onclick="toggleLanguage()">🇬🇧 EN</button>
        </div>
        
        <nav>
            <a href="index.html" style="color: #2b6cb0; text-decoration: none; font-weight: bold; margin-right: 15px;" data-fr="Accueil" data-en="Home">Accueil</a>
            <a href="https://benjaminlouis.eu/a-propos.html" style="color: #2b6cb0; text-decoration: none; font-weight: bold; margin-right: 15px;"  data-fr="Accueil" data-en="About">À propos</a>
            <a href="https://benjaminlouis.eu/portfolio.html" style="color: #2b6cb0; text-decoration: none; font-weight: bold; margin-right: 15px;" data-fr="Portfolio" data-en="Portfolio">Portfolio</a>
        </nav>
    </header>

    <!-- Titre -->
    <main style="padding: 20px 0;">
        <section style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #1a202c !important; font-size: 1.8rem; margin-bottom: 10px;">Votre Boutique E-commerce Clé en Main</h1>
            <p style="color: #4a5568 !important; font-size: 1.1rem; margin: 0;">
                Une solution complète et prête à vendre pour <strong>990 € HT</strong>.
            </p>
        </section>

        <!-- Formulaire isolé -->
        <section style="max-width: 500px; margin: 0 auto; background: #ffffff !important; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
            <h2 style="color: #1a202c !important; text-align: center; margin-top: 0; margin-bottom: 20px; font-size: 1.3rem;">Commander votre solution</h2>
           
            <form action="checkout.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="client_nom" style="display: block; color: #2d3748 !important; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Nom complet ou Société *</label>
                    <input type="text" id="client_nom" name="client_nom" required placeholder="Ex: Jean Dupont" style="width: 100%; padding: 10px; background: #fff !important; color: #000 !important; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 0.95rem;">
                </div>

                <div>
                    <label for="client_email" style="display: block; color: #2d3748 !important; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Adresse E-mail *</label>
                    <input type="email" id="client_email" name="client_email" required placeholder="jean@exemple.fr" style="width: 100%; padding: 10px; background: #fff !important; color: #000 !important; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 0.95rem;">
                </div>

                <div>
                    <label for="domaine_souhaite" style="display: block; color: #2d3748 !important; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Nom de domaine souhaité</label>
                    <input type="text" id="domaine_souhaite" name="domaine_souhaite" placeholder="maboutique.com" style="width: 100%; padding: 10px; background: #fff !important; color: #000 !important; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 0.95rem;">
                </div>

                <div style="margin-top: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; color: #4a5568 !important; font-size: 0.85rem; line-height: 1.4; cursor: pointer;">
                        <input type="checkbox" name="cgv_acceptees" value="1" required style="margin-top: 3px; width: 16px; height: 16px;">
                        <span>
                            J'accepte les <a href="cgv.php" target="_blank" style="color: #3182ce;">Conditions Générales de Vente</a> et je demande l'exécution immédiate du service, renonçant expressément à mon droit de rétractation.
                        </span>
                    </label>
                </div>

                <button type="submit" style="width: 100%; padding: 12px; background: #3182ce; color: #ffffff; font-weight: bold; font-size: 1rem; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">
                    Procéder au paiement (990 € HT)
                </button>
            </form>
        </section>
    </main>

    <!-- Pied de page -->
    <footer style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 0.85rem; color: #718096;">
        <!--<div style="margin-bottom: 10px;">
            <a href="#" style="color: #4a5568; margin: 0 5px;"><i class="fab fa-facebook"></i></a>
            <a href="#" style="color: #4a5568; margin: 0 5px;"><i class="fab fa-youtube"></i></a>
        </div>-->
        <div>
            <a href="mentions.php" style="color: #4a5568; text-decoration: none; margin: 0 10px;">Mentions Légales</a> |
            <a href="cgv.php" style="color: #4a5568; text-decoration: none; margin: 0 10px;">Conditions Générales de Vente</a>
        </div>
    </footer>

</div>

<script src="js/script.js"></script>
</body>
</html>