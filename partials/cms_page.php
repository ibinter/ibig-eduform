<?php
/* =========================================================
   SHIM CMS — IBIG EDUFORM
   Usage en haut d'une page statique, juste après le require :
     $pageSlug = 'a-propos';
     require __DIR__ . '/partials/cms_page.php';
   Si une page 'slug' est PUBLIÉE en base, elle est rendue ici
   puis exit. Sinon, le fichier continue (contenu figé d'origine).
========================================================= */

require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/content.php';

$__p = isset($pageSlug) ? page_get((string)$pageSlug) : null;
if ($__p) {
    $pageTitle = $__p['title'];
    if (!empty($__p['meta_description'])) {
        $ogDesc = $__p['meta_description'];
    }
    include __DIR__ . '/header.php';
    ?>
    <main class="cms-page" style="max-width:920px;margin:48px auto 80px;padding:0 20px;line-height:1.7;color:#1f2937">
      <h1 style="font-size:2rem;margin-bottom:20px"><?= htmlspecialchars($__p['title']); ?></h1>
      <div class="cms-content"><?= $__p['content']; /* HTML saisi par l'admin */ ?></div>
    </main>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}
