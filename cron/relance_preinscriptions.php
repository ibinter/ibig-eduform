<?php
declare(strict_types=1);
/* =====================================================================
   IBIG EDUFORM — Relance automatique des préinscrits NON PAYÉS
   Stades : J+1 (relance1) et J+3 (relance2).
   - Cible : preinscriptions statut='nouvelle', non payées (aucun paiement
     'paye' avec le même email), formation encore à venir.
   - Envoi : mis en file dans notification_queue (envoyé par send_notifications.php).
   - Anti-doublon : colonnes relance1_at / relance2_at sur preinscriptions.

   À planifier UNE FOIS PAR JOUR (voir cron en bas de fichier).
   Exécution : CLI  →  php cron/relance_preinscriptions.php
               ou URL →  /cron/relance_preinscriptions.php?key=CHANGER_CETTE_CLE
===================================================================== */

/* Clé pour l'exécution via URL (change-la !). En CLI, aucune clé requise. */
const RELANCE_CRON_KEY = 'ibig-rlz-2026-k7m3q9';

if (php_sapi_name() !== 'cli') {
  if (($_GET['key'] ?? '') !== RELANCE_CRON_KEY) { http_response_code(403); exit('Forbidden'); }
  header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/whatsapp.php';

$pdo = Database::connect();

/* 0-bis) Désactivation des formations PASSÉES (mutualisé ici : quota de cron limité).
   Toute formation active dont la date est dépassée passe en 'inactive'. */
$nDesact = 0;
try {
  $stDes = $pdo->prepare("UPDATE formations
       SET statut = 'inactive', updated_at = NOW()
     WHERE statut = 'active'
       AND annee IS NOT NULL AND annee != 0
       AND date_debut IS NOT NULL
       AND COALESCE(date_fin, date_debut) < CURDATE()");
  $stDes->execute();
  $nDesact = $stDes->rowCount();
} catch (Throwable $e) { $nDesact = 0; }

/* 0) Colonnes anti-doublon (idempotent) */
try {
  $pdo->exec("ALTER TABLE preinscriptions
              ADD COLUMN IF NOT EXISTS relance1_at DATETIME NULL,
              ADD COLUMN IF NOT EXISTS relance2_at DATETIME NULL");
} catch (Throwable $e) { /* déjà présentes */ }

$appUrl  = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
$waPhone = function_exists('whatsapp_admin_phone') ? whatsapp_admin_phone() : '2250778882592';

/* Construit le corps HTML d'une relance */
function relance_email_html(array $p, string $stade, string $appUrl, string $waPhone): array {
  $prenom = trim((string)($p['prenoms'] ?? '')) ?: trim((string)($p['nom'] ?? '')) ?: 'Bonjour';
  $titre  = (string)($p['titre'] ?? 'votre formation');
  $slug   = (string)($p['slug'] ?? '');
  $fid    = (int)($p['formation_id'] ?? 0);
  $lienPaiement = $appUrl . '/paiement-inscription.php?formation_id=' . $fid;
  $lienFiche    = $slug !== '' ? ($appUrl . '/formation/' . rawurlencode($slug)) : ($appUrl . '/formation.php?id=' . $fid);
  $dateFr = !empty($p['date_debut']) ? date('d/m/Y', strtotime((string)$p['date_debut'])) : '';

  $accroche = $stade === 'j1'
    ? "Votre place pour <b>{$titre}</b> vous attend."
    : "Il reste peu de temps pour confirmer votre place à <b>{$titre}</b>.";
  $sujet = $stade === 'j1'
    ? "Finalisez votre inscription — {$titre}"
    : "Dernier rappel : votre place pour {$titre}";

  $waText = "Bonjour IBIG EDUFORM, je souhaite finaliser mon inscription à : {$titre}";
  $waLink = function_exists('whatsapp_wa_link')
          ? whatsapp_wa_link($waText, $waPhone)
          : ('https://wa.me/' . $waPhone . '?text=' . rawurlencode($waText));

  $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:auto;color:#0f172a">'
    . '<div style="background:#0a1733;color:#fff;padding:22px 26px;border-radius:16px 16px 0 0">'
    . '<h2 style="margin:0;font-size:20px">IBIG EDUFORM</h2></div>'
    . '<div style="border:1px solid #e3ebf6;border-top:0;border-radius:0 0 16px 16px;padding:26px">'
    . '<p style="font-size:16px">Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p style="font-size:15px;line-height:1.6">' . $accroche
    . ($dateFr ? " La session démarre le <b>{$dateFr}</b>." : '')
    . ' Réservez définitivement en réglant vos frais d\'inscription (50 000 FCFA).</p>'
    . '<p style="font-size:14px;background:#fff3d6;border:1px solid #f5c451;border-radius:10px;padding:10px 14px;color:#8a5a00">'
    . '🐦 Profitez encore de l\'<b>Offre Anticipée</b> tant que la date n\'approche pas — et des <b>places limitées</b>.</p>'
    . '<p style="text-align:center;margin:26px 0">'
    . '<a href="' . htmlspecialchars($lienPaiement, ENT_QUOTES, 'UTF-8') . '" style="background:#e8242c;color:#fff;text-decoration:none;font-weight:800;padding:14px 26px;border-radius:12px;display:inline-block">Finaliser mon inscription</a>'
    . '</p>'
    . '<p style="text-align:center;font-size:14px">ou <a href="' . htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') . '" style="color:#1aa851;font-weight:700">💬 en parler sur WhatsApp</a>'
    . ' · <a href="' . htmlspecialchars($lienFiche, ENT_QUOTES, 'UTF-8') . '" style="color:#1f3fe0">revoir le programme</a></p>'
    . '<hr style="border:0;border-top:1px solid #eef2f7;margin:20px 0">'
    . '<p style="font-size:12px;color:#7a8aa8">IBIG EDUFORM — INTERMARK BUSINESS INTERNATIONAL GROUP SARL · Abidjan · '
    . 'Si vous êtes déjà inscrit, ignorez ce message.</p>'
    . '</div></div>';

  return [$sujet, $html];
}

/* Traite un stade (j1 = 1 jour, j2 = 3 jours) */
function traiter_stade(PDO $pdo, string $stade, int $jours, string $col, string $appUrl, string $waPhone): int {
  $sql = "SELECT p.id, p.nom, p.prenoms, p.email, p.formation_id,
                 f.titre, f.slug, f.date_debut
          FROM preinscriptions p
          JOIN formations f ON f.id = p.formation_id
          WHERE p.statut = 'nouvelle'
            AND p.email IS NOT NULL AND p.email <> ''
            AND DATE(p.created_at) = (CURDATE() - INTERVAL :j DAY)
            AND p.$col IS NULL
            AND f.statut = 'active'
            AND COALESCE(f.date_fin, f.date_debut) >= CURDATE()
            AND NOT EXISTS (
              SELECT 1 FROM paiements_inscription pay
              WHERE pay.customer_email = p.email AND pay.statut = 'paye'
            )
          LIMIT 200";
  $st = $pdo->prepare($sql);
  $st->execute([':j' => $jours]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $n = 0;
  $upd = $pdo->prepare("UPDATE preinscriptions SET $col = NOW() WHERE id = ?");
  foreach ($rows as $p) {
    [$sujet, $html] = relance_email_html($p, $stade, $appUrl, $waPhone);
    if (function_exists('queue_email')) { queue_email((string)$p['email'], $sujet, $html); }
    else { send_email((string)$p['email'], $sujet, $html); }
    $upd->execute([(int)$p['id']]);
    $n++;
  }
  return $n;
}

$n1 = traiter_stade($pdo, 'j1', 1, 'relance1_at', $appUrl, $waPhone);
$n2 = traiter_stade($pdo, 'j2', 3, 'relance2_at', $appUrl, $waPhone);

$msg = date('Y-m-d H:i') . " — Formations passées désactivées : {$nDesact} · Relances mises en file : J+1 = {$n1}, J+3 = {$n2}\n";
echo $msg;
try { error_log('[RELANCE] ' . trim($msg)); } catch (Throwable $e) {}

/* =====================================================================
   PLANIFICATION (cron) — 1 fois/jour, ex. 09h00 :
     0 9 * * *  php /chemin/vers/site/cron/relance_preinscriptions.php >/dev/null 2>&1
   ou via URL (cron d'hébergeur) :
     0 9 * * *  curl -s "https://ibig-eduform.com/cron/relance_preinscriptions.php?key=CHANGER_CETTE_CLE"
   ⚠️ Le cron send_notifications.php doit tourner (il envoie la file).
===================================================================== */
