// Masquage intelligent du header au scroll
window.onscroll = function() { scrollFunction() };

function scrollFunction() {
  const wrapper = document.getElementById("wrapper");
  if (wrapper) {
    if (document.body.scrollTop > 1160 || document.documentElement.scrollTop > 1160) {
      wrapper.style.top = "-150px";
    } else {
      wrapper.style.top = "0";
    }
  }
}

// Commutateur Bilingue (Français / Anglais)
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

 
