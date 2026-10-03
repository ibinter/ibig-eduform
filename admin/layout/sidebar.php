<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — ADMIN SIDEBAR (REFONTE 2026)
 * ------------------------------------------------------------
 * ✔ Menu dynamique par permissions (RBAC)
 * ✔ Compatible guard.php (has_permission)
 * ✔ Aucun groupe vide affiché
 * ✔ Mode réduit + mobile drawer + tooltips collapsed
 * ✔ Design navy/gold — marque IBIG EDUFORM
 * ============================================================
 */

require_once __DIR__ . '/../auth/guard.php';

$active = $activeMenu ?? 'dashboard';

$u = $_SESSION['user'] ?? [
    'first_name' => 'Admin',
    'last_name'  => 'EDUFORM',
    'email'      => 'admin@ibig-eduform.com',
    'role'       => 'super_admin',
];

$initials = strtoupper(
    substr($u['first_name'] ?? 'A', 0, 1) .
    substr($u['last_name']  ?? 'E', 0, 1)
);
?>

<aside class="admin-sidebar" id="adminSidebar">

  <!-- BRAND -->
  <div class="brand">
    <div class="brand-logo">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
    </div>
    <div class="brand-text">
      <div class="brand-title">IBIG EDUFORM</div>
      <div class="brand-sub">Administration</div>
    </div>
    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Réduire le menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </div>

  <!-- NAVIGATION -->
  <nav class="nav">

    <!-- PILOTAGE -->
    <?php if (has_permission('view_dashboard')): ?>
      <div class="nav-group"><span>Pilotage</span></div>
      <a href="/admin/dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>" data-tip="Dashboard">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </span>
        <span>Dashboard</span>
      </a>
    <?php endif; ?>

    <!-- FORMATIONS -->
    <?php
      $showFormations =
        has_permission('manage_formations') ||
        has_permission('view_preinscriptions');
    ?>
    <?php if ($showFormations): ?>
      <div class="nav-group"><span>Formations</span></div>

      <?php if (has_permission('manage_formations')): ?>
        <a href="/admin/formations/index.php" class="<?= $active === 'formations' ? 'active' : '' ?>" data-tip="Formations">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.66 2.24 3 6 3s6-1.34 6-3v-5"/></svg>
          </span>
          <span>Formations</span>
        </a>
        <a href="/admin/niveaux/index.php" class="<?= $active === 'niveaux' ? 'active' : '' ?>" data-tip="Niveaux">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </span>
          <span>Niveaux formations</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('view_preinscriptions')): ?>
        <a href="/admin/preinscriptions/index.php" class="<?= $active === 'preinscriptions' ? 'active' : '' ?>" data-tip="Préinscriptions">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </span>
          <span>Préinscriptions</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('view_preinscriptions')): ?>
        <a href="/admin/calendrier/index.php" class="<?= $active === 'planning' ? 'active' : '' ?>" data-tip="Planning">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8.01" y2="14"/><line x1="12" y1="14" x2="12.01" y2="14"/><line x1="16" y1="14" x2="16.01" y2="14"/></svg>
          </span>
          <span>Planning formations</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('manage_formations') || has_permission('view_preinscriptions')): ?>
        <a href="/admin/demandes-formation/index.php" class="<?= $active === 'demandes_formation' ? 'active' : '' ?>" data-tip="Demandes">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9.5h20"/></svg>
          </span>
          <span>Demandes de formation</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('manage_formations') || has_permission('view_preinscriptions')): ?>
        <a href="/admin/satisfaction/index.php" class="<?= $active === 'satisfaction' ? 'active' : '' ?>" data-tip="Satisfaction">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
          </span>
          <span>Satisfaction apprenants</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>

    <!-- EMPLOI & INSERTION -->
    <?php if (has_permission('manage_formateurs')): ?>
      <div class="nav-group"><span>Emploi &amp; Insertion</span></div>

      <a href="/admin/emplois/offres.php" class="<?= $active === 'emplois_offres' ? 'active' : '' ?>" data-tip="Offres d'emploi">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </span>
        <span>Offres d'emploi</span>
      </a>

      <a href="/admin/emplois/candidatures.php" class="<?= $active === 'emplois_candidatures' ? 'active' : '' ?>" data-tip="Candidatures">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </span>
        <span>Candidatures</span>
      </a>

      <a href="/admin/emplois/insertions.php" class="<?= $active === 'emplois_insertions' ? 'active' : '' ?>" data-tip="Insertions">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </span>
        <span>Insertions</span>
      </a>

      <a href="/admin/emplois/notifications.php" class="<?= $active === 'emplois_notifications' ? 'active' : '' ?>" data-tip="Notifications">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </span>
        <span>Notifications</span>
      </a>

      <a href="/admin/emplois/impact.php" class="<?= $active === 'emplois_impact' ? 'active' : '' ?>" data-tip="Impact">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </span>
        <span>Impact &amp; statistiques</span>
      </a>
    <?php endif; ?>

    <!-- CONTENU -->
    <?php if (has_permission('manage_formations')): ?>
      <div class="nav-group"><span>Contenu</span></div>

      <a href="/admin/opportunites/index.php" class="<?= $active === 'opportunites' ? 'active' : '' ?>" data-tip="Opportunités">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </span>
        <span>Opportunités &amp; Emploi</span>
      </a>

      <a href="/admin/avis/index.php" class="<?= $active === 'avis' ? 'active' : '' ?>" data-tip="Avis">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </span>
        <span>Avis &amp; Témoignages</span>
      </a>

      <a href="/admin/blog/index.php" class="<?= $active === 'blog' ? 'active' : '' ?>" data-tip="Blog">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6z"/></svg>
        </span>
        <span>Blog &amp; Publications</span>
      </a>

      <a href="/admin/faq/index.php" class="<?= $active === 'faq' ? 'active' : '' ?>" data-tip="FAQ">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <span>FAQ</span>
      </a>
    <?php endif; ?>

    <!-- COMMERCIAL -->
    <?php if (has_permission('view_preinscriptions') || has_permission('manage_formations')): ?>
      <div class="nav-group"><span>Commercial</span></div>

      <a href="/admin/leads/index.php" class="<?= $active === 'leads' ? 'active' : '' ?>" data-tip="Leads">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <span>Leads</span>
      </a>

      <a href="/admin/tdr-copies/index.php" class="<?= $active === 'tdr_copies' ? 'active' : '' ?>" data-tip="TDR Téléchargés">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>
        </span>
        <span>TDR Téléchargés</span>
      </a>

      <a href="/admin/tdr-admin-download.php" class="<?= $active === 'tdr_admin' ? 'active' : '' ?>" data-tip="Générer TDR">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="12" cy="15" r="2"/><path d="M12 12v1"/></svg>
        </span>
        <span>Générer TDR</span>
      </a>

      <a href="/admin/parrainages/index.php" class="<?= $active === 'parrainages' ? 'active' : '' ?>" data-tip="Parrainages">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </span>
        <span>Parrainages</span>
      </a>
    <?php endif; ?>

    <!-- STATISTIQUES -->
    <?php if (has_permission('view_statistics')): ?>
      <div class="nav-group"><span>Statistiques</span></div>

      <a href="/admin/statistics/index.php" class="<?= $active === 'statistics' ? 'active' : '' ?>" data-tip="Statistiques">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
        </span>
        <span>Statistiques globales</span>
      </a>
    <?php endif; ?>

    <!-- RH -->
    <?php if (has_permission('manage_formateurs')): ?>
      <div class="nav-group"><span>Ressources humaines</span></div>

      <a href="/admin/formateurs/index.php" class="<?= $active === 'formateurs' ? 'active' : '' ?>" data-tip="Formateurs">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <span>Formateurs</span>
      </a>
    <?php endif; ?>

    <!-- SYSTEME -->
    <?php
      $showSystem =
        has_permission('manage_users') ||
        has_permission('manage_admins') ||
        has_permission('manage_settings');
    ?>
    <?php if ($showSystem): ?>
      <div class="nav-group"><span>Système</span></div>

      <?php if (has_permission('manage_users')): ?>
        <a href="/admin/users/index.php" class="<?= $active === 'users' ? 'active' : '' ?>" data-tip="Utilisateurs">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <span>Utilisateurs</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('manage_admins')): ?>
        <a href="/admin/admins/index.php" class="<?= $active === 'admins' ? 'active' : '' ?>" data-tip="Administrateurs">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          <span>Administrateurs</span>
        </a>
      <?php endif; ?>

      <?php if (has_permission('manage_settings')): ?>
        <a href="/admin/settings/index.php" class="<?= $active === 'settings' ? 'active' : '' ?>" data-tip="Paramètres">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          </span>
          <span>Paramètres</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>

  </nav>

  <!-- USER FOOTER -->
  <div class="sidebar-footer">
    <div class="sf-avatar"><?= htmlspecialchars($initials) ?></div>
    <div class="sf-info">
      <div class="sf-user"><?= e(trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? ''))) ?></div>
      <div class="sf-mail"><?= e($u['email'] ?? '') ?></div>
    </div>
    <a class="btn-logout" href="/admin/auth/logout.php" title="Déconnexion">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>

