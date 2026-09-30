(function () {

  let score = 0;
  let triggered = false;
  let adminAlertSent = false;

  const formationId = window.FORMATION_ID || null;
  const sessionKey  = window.SESSION_KEY  || null;

  if (!formationId || !sessionKey) return;

  function sendIntent(action, value = null) {
    fetch('/track-intent.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        formation_id: formationId,
        session_key: sessionKey,
        action: action,
        value: value
      })
    });
  }

  /* ===== RÈGLES ===== */

  setTimeout(() => {
    score += 2;
    sendIntent('time_30s', 30);
    check();
  }, 30000);

  setTimeout(() => {
    score += 2;
    sendIntent('time_60s', 60);
    check();
  }, 60000);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
      score += 1;
      sendIntent('scroll');
    }
  }, { once: true });

  document.addEventListener('click', e => {
    if (e.target.closest('.cta')) {
      score += 3;
      sendIntent('cta_click');
      check();
    }
  });

  function check() {
    if (score >= 6 && !triggered) {

      if (!adminAlertSent) {
        sendAdminAlert(score);
        adminAlertSent = true;
      }

      triggered = true;
      showWhatsappPopup();
    }
  }

  /* ===== POPUP ===== */

  function showWhatsappPopup() {

    const popup = document.createElement('div');
    popup.id = 'wa-popup';
    popup.innerHTML = `
      <div class="wa-box">
        <strong>Besoin d’aide pour cette formation ?</strong>
        <p>Un conseiller peut vous répondre maintenant.</p>
        <a id="wa-btn">Discuter sur WhatsApp</a>
        <span id="wa-close">&times;</span>
      </div>
    `;
    document.body.appendChild(popup);

    document.getElementById('wa-btn').onclick = () => {
      fetch('/track-whatsapp.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          formation_id: formationId,
          session_key: sessionKey
        })
      });

      window.open(
        'https://wa.me/225XXXXXXXX?text=' +
        encodeURIComponent('Bonjour, je suis intéressé par la formation.'),
        '_blank'
      );
    };

    document.getElementById('wa-close').onclick = () => popup.remove();
  }

})();

function sendAdminAlert(score) {
  fetch('/admin/api/create-alert.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      type: 'intent',
      formation_id: window.FORMATION_ID,
      session_key: window.SESSION_KEY,
      score: score,
      message: 'Intention forte détectée (score ' + score + ')'
    })
  });
}

function sendIntent(action, value = null) {
  fetch('/track-intent.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      formation_id: formationId,
      session_key: sessionKey,
      utm_source: window.UTM_SOURCE || null,
      utm_campaign: window.UTM_CAMPAIGN || null,
      action: action,
      value: value
    })
  });
}

