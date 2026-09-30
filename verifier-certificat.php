<?php
declare(strict_types=1);
$pageTitle = 'Vérifier un certificat — IBIG EDUFORM';
$ogDesc    = 'Vérifiez l\'authenticité d\'un certificat IBIG EDUFORM en saisissant le code indiqué sur le document.';
require_once __DIR__ . '/partials/header.php';
?>
<style>
.vc-wrap{max-width:620px;margin:60px auto 80px;padding:0 20px;text-align:center}
.vc-icon{font-size:3.5rem;margin-bottom:16px}
.vc-title{font-size:1.8rem;font-weight:900;color:#0a1733;margin:0 0 10px}
.vc-sub{color:#6b7280;font-size:1rem;margin:0 0 36px;line-height:1.6}
.vc-form{display:flex;gap:10px;max-width:480px;margin:0 auto 32px;flex-wrap:wrap;justify-content:center}
.vc-input{flex:1;min-width:220px;border:2px solid #e5e7eb;border-radius:10px;padding:13px 16px;font-size:1rem;outline:none;font-family:monospace;letter-spacing:.5px;transition:border-color .2s}
.vc-input:focus{border-color:#1d4ed8}
.vc-btn{background:#0a1733;color:#fff;border:none;border-radius:10px;padding:13px 28px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s;white-space:nowrap}
.vc-btn:hover{background:#1d4ed8}
.vc-info{background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:20px 24px;text-align:left;color:#0c4a6e;font-size:.9rem;line-height:1.7}
.vc-info strong{display:block;margin-bottom:6px;color:#0a1733}
.vc-extern{display:inline-flex;align-items:center;gap:8px;margin-top:28px;background:#f59e0b;color:#0a1733;font-weight:700;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:.95rem;transition:background .2s}
.vc-extern:hover{background:#fbbf24}
</style>

<main>
<div class="vc-wrap">
  <div class="vc-icon">🎓</div>
  <h1 class="vc-title">Vérifier un certificat IBIG EDUFORM</h1>
  <p class="vc-sub">Saisissez le code unique inscrit sur le certificat pour confirmer son authenticité et consulter les informations du bénéficiaire.</p>

  <form class="vc-form" method="get" action="https://ibigroupsarl.com/verify/certificat.php" target="_blank">
    <input class="vc-input" type="text" name="code" placeholder="Ex : IBIG-CERT-2026-XXXX"
           pattern="IBIG-[A-Z0-9\-]+" title="Format : IBIG-CERT-ANNÉE-NUMÉRO"
           value="<?= htmlspecialchars((string)($_GET['code'] ?? ''), ENT_QUOTES) ?>"
           required autocomplete="off" autocapitalize="characters">
    <button class="vc-btn" type="submit">🔍 Vérifier</button>
  </form>

  <div class="vc-info">
    <strong>📋 Comment trouver le code ?</strong>
    Le code de vérification est imprimé en bas de votre certificat, sous le QR code, au format <code>IBIG-CERT-ANNÉE-NUMÉRO</code>.<br>
    Vous pouvez également scanner le QR code directement — il vous redirigera automatiquement vers cette vérification.
  </div>

  <a href="https://ibigroupsarl.com/verify/certificat.php" target="_blank" rel="noopener" class="vc-extern">
    🌐 Portail officiel de vérification IBIG
  </a>

  <p style="margin-top:24px;font-size:.8rem;color:#9ca3af">
    Les certificats IBIG EDUFORM sont délivrés par INTERMARK BUSINESS INTERNATIONAL GROUP (IBIG SARL) et reconnus dans les 17 pays de l'espace OHADA.
  </p>
</div>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
