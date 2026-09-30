/* ============================================================
   IBIG EDUFORM — ADMIN UI (SIDEBAR CONTROLLER)
   ------------------------------------------------------------
   ✔ Source unique de vérité
   ✔ Compatible sidebar + topbar
   ✔ Mobile & desktop
   ✔ Aucune redéfinition
   ============================================================ */

(function () {
  'use strict';

  const BODY = document.body;
  const STORAGE_KEY = 'eduSidebarState';

  function openSidebar() {
    BODY.classList.add('sidebar-open');
    localStorage.setItem(STORAGE_KEY, 'open');
  }

  function closeSidebar() {
    BODY.classList.remove('sidebar-open');
    localStorage.setItem(STORAGE_KEY, 'closed');
  }

  function toggleSidebar() {
    BODY.classList.toggle('sidebar-open');
    localStorage.setItem(
      STORAGE_KEY,
      BODY.classList.contains('sidebar-open') ? 'open' : 'closed'
    );
  }

  function restoreState() {
    if (localStorage.getItem(STORAGE_KEY) === 'open') {
      BODY.classList.add('sidebar-open');
    }
  }

  // Délégation globale (boutons & overlay)
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-action="toggle-sidebar"]')) {
      toggleSidebar();
    }
    if (e.target.closest('[data-action="close-sidebar"]')) {
      closeSidebar();
    }
  });

  // Init
  document.addEventListener('DOMContentLoaded', restoreState);

  // Exposition contrôlée
  window.AdminUI = {
    openSidebar,
    closeSidebar,
    toggleSidebar
  };
})();