<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

$pdo = Database::connect();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function slugify(string $text): string {
  $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
  $text = strtolower($text);
  $text = preg_replace('~[^a-z0-9]+~', '-', $text);
  return trim($text, '-');
}

/* Filtres */
$domaine = trim($_GET['domaine'] ?? '');
$mode    = trim($_GET['mode'] ?? '');
$q       = trim($_GET['q'] ?? '');

$where = ["f.statut='active'"];
$params = [];

if ($domaine !== '') { $where[] = "f.domaine = ?"; $params[] = $domaine; }
if ($mode !== '')    { $where[] = "f.mode = ?";    $params[] = $mode; }
if ($q !== '')       { $where[] = "(f.titre LIKE ? OR f.description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }

$sql = "
  SELECT f.id, f.titre, f.slug, f.domaine, f.mode, f.tarif_en_ligne, f.tarif_presentiel,
         l.hero_image, l.hero_subtitle, l.pitch_marketing
  FROM formations f
  LEFT JOIN formation_landings l ON l.formation_id = f.id
  WHERE ".implode(' AND ', $where)."
  ORDER BY f.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Liste des domaines (pour filtre) */
$domaines = $pdo->query("SELECT DISTINCT domaine FROM formations WHERE statut='active' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Catalogue des formations — IBIG EDUFORM</title>
<meta name="description" content="Catalogue des formations professionnelles IBIG EDUFORM.">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{margin:0;font-family:Inter,system-ui,sans-serif;background:#020617;color:#e5e7eb}
.container{max-width:1200px;margin:auto;padding:40px 18px}
h1{margin:0 0 14px}
.filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;margin:18px 0 28px}
input,select,button{padding:12px;border-radius:12px;border:none;background:rgba(255,255,255,.08);color:#e5e7eb}
button{background:#f5a623;color:#111;font-weight:900;cursor:pointer}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.card{background:rgba(255,255,255,.06);border-radius:18px;overflow:hidden}
.card img{width:100%;height:180px;object-fit:cover;display:block}
.card .p{padding:16px}
.badge{display:inline-block;background:#f5a623;color:#111;padding:4px 10px;border-radius:999px;font-weight:800;font-size:.85rem}
a{color:inherit;text-decoration:none}
.small{opacity:.85;font-size:.95rem}
.meta{opacity:.8;font-size:.9rem;margin-top:8px}
@media(max-width:950px){.grid{grid-template-columns:1fr 1fr}.filters{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.grid{grid-template-columns:1fr}.filters{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="container">

<h1>Catalogue des formations</h1>
<div class="small">Filtre et ouvre une landing complète (marketing + contenu + SEO).</div>

<form class="filters" method="get">
  <input name="q" value="<?= h($q); ?>" placeholder="Rechercher une formation...">
  <select name="domaine">
    <option value="">Tous les domaines</option>
    <?php foreach($domaines as $dom): ?>
      <option value="<?= h($dom); ?>" <?= $domaine===$dom?'selected':''; ?>><?= h($dom); ?></option>
    <?php endforeach; ?>
  </select>
  <select name="mode">
    <option value="">Tous les modes</option>
    <option value="presentiel" <?= $mode==='presentiel'?'selected':''; ?>>Présentiel</option>
    <option value="en_ligne"   <?= $mode==='en_ligne'?'selected':''; ?>>En ligne</option>
    <option value="hybride"    <?= $mode==='hybride'?'selected':''; ?>>Hybride</option>
  </select>
  <button type="submit">Filtrer</button>
</form>

<div class="grid">
<?php foreach($items as $f): ?>
  <?php
    $slug = $f['slug'] ?: slugify($f['titre']);
    $url  = "/formations/".$slug;
  ?>
  <div class="card">
    <?php if(!empty($f['hero_image'])): ?>
      <a href="<?= h($url); ?>"><img src="/<?= h($f['hero_image']); ?>" alt="<?= h($f['titre']); ?>" loading="lazy" decoding="async"></a>
    <?php endif; ?>
    <div class="p">
      <span class="badge"><?= h($f['domaine']); ?></span>
      <h3 style="margin:10px 0 8px"><a href="<?= h($url); ?>"><?= h($f['titre']); ?></a></h3>
      <div class="small"><?= h(ucfirst(str_replace('_',' ', $f['mode']))); ?></div>
      <div class="meta">
        Présentiel: <?= number_format((int)$f['tarif_presentiel']); ?> FCFA |
        En ligne: <?= number_format((int)$f['tarif_en_ligne']); ?> FCFA
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

</div>
</body>
</html>
