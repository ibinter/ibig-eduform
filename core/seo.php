<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php'; // ✅ AJOUT OBLIGATOIRE

function seo_meta(
  string $title,
  string $description,
  string $keywords = ''
): void {
  echo '<meta name="description" content="'.e($description).'">';
  if ($keywords) {
    echo '<meta name="keywords" content="'.e($keywords).'">';
  }
}
