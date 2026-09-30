(function(){

  function sendEvent(type, label = null, formation = null) {
    try {
      fetch('/track_event.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          event_type: type,
          event_label: label,
          formation_id: formation
        })
      });
    } catch(e){}
  }

  /* ======== CLICS TRACKÉS ======== */
  document.addEventListener('click', function(e){
    const el = e.target.closest('[data-track]');
    if(!el) return;

    sendEvent(
      el.dataset.track,
      el.dataset.label || null,
      el.dataset.formation || null
    );
  });

  /* ======== SCROLL ENGAGEMENT ======== */
  let scrollTracked = false;
  window.addEventListener('scroll', function(){
    if(!scrollTracked && window.scrollY > 600){
      scrollTracked = true;
      sendEvent('scroll_600px');
    }
  });

  /* ======== TEMPS SUR PAGE ======== */
  let start = Date.now();
  window.addEventListener('beforeunload', function(){
    let seconds = Math.round((Date.now() - start) / 1000);
    if (seconds >= 15) {
      sendEvent('time_on_page', seconds + 's');
    }
  });

})();

(function () {
  function send(event, data = {}) {
    navigator.sendBeacon(
      '/core/track_event.php',
      JSON.stringify({
        event: event,
        page: location.pathname,
        formation_id: data.formation_id || null
      })
    );
  }

  /* CLICS CTA */
  document.addEventListener('click', function (e) {
    const t = e.target.closest('[data-track]');
    if (!t) return;

    send(t.getAttribute('data-track'), {
      formation_id: t.getAttribute('data-formation') || null
    });
  });

  /* SCROLL 75% */
  let sentScroll = false;
  window.addEventListener('scroll', function () {
    if (sentScroll) return;
    if ((window.scrollY + window.innerHeight) / document.body.scrollHeight > 0.75) {
      sentScroll = true;
      send('scroll_75');
    }
  });

  /* TEMPS PAGE */
  setTimeout(() => send('time_30s'), 30000);
})();

