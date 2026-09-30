<?php
/* Formulaire partagé create/edit. Attend : $s (array), $error (string). */
$heroDir = dirname(__DIR__, 2) . '/assets/images/hero';
$existing = [];
foreach (glob("$heroDir/*.{jpg,jpeg,png,webp}", GLOB_BRACE) ?: [] as $p) {
    $existing[] = basename($p);
}
?>
<div class="page-head" style="margin-bottom:18px">
  <h1 style="margin:0"><?= e($pageTitle); ?></h1>
  <a href="index.php" class="btn btn-sm">← Retour</a>
</div>

<?php if (!empty($error)): ?>
  <div style="padding:12px;border:1px solid #ef4444;background:#fef2f2;color:#7f1d1d;border-radius:10px;margin-bottom:16px"><?= e($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="max-width:760px;display:grid;gap:14px">
  <?= csrf_field(); ?>

  <label>Position (ordre d’affichage)
    <input type="number" name="position" value="<?= e($s['position']); ?>" style="width:120px">
  </label>

  <fieldset style="border:1px solid #e5e7eb;border-radius:10px;padding:14px">
    <legend>Image de fond</legend>
    <label>Choisir une image déjà présente
      <input list="hero-imgs" name="image_existing" value="<?= e($s['image']); ?>" style="width:100%">
      <datalist id="hero-imgs">
        <?php foreach ($existing as $img): ?><option value="<?= e($img); ?>"><?php endforeach; ?>
      </datalist>
    </label>
    <p style="margin:8px 0 4px;color:#666">…ou téléverser une nouvelle image (JPEG/PNG/WebP, max 5 Mo) :</p>
    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
  </fieldset>

  <label>Sur-titre (kicker)
    <input name="kicker" value="<?= e($s['kicker']); ?>" style="width:100%">
  </label>

  <label>Titre (HTML autorisé : &lt;span&gt; pour la couleur)
    <input name="title" value="<?= e($s['title']); ?>" style="width:100%" required>
  </label>

  <label>Texte d’accroche
    <textarea name="lead" rows="2" style="width:100%"><?= e($s['lead']); ?></textarea>
  </label>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <label>Bouton 1 — libellé
      <input name="btn1_label" value="<?= e($s['btn1_label']); ?>" style="width:100%">
    </label>
    <label>Bouton 1 — lien
      <input name="btn1_href" value="<?= e($s['btn1_href']); ?>" style="width:100%">
    </label>
    <label>Bouton 2 — libellé
      <input name="btn2_label" value="<?= e($s['btn2_label']); ?>" style="width:100%">
    </label>
    <label>Bouton 2 — lien
      <input name="btn2_href" value="<?= e($s['btn2_href']); ?>" style="width:100%">
    </label>
  </div>

  <label>Panneau — titre
    <input name="panel_title" value="<?= e($s['panel_title']); ?>" style="width:100%">
  </label>
  <label>Panneau — texte
    <textarea name="panel_text" rows="2" style="width:100%"><?= e($s['panel_text']); ?></textarea>
  </label>
  <label>Panneau — liste à puces (une ligne = une puce)
    <textarea name="panel_list" rows="4" style="width:100%"><?= e($s['panel_list']); ?></textarea>
  </label>

  <label style="display:flex;align-items:center;gap:8px">
    <input type="checkbox" name="is_active" <?= $s['is_active'] ? 'checked' : ''; ?>>
    Slide actif (visible sur le site)
  </label>

  <div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
  </div>
</form>
