// Détection du scroll pour masquer/afficher le header
let lastScrollTop = 0;

window.addEventListener("scroll", function () {
  let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  // Cible #wrapper OU .wrapperPortfolio selon la page
  let wrapper = document.getElementById("wrapper") || document.querySelector(".wrapperPortfolio");

  if (wrapper) {
    if (scrollTop > 80 && scrollTop > lastScrollTop) {
      // Défilement vers le bas : on masque le menu
      wrapper.style.top = "-150px";
    } else {
      // Défilement vers le haut ou haut de page : on réaffiche
      wrapper.style.top = "0";
    }
  }
  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
}, false);

// Commutateur Bilingue (Français / Anglais)
let currentLang = 'fr';

function toggleLanguage() {
  currentLang = currentLang === 'fr' ? 'en' : 'fr';
  const langBtn = document.getElementById('lang-btn');

  if (langBtn) {
    langBtn.textContent = currentLang === 'fr' ? '🇬🇧 EN' : '🇫🇷 FR';
  }

  document.querySelectorAll('[data-fr][data-en]').forEach(el => {
    el.textContent = el.getAttribute(`data-${currentLang}`);
  });

  const submitBtn = document.getElementById('submit-btn');
  if (submitBtn) {
    submitBtn.value = currentLang === 'fr' ? 'ENVOYER' : 'SEND';
  }
};

function updateTotal() {
  const basePriceHT = 990;
  const optionPriceHT = 290;
  const tvaRate = 1.20; // 20% TVA

  const hasOption = document.getElementById('opt_multilingue').checked;

  // Calcul HT et TTC
  const totalHT = hasOption ? (basePriceHT + optionPriceHT) : basePriceHT;
  const totalTTC = (totalHT * tvaRate).toFixed(2);

  // Mise à jour du texte du bouton
  const submitBtn = document.getElementById('submit-btn') || document.querySelector('button[type="submit"');
  if (submitBtn) {
    submitBtn.value = `Procéder au paiement (${totalHT} € HT)`;
  }
};


document.addEventListener('DOMContentLoaded'), () => {
  if (document.getElementById('opt_multilingue')) {
    updateTotal();
  }
};