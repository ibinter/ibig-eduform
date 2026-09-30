function refreshAlertBadge() {
  fetch('/admin/api/count-alerts.php')
    .then(r => r.json())
    .then(d => {
      const badge = document.querySelector('.badge-alert');
      if (!badge) return;

      if (d.total > 0) {
        badge.textContent = d.total;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    });
}

setInterval(refreshAlertBadge, 5000);
