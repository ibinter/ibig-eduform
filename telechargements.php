<?php
declare(strict_types=1);
$pageTitle = 'Documents & Supports — IBIG EDUFORM';
$ogDesc    = 'Téléchargez les brochures, guides et supports pédagogiques IBIG EDUFORM.';
require_once __DIR__ . '/partials/header.php';

$docs = [
    [
        'cat'   => 'Brochures & Présentations',
        'icon'  => '📘',
        'items' => [
            ['name' => 'Catalogue des formations IBIG EDUFORM 2025-2026', 'file' => 'catalogue-formations-ibig-eduform-2026.pdf', 'size' => 'PDF'],
            ['name' => 'Présentation générale IBIG EDUFORM', 'file' => 'presentation-ibig-eduform.pdf', 'size' => 'PDF'],
        ],
    ],
    [
        'cat'   => 'Formulaires d\'inscription',
        'icon'  => '📝',
        'items' => [
            ['name' => 'Dossier d\'inscription individuelle', 'file' => 'dossier-inscription-individuelle.pdf', 'size' => 'PDF'],
            ['name' => 'Dossier d\'inscription entreprise / groupe', 'file' => 'dossier-inscription-entreprise.pdf', 'size' => 'PDF'],
        ],
    ],
    [
        'cat'   => 'Guides & Ressources',
        'icon'  => '📚',
        'items' => [
            ['name' => 'Guide de l\'apprenant IBIG EDUFORM', 'file' => 'guide-apprenant-ibig-eduform.pdf', 'size' => 'PDF'],
            ['name' => 'Règlement intérieur des formations', 'file' => 'reglement-formations.pdf', 'size' => 'PDF'],
        ],
    ],
    [
        'cat'   => 'Certifications & Attestations',
        'icon'  => '🎓',
        'items' => [
            ['name' => 'Modèle d\'attestation de formation', 'file' => 'modele-attestation-formation.pdf', 'size' => 'PDF'],
            ['name' => 'Présentation des certifications IBIG EDUFORM', 'file' => 'certifications-ibig-eduform.pdf', 'size' => 'PDF'],
        ],
    ],
];
?>
<style>
.tdl-hero{background:linear-gradient(135deg,#0a1733 0%,#0b2552 60%,#1a3a6e 100%);color:#fff;padding:60px 20px 50px;text-align:center}
.tdl-hero h1{font-size:2rem;font-weight:900;margin:0 0 12px}
.tdl-hero p{color:#94a3b8;font-size:1rem;max-width:600px;margin:0 auto}
.tdl-wrap{max-width:960px;margin:0 auto;padding:50px 20px 80px}
.tdl-section{margin-bottom:44px}
.tdl-cat-head{display:flex;align-items:center;gap:10px;font-size:1.1rem;font-weight:800;color:#0a1733;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e5e7eb}
.tdl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.tdl-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;display:flex;align-items:flex-start;gap:14px;text-decoration:none;color:inherit;transition:box-shadow .2s,border-color .2s}
.tdl-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.1);border-color:#1d4ed8}
.tdl-card-icon{font-size:2rem;flex-shrink:0;line-height:1}
.tdl-card-body{flex:1;min-width:0}
.tdl-card-name{font-size:.9rem;font-weight:700;color:#0a1733;line-height:1.35;margin-bottom:4px}
.tdl-card-meta{font-size:.75rem;color:#6b7280;display:flex;align-items:center;gap:6px}
.tdl-card-badge{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:1px 8px;font-size:.7rem;font-weight:700}
.tdl-card-dl{font-size:.8rem;font-weight:700;color:#1d4ed8;display:flex;align-items:center;gap:4px;margin-top:8px}
.tdl-note{background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:18px 22px;margin-bottom:40px;font-size:.88rem;color:#78350f;line-height:1.6}
.tdl-note strong{display:block;margin-bottom:4px;color:#92400e}
</style>

<div class="tdl-hero">
  <div style="font-size:3rem;margin-bottom:14px">📥</div>
  <h1>Documents & Supports</h1>
  <p>Retrouvez ici tous les documents utiles : brochures, formulaires d'inscription, guides pédagogiques et ressources liées aux certifications IBIG EDUFORM.</p>
</div>

<div class="tdl-wrap">
  <div class="tdl-note">
    <strong>📌 Note importante</strong>
    Ces documents sont mis à jour régulièrement. Pour toute demande de document spécifique ou d'information complémentaire, contactez-nous à <a href="mailto:formation@ibig-eduform.com" style="color:#92400e;font-weight:700">formation@ibig-eduform.com</a> ou appelez le <a href="tel:+2252722276014" style="color:#92400e;font-weight:700">+225 27 22 27 60 14</a>.
  </div>

  <?php foreach ($docs as $section): ?>
  <div class="tdl-section">
    <div class="tdl-cat-head">
      <span><?= $section['icon'] ?></span>
      <span><?= htmlspecialchars($section['cat']) ?></span>
    </div>
    <div class="tdl-grid">
      <?php foreach ($section['items'] as $doc): ?>
      <a class="tdl-card" href="mailto:formation@ibig-eduform.com?subject=Demande+document+:+<?= urlencode($doc['name']) ?>" title="Demander ce document par email">
        <div class="tdl-card-icon">📄</div>
        <div class="tdl-card-body">
          <div class="tdl-card-name"><?= htmlspecialchars($doc['name']) ?></div>
          <div class="tdl-card-meta">
            <span class="tdl-card-badge"><?= $doc['size'] ?></span>
          </div>
          <div class="tdl-card-dl">✉ Demander par email</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:28px 28px;text-align:center;margin-top:20px">
    <div style="font-size:2rem;margin-bottom:10px">📞</div>
    <div style="font-size:1.05rem;font-weight:800;color:#0a1733;margin-bottom:8px">Besoin d'aide ou d'un document personnalisé ?</div>
    <p style="color:#475569;font-size:.9rem;margin:0 0 18px">Notre équipe est disponible pour vous accompagner dans votre démarche de formation.</p>
    <a href="/contact.php" style="display:inline-block;background:#0a1733;color:#fff;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none;font-size:.95rem">📬 Nous contacter</a>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
