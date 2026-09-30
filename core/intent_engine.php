<?php
declare(strict_types=1);

/**
 * /core/intent_engine.php
 * ICE — Intent & Conversion Engine
 * - Met à jour intent_sessions
 * - Déclenche des alertes admin (admin_alerts)
 */

function ice_points_for_action(string $action): int
{
  // Actions normalisées (tu peux en ajouter)
  $map = [
    'page_view'            => 1,
    'engaged_view'         => 2, // ex: > 60s
    'scroll_70'            => 2,
    'share_facebook'       => 3,
    'share_whatsapp'       => 3,
    'share_linkedin'       => 3,
    'share_x'              => 3,
    'copy_link'            => 2,
    'whatsapp_click'       => 4,
    'preinscription_start' => 5,
    'preinscription_submit'=> 6,
    'pay_click'            => 5,
  ];

  return (int)($map[$action] ?? 0);
}

function ice_is_share_action(string $action): bool
{
  return (strpos($action, 'share_') === 0) || ($action === 'copy_link');
}

function ice_create_alert(PDO $pdo, string $type, string $message, ?int $formationId, string $sessionKey, array $meta = []): void
{
  // Anti-spam : n'envoie pas la même alerte pour la même session dans les 2 dernières minutes
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM admin_alerts
    WHERE type = ?
      AND session_key = ?
      AND created_at >= (NOW() - INTERVAL 2 MINUTE)
  ");
  $stmt->execute([$type, $sessionKey]);
  $exists = (int)$stmt->fetchColumn();

  if ($exists > 0) return;

  $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;

  $ins = $pdo->prepare("
    INSERT INTO admin_alerts (type, message, formation_id, session_key, meta_json)
    VALUES (?, ?, ?, ?, ?)
  ");
  $ins->execute([$type, $message, $formationId, $sessionKey, $metaJson]);
}

/**
 * Applique un événement au moteur d'intention.
 *
 * $event = [
 *  'session_key' => '...',
 *  'action' => 'whatsapp_click',
 *  'label' => '...',
 *  'formation_id' => 12,
 *  'utm_source' => 'facebook',
 *  'utm_campaign' => 'janv2026',
 *  'page_url' => '/formation/leadership-feminin'
 * ]
 */
function ice_apply_event(PDO $pdo, array $event): void
{
  $sessionKey  = (string)($event['session_key'] ?? '');
  $action      = (string)($event['action'] ?? '');
  $label       = (string)($event['label'] ?? '');
  $formationId = isset($event['formation_id']) && $event['formation_id'] !== '' ? (int)$event['formation_id'] : null;
  $utmSource   = (string)($event['utm_source'] ?? '');
  $utmCampaign = (string)($event['utm_campaign'] ?? '');
  $pageUrl     = (string)($event['page_url'] ?? '');

  if ($sessionKey === '' || $action === '') return;

  $points = ice_points_for_action($action);

  // 1) Upsert session
  $up = $pdo->prepare("
    INSERT INTO intent_sessions
      (session_key, formation_id, score, events_count, last_action, last_label, utm_source, utm_campaign, first_seen, last_seen, is_hot)
    VALUES
      (?, ?, ?, 1, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NOW(), NOW(), 0)
    ON DUPLICATE KEY UPDATE
      formation_id = COALESCE(VALUES(formation_id), formation_id),
      score        = score + VALUES(score),
      events_count = events_count + 1,
      last_action  = VALUES(last_action),
      last_label   = VALUES(last_label),
      utm_source   = COALESCE(utm_source, VALUES(utm_source)),
      utm_campaign = COALESCE(utm_campaign, VALUES(utm_campaign)),
      last_seen    = NOW()
  ");
  $up->execute([$sessionKey, $formationId, $points, $action, mb_substr($label, 0, 255), $utmSource, $utmCampaign]);

  // 2) Lire l'état courant
  $st = $pdo->prepare("SELECT score, is_hot, formation_id FROM intent_sessions WHERE session_key = ? LIMIT 1");
  $st->execute([$sessionKey]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return;

  $scoreNow    = (int)($row['score'] ?? 0);
  $isHot       = (int)($row['is_hot'] ?? 0);
  $formationId = isset($row['formation_id']) ? (int)$row['formation_id'] : $formationId;

  // 3) Déclencheurs d'alertes

  // A) WhatsApp click = intention très forte
  if ($action === 'whatsapp_click') {
    $msg = 'Alerte intention : clic WhatsApp (visiteur chaud).';
    ice_create_alert($pdo, 'whatsapp_click', $msg, $formationId, $sessionKey, [
      'page_url' => $pageUrl,
      'utm_source' => $utmSource,
      'utm_campaign' => $utmCampaign
    ]);
  }

  // B) Partage = signal viral
  if (ice_is_share_action($action)) {
    $msg = 'Signal viral : partage d\'une formation.';
    ice_create_alert($pdo, 'share', $msg, $formationId, $sessionKey, [
      'action' => $action,
      'page_url' => $pageUrl
    ]);
  }

  // C) Score chaud (une seule fois)
  if ($isHot === 0 && $scoreNow >= 6) {
    $pdo->prepare("UPDATE intent_sessions SET is_hot = 1 WHERE session_key = ?")->execute([$sessionKey]);

    $msg = 'Visiteur très engagé : score d\'intention élevé.';
    ice_create_alert($pdo, 'hot_intent', $msg, $formationId, $sessionKey, [
      'score' => $scoreNow,
      'page_url' => $pageUrl,
      'utm_source' => $utmSource,
      'utm_campaign' => $utmCampaign
    ]);
  }

  // D) Début préinscription = alerte conversion
  if ($action === 'preinscription_start') {
    $msg = 'Préinscription commencée : risque d\'abandon (à relancer).';
    ice_create_alert($pdo, 'preinscription_start', $msg, $formationId, $sessionKey, [
      'page_url' => $pageUrl
    ]);
  }
}
