<?php
declare(strict_types=1);

$u = auth_user();

$initials = strtoupper(
    substr($u['first_name'] ?? 'A', 0, 1) .
    substr($u['last_name']  ?? 'E', 0, 1)
);

$roleLabels = [
    'super_admin'    => 'Super Admin',
    'admin'          => 'Admin',
    'manager'        => 'Manager',
    'content_editor' => 'Éditeur',
    'rh_manager'     => 'RH Manager',
    'commercial'     => 'Commercial',
];
$roleLabel = $roleLabels[$u['role'] ?? ''] ?? ucfirst(str_replace('_', ' ', $u['role'] ?? 'Admin'));
?>

<div class="topbar">

  <!-- LEFT -->
  <div class="topbar-left">
    <button class="topbar-toggle" id="topbarToggle" aria-label="Menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <div class="titles">
      <div class="tb-page"><?= e($pageTitle ?? 'Dashboard'); ?></div>
      <div class="tb-sub">IBIG EDUFORM — Administration</div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="topbar-right">

    <a class="topbar-site-btn" href="/" target="_blank" rel="noopener" title="Voir le site public">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      <span>Site</span>
    </a>

    <div class="topbar-user">
      <div class="tb-user-info">
        <div class="tb-user-name"><?= e(trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? ''))) ?></div>
        <div class="tb-user-role"><?= e($roleLabel) ?></div>
      </div>
      <div class="tb-avatar"><?= htmlspecialchars($initials) ?></div>
    </div>

  </div>

</div>

<style>
/* ======================================================================
   TOPBAR — IBIG EDUFORM (REFONTE 2026 — NAVY / GOLD)
====================================================================== */

:root{
  --topbar-h:60px;
  --tb-bg:#0c1a30;
  --tb-border:rgba(255,255,255,.07);
  --tb-text:#c8d6e8;
  --tb-muted:#6b7e96;
  --tb-bright:#e8f0fb;
  --tb-gold:#f5a623;
}

/* ===== CONTENEUR ===== */
.topbar{
  position:fixed;
  top:0;
  left:0;
  right:0;
  height:var(--topbar-h);
  background:var(--tb-bg);
  border-bottom:1px solid var(--tb-border);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 18px;
  z-index:1200;
  box-shadow:0 2px 16px rgba(0,0,0,.3);
}
/* Ligne d'accentuation dorée en bas de la topbar */
.topbar::after{
  content:'';
  position:absolute;
  bottom:-1px;
  left:0;
  right:0;
  height:2px;
  background:linear-gradient(90deg, var(--tb-gold), rgba(245,166,35,.3), transparent 60%);
  pointer-events:none;
}

/* La topbar étant hors flux, on décale le contenu vers le bas */
.admin-app{ margin-top:var(--topbar-h,60px); }

/* ===== LEFT ===== */
.topbar-left{
  display:flex;
  align-items:center;
  gap:14px;
}

/* ===== TOGGLE ===== */
.topbar-toggle{
  background:transparent;
  border:1px solid var(--tb-border);
  color:var(--tb-text);
  width:36px;
  height:36px;
  border-radius:10px;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
  transition:background .2s, border-color .2s, color .2s;
}
.topbar-toggle:hover{
  background:rgba(255,255,255,.07);
  border-color:rgba(255,255,255,.14);
  color:var(--tb-bright);
}

/* ===== TITRES ===== */
.titles{
  display:flex;
  flex-direction:column;
  line-height:1.25;
}
.tb-page{
  font-size:16px;
  font-weight:800;
  color:var(--tb-bright);
  white-space:nowrap;
}
.tb-sub{
  font-size:11px;
  color:var(--tb-muted);
  margin-top:1px;
  white-space:nowrap;
}

/* ===== RIGHT ===== */
.topbar-right{
  display:flex;
  align-items:center;
  gap:10px;
}

/* BOUTON SITE */
.topbar-site-btn{
  display:flex;
  align-items:center;
  gap:6px;
  padding:7px 12px;
  border-radius:9px;
  background:transparent;
  border:1px solid var(--tb-border);
  color:var(--tb-text);
  font-size:12px;
  font-weight:600;
  text-decoration:none;
  transition:background .2s, border-color .2s, color .2s, transform .15s;
  white-space:nowrap;
}
.topbar-site-btn:hover{
  background:rgba(255,255,255,.06);
  border-color:rgba(245,166,35,.35);
  color:var(--tb-gold);
  transform:translateY(-1px);
}

/* USER ZONE */
.topbar-user{
  display:flex;
  align-items:center;
  gap:8px;
  padding:5px 5px 5px 10px;
  border-radius:10px;
  border:1px solid var(--tb-border);
  background:rgba(255,255,255,.03);
  cursor:default;
}
.tb-user-info{
  display:flex;
  flex-direction:column;
  text-align:right;
}
.tb-user-name{
  font-size:12px;
  font-weight:700;
  color:var(--tb-bright);
  white-space:nowrap;
}
.tb-user-role{
  font-size:10px;
  color:var(--tb-gold);
  font-weight:600;
  margin-top:1px;
  white-space:nowrap;
}
.tb-avatar{
  width:32px;height:32px;
  border-radius:8px;
  background:linear-gradient(135deg, #f5a623, #e8890a);
  color:#0c1a30;
  font-weight:900;
  font-size:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
}

/* ===== RESPONSIVE ===== */
@media (max-width:760px){
  .topbar{ padding:0 12px; }
  .tb-sub,.tb-user-info{ display:none; }
  .topbar-site-btn span{ display:none; }
  .topbar-site-btn{ padding:7px 9px; }
}
</style>

<script>
(function(){
  const btn = document.getElementById('topbarToggle');
  if(!btn) return;
  const isMobile = function(){ return window.matchMedia('(max-width:980px)').matches; };
  btn.addEventListener('click', function(){
    if (isMobile()) {
      document.body.classList.toggle('sidebar-open');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
    }
  });
})();
</script>
