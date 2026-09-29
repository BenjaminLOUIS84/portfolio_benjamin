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
    <meta name="description" content="Conditions Générales de Vente — <?= SITE_NAME ?>">

    <title>Conditions Générales de Vente — <?= SITE_NAME ?></title>

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
    <div class="wrapper">

        <div class="head">
            <div class="name">
                <figure>
                <img class="size" src="images/logoBenjaminLouis.png" alt="Logo Benjamin Louis Développeur Web">
                </figure>
        </div>

    
        <!-- SELECTION DE LA LANGUE FR OU EN -->
        <select aria-label="Sélection de la langue" id="language-selector">
            <option value="fr">FR</option>
            <option value="en">EN</option>
        </select>

        <!-- MENU BURGER -->
        <div class="burger">
            <a href="https://benjaminlouis.eu/menu.html"><img src="./images/burger.png" class="meal" alt="Icône du menu burger" title="Menu"></a>
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
                <h1 id="title4.2" data-fr="Conditions Générales de Vente" data-en="General Terms and Conditions of Sale">Conditions Générales de Vente (CGV)</h1>
            </section>

            <p id="update-date" data-fr="Dernière mise à jour : <?= date('d/m/Y') ?>" data-en="Last update: <?= date('d/m/Y') ?>">Dernière mise à jour : <?= date('d/m/Y') ?></p><br>

            <h3 id="cgv-art1-title" data-fr="Article 1 : Objet et Mentions Légales" data-en="Article 1: Scope and Legal Notices">Article 1 : Objet et Mentions Légales</h3><br>
            <p id="cgv-art1-1"
               data-fr="Les présentes Conditions Générales de Vente (CGV) régissent de manière exclusive les ventes de produits et services effectuées sur le site internet <?= SITE_NAME ?>."
               data-en="These General Terms and Conditions of Sale (GTCS) exclusively govern the sales of products and services on the website <?= SITE_NAME ?>.">
                Les présentes Conditions Générales de Vente (CGV) régissent de manière exclusive les ventes de produits et services effectuées sur le site internet <strong><?= SITE_NAME ?></strong>.
            </p><br>
           
            <p id="cgv-art1-2"
               data-fr="Éditeur : <?= COMPANY_NAME ?> (<?= COMPANY_STATUS ?> au capital de <?= COMPANY_CAPITAL ?>). Siège social : <?= COMPANY_ADDRESS ?>. SIRET : <?= COMPANY_SIRET ?> — <?= COMPANY_RCS ?>. Contact : <?= CONTACT_EMAIL ?> — <?= CONTACT_PHONE ?>."
               data-en="Publisher: <?= COMPANY_NAME ?> (<?= COMPANY_STATUS ?> with capital of <?= COMPANY_CAPITAL ?>). Registered office: <?= COMPANY_ADDRESS ?>. SIRET: <?= COMPANY_SIRET ?> — <?= COMPANY_RCS ?>. Contact: <?= CONTACT_EMAIL ?> — <?= CONTACT_PHONE ?>.">
                <strong>Établissement / Éditeur :</strong> <?= COMPANY_NAME ?><br>
                <strong>Forme juridique :</strong> <?= COMPANY_STATUS ?> au capital de <?= COMPANY_CAPITAL ?><br>
                <strong>Siège social :</strong> <?= COMPANY_ADDRESS ?><br>
                <strong>SIRET / RCS :</strong> <?= COMPANY_SIRET ?> — <?= COMPANY_RCS ?><br>
                <strong>Téléphone :</strong> <?= CONTACT_PHONE ?><br>
                <strong>Contact Client :</strong> <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
            </p><br>

            <h3 id="cgv-art2-title" data-fr="Article 2 : Produits et Services vendus" data-en="Article 2: Products and Services Sold">Article 2 : Produits et Services vendus</h3><br>
            <p id="cgv-art2-desc"
               data-fr="Le site <?= SITE_NAME ?> propose la vente en ligne de produits catalogués dans la section boutique. Les caractéristiques essentielles et leurs prix sont mis à disposition sur les fiches produits."
               data-en="The website <?= SITE_NAME ?> offers online sales of products cataloged in the shop section. Essential characteristics and prices are available on the product pages.">
                Le site <strong><?= SITE_NAME ?></strong> propose la vente en ligne de produits catalogués et présentés dans la section boutique du site.<br>
                Les caractéristiques essentielles des produits et leurs prix respectifs sont mis à disposition de l'acheteur sur les fiches produits.
            </p><br>

            <h3 id="cgv-art3-title" data-fr="Article 3 : Prix et Paiement" data-en="Article 3: Prices and Payment">Article 3 : Prix et Paiement</h3><br>
            <p id="cgv-art3-desc"
               data-fr="Les prix sont indiqués en Euros (€) TTC. Le paiement est exigible immédiatement à la commande et s'effectue de manière sécurisée par carte bancaire via Stripe."
               data-en="Prices are indicated in Euros (€) including taxes. Payment is due immediately upon order and is securely processed by credit card via Stripe.">
                Les prix de nos produits sont indiqués en Euros (€) Toutes Taxes Comprises (TTC).<br>
                Le paiement est exigible immédiatement à la commande. Le règlement s'effectue de manière entièrement sécurisée par carte bancaire via notre prestataire de paiement <strong>Stripe</strong>.
            </p><br>

            <h3 id="cgv-art4-title" data-fr="Article 4 : Droit de Rétractation et Garanties" data-en="Article 4: Right of Withdrawal and Warranties">Article 4 : Droit de Rétractation et Garanties</h3><br>
            <p id="cgv-art4-1"
               data-fr="4.1 Droit de rétractation : Conformément à l'article L.221-18 du Code de la consommation, le client dispose d'un délai de 14 jours francs pour exercer son droit de rétractation. Pour les contenus numériques, l'accès immédiat fourni après achat vaut renonciation expresse à ce droit."
               data-en="4.1 Right of withdrawal: In accordance with Article L.221-18 of the Consumer Code, the customer has 14 clear days to exercise their right of withdrawal. For digital content, immediate access provided after purchase constitutes an express waiver of this right.">
                <strong>4.1 Droit de rétractation :</strong> Conformément à l’article L.221-18 du Code de la consommation, le client dispose d'un délai de quatorze (14) jours francs à compter de la réception des produits pour exercer son droit de rétractation.<br>
                Pour les produits numériques ou contenus téléchargeables, l'accès immédiat fourni après achat vaut renonciation expresse au droit de rétractation conformément à l'article L.221-28 du même Code.
            </p><br>

            <p id="cgv-art4-2"
               data-fr="4.2 Garanties légales : Le client bénéficie de la garantie légale de conformité (art. L.217-4 et suiv. du Code de la consommation) et des vices cachés (art. 1641 et suiv. du Code civil)."
               data-en="4.2 Legal warranties: The customer benefits from the legal warranty of conformity and against hidden defects.">
                <strong>4.2 Garanties légales :</strong> Le client bénéficie de la garantie légale de conformité (articles L.217-4 et suivants du Code de la consommation) et de la garantie contre les vices cachés (articles 1641 et suivants du Code civil).
            </p><br>

            <h3 id="cgv-art5-title" data-fr="Article 5 : Protection des données et Preuve de commande" data-en="Article 5: Data Protection and Proof of Order">Article 5 : Protection des données et Preuve de commande</h3><br>
            <p id="cgv-art5-desc"
               data-fr="En cochant la case d'acceptation des CGV lors du processus de commande, le client accepte que ses données de validation (adresse IP, horodatage) soient conservées de manière sécurisée comme preuve électronique de la transaction conformément au RGPD."
               data-en="By checking the GTCS acceptance box during checkout, the customer agrees that validation data (IP address, timestamp) is securely stored as electronic proof of transaction in accordance with GDPR.">
                Afin de sécuriser les transactions et de se prémunir contre les litiges, le site applique les règles de la preuve électronique.<br>
                En cochant la case d'acceptation des présentes CGV lors du processus de commande, le client accepte que ses données de validation (adresse IP, horodatage) soient conservées de manière sécurisée dans la base de données du site.<br>
                Conformément au RGPD, ces informations sont conservées à des fins de preuve légale et ne seront en aucun cas cédées à des tiers.
            </p><br>

            <h3 id="cgv-art6-title" data-fr="Article 6 : Médiation et Loi applicable" data-en="Article 6: Mediation and Applicable Law">Article 6 : Médiation et Loi applicable</h3><br>
            <p id="cgv-art6-1"
               data-fr="6.1 Médiation : En cas de litige non résolu de manière amiable, le consommateur peut recourir gratuitement au service de médiation auquel adhère l'entreprise : <?= MEDIATOR_NAME ?> (<?= MEDIATOR_URL ?>)."
               data-en="6.1 Mediation: In case of unresolved dispute, the consumer may use the free mediation service: <?= MEDIATOR_NAME ?> (<?= MEDIATOR_URL ?>).">
                <strong>6.1 Médiation :</strong> En cas de litige non résolu de manière amiable avec le service client, le consommateur peut recourir gratuitement au service de médiation auquel adhère l'entreprise : <strong><?= MEDIATOR_NAME ?></strong> (<a href="<?= MEDIATOR_URL ?>" target="_blank" rel="noopener"><?= MEDIATOR_URL ?></a>).
            </p><br>

            <p id="cgv-art6-2"
               data-fr="6.2 Loi applicable : Les présentes CGV sont soumises à la loi française. Les tribunaux français seront seuls compétents."
               data-en="6.2 Applicable law: These GTCS are governed by French law. French courts shall have exclusive jurisdiction.">
                <strong>6.2 Loi applicable :</strong> Les présentes CGV sont soumises à la loi française. En cas de litige, et à défaut d'accord amiable, les tribunaux français seront seuls compétents.
            </p><br>

            <figure>
                <img class="icoDS" src="images/cm2c.jpg" alt="Logo de la CM2C" title="Logo de la CM2C">
            </figure>

            <br><a href="index.php" class="btn-principal" id="title10.2" data-fr="Retour" data-en="Back">Retour</a>

            <footer class="site-footer">
                <div class="footer-container">

                    <div class="footer-links">
                        <a href="mentions.php" id="title3.1" class="mentions" data-fr="Mentions Légales" data-en="Legal Notices">Mentions Légales</a>
                        <a href="cgv.php" id="title3.2" class="mentions" data-fr="  Conditions Générales de Vente" data-en="  General Terms and Conditions">  Conditions Générales de Vente</a>
                    </div>

                </div>
            </footer>
        </section>

    </main>

    <script src="js/script.js"></script>

</body>

</html>