<style>
/* ======================================================================
   SIDEBAR — IBIG EDUFORM (REFONTE 2026 — NAVY / GOLD)
====================================================================== */

:root{
  --sb-w:272px;
  --sb-collapsed-w:70px;
  --sb-bg:#0c1a30;
  --sb-bg-deep:#071020;
  --sb-border:rgba(255,255,255,.07);
  --sb-hover:rgba(255,255,255,.05);
  --sb-active-gold:#f5a623;
  --sb-active-bg:rgba(245,166,35,.12);
  --sb-muted:#7c8fa8;
  --sb-text:#c8d6e8;
  --sb-text-bright:#e8f0fb;
}

/* ===== CONTENEUR ===== */
.admin-sidebar{
  width:var(--sb-w);
  background:linear-gradient(180deg, var(--sb-bg) 0%, var(--sb-bg-deep) 100%);
  color:var(--sb-text);
  padding:16px 12px 12px;
  border-right:1px solid var(--sb-border);
  position:fixed;
  top:var(--topbar-h,64px);
  left:0;
  height:calc(100vh - var(--topbar-h,64px));
  display:flex;
  flex-direction:column;
  overflow:hidden;
  transition:width .3s cubic-bezier(.4,0,.2,1);
  z-index:1000;
  box-shadow:4px 0 24px rgba(0,0,0,.35);
}

