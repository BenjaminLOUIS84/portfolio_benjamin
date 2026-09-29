<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://benjaminlouis.eu/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="robots" content="index, follow">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cette page expose les mentions légales de ce site web">

    <title>Mentions Légales — <?= SITE_NAME ?></title>

    <script>
        let currentLang = 'fr';

        function toggleLanguage() {
            currentLang = currentLang === 'fr' ? 'en' : 'fr';
            const langBtn = document.getElementById('lang-btn');

            if (langBtn) {
                langBtn.textContent = currentLang === 'fr' ? '🇬🇧 EN' : '🇫🇷 FR';
            }

            // Met à jour tous les éléments contenant data-fr et data-en
            document.querySelectorAll('[data-fr][data-en]').forEach(el => {
                el.textContent = el.getAttribute(`data-${currentLang}`);
            });

            // Gestion spécifique pour la valeur du bouton d'envoi du formulaire
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) {
                submitBtn.value = currentLang === 'fr' ? 'ENVOYER' : 'SEND';
            }
        }
    </script>
</head>

<body>
    <div id="wrapper">

        <div class="head">
            <div class="name">
                <figure>
                <img class="size" src="images/logoBenjaminLouis.png" alt="Logo Benjamin Louis Développeur Web">
                </figure>
        </div>

        <!-- Sélecteur de Langue -->
        <div class="lang-switch">
            <button id="lang-btn" onclick="toggleLanguage()">🇬🇧 EN</button>
        </div>

        <!-- MENU BURGER -->
        <div class="burger">
            <a href="https://benjaminlouis.eu/menu.html"><img src="./images/burger.png" class="meal" alt="Menu" title="Menu"></a>
        </div>
        </div>

        <header>
        <nav>
            <div class="menu">
            <a href="https://benjaminlouis.eu/index.html" data-fr="Accueil" data-en="Home">Accueil</a>
            <a href="https://benjaminlouis.eu/a-propos.html" data-fr="À propos" data-en="About">À propos</a>
            <a href="https://benjaminlouis.eu/portfolio.html" data-fr="Portfolio" data-en="Portfolio">Portfolio</a>
            <a href="https://benjaminlouis.eu/solution.php" data-fr="Boutique" data-en="Shop">Boutique</a>
            </div>
        </nav>
        </header>

    </div>

    <main>

        <section class="portfolio-section-photos">

            <section class="accueilPortfolio">
                <h1 data-fr="Mentions Légales" data-en="Legal Notices">Mentions Légales</h1>
            </section>

            <h2 data-fr="Notre Entreprise" data-en="Our Company">Notre Entreprise</h2>

            <div class="blocProjet">
                <div class="lien-conteneur-photoGlace">
                    <figure>
                        <img class="photoGlace" src="images/logo.png" alt="Logo de <?= COMPANY_NAME ?>">
                    </figure>
                </div>
            </div>

            <h2 id="title14" data-fr="Le Site Web" data-en="The Web Site">Le Site Web</h2>

            <a href="<?= DEV_SITE ?>" target="_blank" rel="noopener"><img class="icoDS" src="images/photoBenjaminLouis.png" alt="Lien vers le site de <?= DEV_NAME ?>" title="Site Web de <?= DEV_NAME ?>"></a>
            <p id="title5.4" data-fr="Ce Site Web a été réalisé par <?= DEV_NAME ?> (<?= DEV_COMPANY ?>) et est hébergé par <?= HOST_NAME ?>." data-en="This website was created by <?= DEV_NAME ?> (<?= DEV_COMPANY ?>) and is hosted by <?= HOST_NAME ?>.">
                Ce Site Web a été réalisé par <?= DEV_NAME ?> (<?= DEV_COMPANY ?>) et est hébergé par <?= HOST_NAME ?>.
            </p>

            <p id="title5.5" data-fr="En vertu de l’Article 6 de la Loi n° 2004-575 du 21 juin 2004 pour la confiance dans l’économie numérique, il est précisé dans cet article l’identité des différents intervenants dans le cadre de sa réalisation et de son suivi." data-en="Pursuant to Article 6 of Law No. 2004-575 of 21 June 2004 on confidence in the digital economy, this article specifies the identity of the various parties involved in its implementation and monitoring.">
                En vertu de l’Article 6 de la Loi n° 2004-575 du 21 juin 2004 pour la confiance dans l’économie numérique, il est précisé dans cet article l’identité des différents intervenants dans le cadre de sa réalisation et de son suivi.
            </p><br>

            <h3 id="art1-title" data-fr="ARTICLE 1 - Informations légales" data-en="ARTICLE 1 - Legal Information">ARTICLE 1 - Informations légales</h3><br>
            <p id="art1-editor"
               data-fr="Le site web <?= SITE_NAME ?> est édité par : <?= COMPANY_NAME ?> (<?= COMPANY_STATUS ?>) au capital de <?= COMPANY_CAPITAL ?>. Directeur de la publication : <?= COMPANY_DIRECTOR ?>. Immatriculée sous le SIRET <?= COMPANY_SIRET ?> (<?= COMPANY_RCS ?>). Adresse : <?= COMPANY_ADDRESS ?>. Téléphone : <?= CONTACT_PHONE ?>. Email : <?= CONTACT_EMAIL ?>."
               data-en="The website <?= SITE_NAME ?> is published by: <?= COMPANY_NAME ?> (<?= COMPANY_STATUS ?>) with a capital of <?= COMPANY_CAPITAL ?>. Publication Director: <?= COMPANY_DIRECTOR ?>. Registered under SIRET <?= COMPANY_SIRET ?> (<?= COMPANY_RCS ?>). Address: <?= COMPANY_ADDRESS ?>. Phone: <?= CONTACT_PHONE ?>. Email: <?= CONTACT_EMAIL ?>.">
                Le site web <strong><?= SITE_NAME ?></strong> est édité par :<br>
                <strong><?= COMPANY_NAME ?></strong> (<?= COMPANY_STATUS ?>) au capital de <?= COMPANY_CAPITAL ?><br>
                Directeur de la publication : <strong><?= COMPANY_DIRECTOR ?></strong><br>
                SIRET : <?= COMPANY_SIRET ?> — <?= COMPANY_RCS ?><br>
                Adresse du siège social : <?= COMPANY_ADDRESS ?><br>
                Téléphone : <?= CONTACT_PHONE ?><br>
                Email : <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
            </p><br>

            <p id="art1-dev"
               data-fr="Conception et réalisation : Ce site web a été conçu et développé par <?= DEV_NAME ?>, gérant de <?= DEV_COMPANY ?>."
               data-en="Design and development: This website was designed and developed by <?= DEV_NAME ?>, manager of <?= DEV_COMPANY ?>.">
                <strong>Conception et réalisation :</strong><br>
                Ce site web a été conçu et développé par <strong><?= DEV_NAME ?></strong>, gérant de <strong><?= DEV_COMPANY ?></strong> (<a href="<?= DEV_SITE ?>" target="_blank" rel="noopener"><?= DEV_SITE ?></a>).
            </p><br>

            <p id="art1-host"
               data-fr="Hébergeur du Site : <?= HOST_NAME ?>, <?= HOST_ADDRESS ?>."
               data-en="Website Host: <?= HOST_NAME ?>, <?= HOST_ADDRESS ?>.">
                <strong>Hébergeur du Site :</strong><br>
                <strong><?= HOST_NAME ?></strong><br>
                <?= HOST_ADDRESS ?>
            </p><br>

            <h3 id="art2-title" data-fr="ARTICLE 2 - Accessibilité" data-en="ARTICLE 2 - Accessibility">ARTICLE 2 - Accessibilité</h3><br>
            <p id="art2-content"
               data-fr="Le Site est accessible aux utilisateurs 24/24h et 7/7j sauf interruption, programmée ou non, pour maintenance ou force majeure. Le Site ne saurait être tenu pour responsable de tout dommage résultant de son indisponibilité."
               data-en="The Site is accessible 24/7 except for scheduled or unscheduled interruptions for maintenance or force majeure. The Site cannot be held responsible for any damage resulting from its unavailability.">
                Le Site est par principe accessible aux utilisateurs 24/24h et 7/7j sauf interruption, programmée ou non, pour des besoins de maintenance ou en cas de force majeure.<br>
                En cas d’impossibilité d’accès au Site, celui-ci s’engage à faire son maximum afin d’en rétablir l’accès. Le Site ne saurait être tenu pour responsable de tout dommage, quelle qu’en soit la nature, résultant de son indisponibilité.
            </p><br>

            <h3 id="art3-title" data-fr="ARTICLE 3 - Collecte de données et RGPD" data-en="ARTICLE 3 - Data Collection & GDPR">ARTICLE 3 - Collecte de données et RGPD</h3><br>
            <p id="art3-content"
               data-fr="Le site recueille des données nécessaires à la gestion des commandes. Conformément au RGPD et à la loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification et de suppression en écrivant à : <?= CONTACT_EMAIL ?>."
               data-en="The site collects data necessary for order management. In accordance with GDPR, you have the right to access, rectify, and delete your data by writing to: <?= CONTACT_EMAIL ?>.">
                Le site web de <strong><?= COMPANY_NAME ?></strong> est une boutique en ligne permettant de commander des produits. De ce fait, le site recueille des données nécessaires à la gestion des commandes et à la relation client.<br><br>
                Conformément au Règlement général sur la protection des données (RGPD) et à la loi « Informatique et Libertés », vous disposez d’un droit d’accès, de rectification et de suppression des données vous concernant par email : <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
            </p><br>

            <h3 id="art4-title" data-fr="ARTICLE 4 - Politique de cookies" data-en="ARTICLE 4 - Cookie Policy">ARTICLE 4 - Politique de cookies</h3><br>
            <p id="art4-content"
               data-fr="Le site peut utiliser des cookies nécessaires au bon fonctionnement de la boutique et de la session panier."
               data-en="The site may use cookies necessary for the proper functioning of the shop and shopping cart session.">
                Le site peut utiliser des cookies nécessaires au bon fonctionnement de la boutique et de la session de panier. Les utilisateurs peuvent configurer leur navigateur pour refuser ou gérer les cookies.
            </p><br>

            <h3 id="art5-title" data-fr="ARTICLE 5 - Médiation de la consommation" data-en="ARTICLE 5 - Consumer Mediation">ARTICLE 5 - Médiation de la consommation</h3><br>
            <p id="art5-content"
               data-fr="En cas de litige, vous pouvez recourir gratuitement au service de médiation <?= MEDIATOR_NAME ?> (<?= MEDIATOR_URL ?>)."
               data-en="In case of dispute, you may use the free mediation service <?= MEDIATOR_NAME ?> (<?= MEDIATOR_URL ?>).">
                En cas de litige non résolu de manière amiable, le consommateur peut recourir gratuitement au service de médiation auquel adhère l'entreprise : <strong><?= MEDIATOR_NAME ?></strong> (<a href="<?= MEDIATOR_URL ?>" target="_blank" rel="noopener"><?= MEDIATOR_URL ?></a>).
            </p><br>

            <div class="blocProjet">
                <div class="lien-conteneur-photoGlace">
                    <figure>
                        <a href="https://www.cm2c.net"><img class="photoGlace" src="images/cm2c.jpg" alt="Logo de la CM2C" title="Logo de la CM2C">
                    </figure>
               </div>
            </div>

            <p id="update-date" data-fr="Dernière mise à jour : <?= date('d/m/Y') ?>" data-en="Last update: <?= date('d/m/Y') ?>">Dernière mise à jour : <?= date('d/m/Y') ?></p><br>

            <a href="https://benjaminlouis.eu/index.html" class="btn-principal" data-fr="Retour" data-en="Back">Retour</a>

            <footer class="site-footer">
                <div class="footer-container">

                    <div class="footer-links">
                        <a href="https://benjaminlouis.eu/mentions.php" class="mentions" data-fr="Mentions Légales" data-en="Legal Notices">Mentions Légales</a>
                        <a href="https://benjaminlouis.eu/cgv.php" class="mentions" data-fr="  Conditions Générales de Vente" data-en="  General Terms and Conditions">  Conditions Générales de Vente</a>
                    </div>
                </div>
            </footer>
        </section>

    </main>

    <script src="js/script.js"></script>

</body>

</html>
