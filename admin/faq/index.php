<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — FAQ (questions / réponses du site)
============================================================ */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$pdo = Database::connect();

/* ---------- ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'add') {
        $q = trim((string)($_POST['question'] ?? ''));
        $r = trim((string)($_POST['reponse'] ?? ''));
        if ($q !== '' && $r !== '') {
            $ordre = (int)$pdo->query("SELECT COALESCE(MAX(ordre),0)+1 FROM faq")->fetchColumn();
            $pdo->prepare("INSERT INTO faq (question,reponse,ordre,actif,created_at) VALUES (?,?,?,1,NOW())")->execute([mb_substr($q,0,255),$r,$ordre]);
        }
        redirect('index.php');
    }
    if ($act === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $q = trim((string)($_POST['question'] ?? ''));
        $r = trim((string)($_POST['reponse'] ?? ''));
        $ordre = (int)($_POST['ordre'] ?? 0);
        $actif = !empty($_POST['actif']) ? 1 : 0;
        if ($id > 0 && $q !== '' && $r !== '') {
            $pdo->prepare("UPDATE faq SET question=?, reponse=?, ordre=?, actif=? WHERE id=?")->execute([mb_substr($q,0,255),$r,$ordre,$actif,$id]);
        }
        redirect('index.php');
    }
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { $pdo->prepare("DELETE FROM faq WHERE id=?")->execute([$id]); }
        redirect('index.php');
    }
    if ($act === 'import') {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM faq")->fetchColumn();
        if ($count === 0) {
            $def = require __DIR__ . '/../../core/faq_default.php';
            $ins = $pdo->prepare("INSERT INTO faq (question,reponse,ordre,actif,created_at) VALUES (?,?,?,1,NOW())");
            $i = 1;
            foreach ($def as $row) { $ins->execute([mb_substr($row[0],0,255), $row[1], $i++]); }
        }
        redirect('index.php?imported=1');
    }
    redirect('index.php');
}

$rows = $pdo->query("SELECT * FROM faq ORDER BY ordre ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'FAQ'; $activeMenu = 'faq';
ob_start();
?>
<style>
  .faq-admin .row{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:12px}
  .faq-admin label{font-size:12px;color:#64748b;font-weight:700;display:block;margin:6px 0 3px}
  .faq-admin input[type=text],.faq-admin textarea{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;font-size:14px;font-family:inherit}
  .faq-admin .meta{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:8px}
  .faq-admin .meta input[type=number]{width:80px;padding:8px;border:1px solid #cbd5e1;border-radius:8px}
  .addbox{background:#f0f9ff;border:1px solid #bae6fd}
</style>
<div class="admin-wrap faq-admin">

  <div class="toolbar" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h2 style="margin:0">❓ FAQ du site</h2>
    <?php if (!$rows): ?>
      <form method="post" onsubmit="return confirm('Importer les 25 questions par défaut ?');">
        <?= csrf_field(); ?><input type="hidden" name="action" value="import">
        <button class="btn btn-primary">⬇ Importer la FAQ par défaut</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['imported'])): ?><div style="margin-bottom:12px;padding:10px 14px;border-radius:10px;background:#DCFCE7;border:1px solid #16a34a;color:#166534">✅ FAQ par défaut importée. Vous pouvez tout éditer ci-dessous.</div><?php endif; ?>

  <!-- AJOUT -->
  <div class="row addbox">
    <form method="post">
      <?= csrf_field(); ?><input type="hidden" name="action" value="add">
      <label>Nouvelle question</label>
      <input type="text" name="question" required placeholder="Votre question…">
      <label>Réponse</label>
      <textarea name="reponse" rows="2" required placeholder="La réponse…"></textarea>
      <div class="meta"><button class="btn btn-primary">➕ Ajouter</button></div>
    </form>
  </div>

  <!-- LISTE / ÉDITION -->
  <?php if (!$rows): ?>
    <p style="color:#64748b">Aucune entrée. La page FAQ affiche la version par défaut tant que cette liste est vide. Cliquez « Importer la FAQ par défaut » pour l'éditer.</p>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <div class="row">
      <form method="post">
        <?= csrf_field(); ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
        <label>Question</label>
        <input type="text" name="question" value="<?= e($r['question']); ?>" required>
        <label>Réponse</label>
        <textarea name="reponse" rows="2" required><?= e($r['reponse']); ?></textarea>
        <div class="meta">
          <span><label style="display:inline">Ordre</label> <input type="number" name="ordre" value="<?= (int)$r['ordre']; ?>"></span>
          <label style="display:inline;color:#0f172a"><input type="checkbox" name="actif" value="1" <?= !empty($r['actif'])?'checked':''; ?>> Visible</label>
          <button class="btn btn-primary">💾 Enregistrer</button>
        </div>
      </form>
      <form method="post" onsubmit="return confirm('Supprimer cette question ?');" style="margin-top:8px">
        <?= csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
        <button class="btn" style="border-color:#fca5a5;color:#b91c1c">✖ Supprimer</button>
      </form>
    </div>
  <?php endforeach; ?>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