/* ===== DÉCALAGE DU CONTENU ===== */
.admin-main{
  margin-left:var(--sb-w);
  transition:margin-left .3s cubic-bezier(.4,0,.2,1);
}
body.sidebar-collapsed .admin-main{ margin-left:var(--sb-collapsed-w); }

/* ===== SCROLL ===== */
.admin-sidebar .nav{
  overflow-y:auto;
  overflow-x:hidden;
  flex:1;
  padding-right:2px;
  scrollbar-width:thin;
  scrollbar-color:rgba(255,255,255,.1) transparent;
  overflow-anchor:none;
}
.admin-sidebar .nav::-webkit-scrollbar{width:4px}
.admin-sidebar .nav::-webkit-scrollbar-thumb{
  background:rgba(255,255,255,.1);
  border-radius:10px;
}

/* ===== BRAND ===== */
.brand{
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 10px 10px 8px;
  border-radius:14px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--sb-border);
  margin-bottom:18px;
  position:relative;
  flex-shrink:0;
}
.brand-logo{
  width:38px;height:38px;
  border-radius:10px;
  overflow:hidden;
  flex-shrink:0;
  background:rgba(245,166,35,.15);
  display:flex;align-items:center;justify-content:center;
}
.brand-logo img{
  width:100%;height:100%;
  object-fit:cover;
  border-radius:10px;
}
.brand-text{display:flex;flex-direction:column;flex:1;min-width:0}
.brand-title{
  font-weight:800;
  color:var(--sb-text-bright);
  font-size:13px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.brand-sub{
  font-size:11px;
  color:var(--sb-active-gold);
  margin-top:2px;
  opacity:.8;
}

/* ===== TOGGLE ===== */
.sidebar-toggle{
  position:absolute;
  right:8px;
  top:50%;
  transform:translateY(-50%);
  background:transparent;
  border:none;
  color:var(--sb-muted);
  cursor:pointer;
  padding:4px;
  border-radius:6px;
  display:flex;
  align-items:center;
  justify-content:center;
  transition:color .2s, background .2s;
  flex-shrink:0;
}
.sidebar-toggle:hover{
  color:var(--sb-text-bright);
  background:rgba(255,255,255,.08);
}

/* ===== NAV GROUP ===== */
.nav-group{
  display:flex;
  align-items:center;
  gap:8px;
  margin:16px 6px 6px;
  overflow:hidden;
}
.nav-group span{
  font-size:10px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.14em;
  color:var(--sb-muted);
  white-space:nowrap;
}
.nav-group::after{
  content:'';
  flex:1;
  height:1px;
  background:var(--sb-border);
  border-radius:1px;
}

/* ===== NAV LINKS ===== */
.nav a{
  display:flex;
  align-items:center;
  gap:10px;
  padding:9px 10px;
  border-radius:10px;
  color:var(--sb-text);
  font-size:13px;
  font-weight:500;
  text-decoration:none;
  border:1px solid transparent;
  transition:
    background .2s cubic-bezier(.4,0,.2,1),
    border-color .2s,
    color .2s;
  margin-bottom:2px;
  position:relative;
}
.nav a .ico{
  width:20px;
  height:20px;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
}
.nav a .ico svg{
  width:16px;
  height:16px;
  opacity:.75;
  transition:opacity .2s;
}
.nav a span:not(.ico){
  flex:1;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.nav a:hover{
  background:var(--sb-hover);
  border-color:var(--sb-border);
  color:var(--sb-text-bright);
}
.nav a:hover .ico svg{opacity:1}
.nav a.active{
  background:var(--sb-active-bg);
  border-color:rgba(245,166,35,.25);
  color:#fff;
  font-weight:700;
  box-shadow:inset 3px 0 0 var(--sb-active-gold);
}
.nav a.active .ico svg{opacity:1;stroke:var(--sb-active-gold)}

/* ===== FOOTER UTILISATEUR ===== */
.sidebar-footer{
  margin-top:8px;
  padding:10px;
  border-radius:12px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--sb-border);
  display:flex;
  align-items:center;
  gap:10px;
  flex-shrink:0;
}
.sf-avatar{
  width:34px;height:34px;
  border-radius:9px;
  background:linear-gradient(135deg, #f5a623, #e8890a);
  color:#0c1a30;
  font-weight:900;
  font-size:13px;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
  letter-spacing:.03em;
}
.sf-info{flex:1;min-width:0;overflow:hidden}
.sf-user{
  font-weight:700;
  color:var(--sb-text-bright);
  font-size:12px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.sf-mail{
  margin-top:2px;
  font-size:11px;
  color:var(--sb-muted);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.btn-logout{
  width:30px;height:30px;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:8px;
  background:rgba(239,68,68,.1);
  border:1px solid rgba(239,68,68,.2);
  color:#f87171;
  text-decoration:none;
  flex-shrink:0;
  transition:background .2s, border-color .2s, color .2s;
}
.btn-logout:hover{
  background:rgba(239,68,68,.2);
  border-color:rgba(239,68,68,.4);
  color:#ff8c8c;
}

/* ===== COLLAPSED ===== */
body.sidebar-collapsed .admin-sidebar{
  width:var(--sb-collapsed-w);
  padding:16px 9px 12px;
}
body.sidebar-collapsed .brand{
  justify-content:center;
  padding:9px;
  gap:0;
}
body.sidebar-collapsed .brand-text,
body.sidebar-collapsed .sidebar-toggle,
body.sidebar-collapsed .nav-group span,
body.sidebar-collapsed .nav-group::after,
body.sidebar-collapsed .nav a span:not(.ico),
body.sidebar-collapsed .sf-info,
body.sidebar-collapsed .btn-logout{
  display:none;
}
body.sidebar-collapsed .nav-group{
  margin:12px 4px 4px;
  justify-content:center;
}
body.sidebar-collapsed .nav a{
  justify-content:center;
  padding:10px;
  gap:0;
}
body.sidebar-collapsed .nav a .ico{width:22px;height:22px}
body.sidebar-collapsed .nav a .ico svg{width:18px;height:18px}
body.sidebar-collapsed .sidebar-footer{
  justify-content:center;
  padding:9px;
  gap:0;
}
body.sidebar-collapsed .sf-avatar{width:36px;height:36px;border-radius:10px}

/* Tooltip collapsed */
body.sidebar-collapsed .nav a::after{
  content:attr(data-tip);
  position:absolute;
  left:calc(100% + 12px);
  top:50%;
  transform:translateY(-50%);
  background:#1e3a5f;
  color:#fff;
  font-size:12px;
  font-weight:600;
  padding:5px 10px;
  border-radius:8px;
  white-space:nowrap;
  pointer-events:none;
  opacity:0;
  transition:opacity .18s, transform .18s;
  transform:translateY(-50%) translateX(-4px);
  z-index:9999;
  box-shadow:0 4px 16px rgba(0,0,0,.4);
  border:1px solid rgba(255,255,255,.1);
}
body.sidebar-collapsed .nav a:hover::after{
  opacity:1;
  transform:translateY(-50%) translateX(0);
}

/* ===== MOBILE ===== */
@media (max-width:980px){
  .admin-sidebar{
    position:fixed;
    left:calc(-1 * var(--sb-w) - 10px);
    width:var(--sb-w);
    z-index:1500;
    transition:left .3s cubic-bezier(.4,0,.2,1);
  }
  body.sidebar-open .admin-sidebar{left:0}
  .admin-main{ margin-left:0 !important; }
}
</style>

</aside>

<!-- SIDEBAR SCRIPT -->
<script>
(function () {
  var KEY = 'ibig_sb_scroll';

  function getNav() { return document.querySelector('.admin-sidebar .nav'); }

  function save() {
    var n = getNav();
    if (!n) return;
    try { localStorage.setItem(KEY, String(n.scrollTop)); } catch(e) {}
  }

  function restore() {
    var n = getNav();
    if (!n) return;
    try {
      var v = localStorage.getItem(KEY);
      if (v !== null) { n.scrollTop = parseInt(v, 10) || 0; }
    } catch(e) {}
  }

  /* Sauvegarder juste avant de quitter la page (le plus fiable) */
  window.addEventListener('beforeunload', save);

  /* Sauvegarder aussi en continu */
  var nav = getNav();
  if (nav) {
    nav.addEventListener('scroll', save, { passive: true });
    nav.style.overflowAnchor = 'none'; /* empêche le navigateur de sauter */
  }

  /* Restaurer après que le navigateur a calculé les dimensions (raf x2) */
  requestAnimationFrame(function() {
    requestAnimationFrame(function() {
      restore();
    });
  });

  /* Toggle collapsed */
  var btn = document.getElementById('sidebarToggle');
  if (!btn) return;
  var isMobile = function(){ return window.matchMedia('(max-width:980px)').matches; };
  btn.addEventListener('click', function () {
    if (isMobile()) {
      document.body.classList.remove('sidebar-open');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
    }
  });
})();
</script>
