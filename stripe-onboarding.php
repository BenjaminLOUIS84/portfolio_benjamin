<?php
require_once __DIR__ . '/db_config.php';
?> 

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Activation de votre compte marchand</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div style="max-width:500px; margin:50px auto; text-align:center; font-family:Arial;">
        <h2>Activation de votre compte marchand</h2>
        <p>Pour continuer, veuillez accepter les conditions générales de Stripe.</p>
        
        <form id="onboarding-form" action="/connect-stripe.php" method="POST">
            <input type="hidden" name="commande_id" value="<?= htmlspecialchars($_GET['commande_id'] ?? '') ?>">
            <input type="hidden" name="stripe_token" id="stripe_token">
            
            <label>
                <input type="checkbox" id="tos" required>
                J'accepte les <a href="https://stripe.com/fr/legal" target="_blank">conditions générales de Stripe</a>
            </label>
            <br><br>
            <button type="submit" id="submit-btn" style="padding:10px 20px; background:#635bff; color:white; border:none; border-radius:5px; cursor:pointer;">
                Continuer vers l'activation
            </button>
        </form>
    </div>

   <script>

    console.log('Script chargé');
    const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
    console.log('Clé publique :', '<?= STRIPE_PUBLIC_KEY ?>');
    console.log('Stripe initialisé');
    

    document.getElementById('onboarding-form').addEventListener('submit', async function(e) {
        console.log('Formulaire soumis');
        e.preventDefault();
        
        console.log('Formulaire soumis');

        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Chargement...';

        try {
            console.log('Tentative création token...');
            
            const result = await stripe.createToken('account', {
                tos_shown_and_accepted: true,
            });

            console.log('Résultat token :', result);

            if (result.error) {
                alert('Erreur : ' + result.error.message);
                btn.disabled = false;
                btn.textContent = 'Continuer vers l\'activation';
                return;
            }

            console.log('Token généré :', result.token.id);
            document.getElementById('stripe_token').value = result.token.id;
            this.submit();

        } catch(err) {
            console.log('Erreur catch :', err);
            alert('Une erreur est survenue.');
            btn.disabled = false;
        }
    });
</script>
</body>
</html>