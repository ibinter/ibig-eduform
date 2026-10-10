<?php
/**
 * IBIG EDUFORM — Compteur de visites du site
 * Une visite = une session de navigateur. Le total part de l'historique
 * déjà présent dans site_events (sessions distinctes) puis s'incrémente.
 */
if (!function_exists('visit_counter_total')) {
  function visit_counter_total(PDO $pdo): int
  {
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_visit_counter (
      id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
      total BIGINT UNSIGNED NOT NULL DEFAULT 0,
      updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $total = $pdo->query("SELECT total FROM site_visit_counter WHERE id = 1")->fetchColumn();
    if ($total === false) {
      $base = 0;
      try {
        $base = (int)$pdo->query("SELECT COUNT(DISTINCT session_key) FROM site_events")->fetchColumn();
      } catch (Throwable $ignore) {}
      $pdo->prepare("INSERT IGNORE INTO site_visit_counter (id, total, updated_at) VALUES (1, ?, NOW())")->execute([$base]);
      $total = $pdo->query("SELECT total FROM site_visit_counter WHERE id = 1")->fetchColumn();
    }
    $total = (int)$total;

    if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['visit_counted'])) {
      $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
      if ($ua !== '' && !preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua)) {
        $pdo->exec("UPDATE site_visit_counter SET total = total + 1, updated_at = NOW() WHERE id = 1");
        $total++;
      }
      $_SESSION['visit_counted'] = 1;
    }
    return $total;
  }
}
