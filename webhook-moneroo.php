<?php
declare(strict_types=1);

/* =====================================================
   WEBHOOK MONEROO — confirmation automatique des paiements
   À configurer dans le tableau de bord Moneroo :
     URL : https://ibig-eduform.com/webhook-moneroo.php
   Sécurité : vérification de signature HMAC-SHA256.
===================================================== */

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/moneroo.php';

$raw = file_get_contents('php://input') ?: '';
$sig = $_SERVER['HTTP_X_MONEROO_SIGNATURE'] ?? '';

/* Signature invalide -> on refuse */
if (!moneroo_verify_webhook($raw, (string)$sig)) {
    http_response_code(401);
    echo 'invalid signature';
    exit;
}

$event = json_decode($raw, true);
if (!is_array($event)) {
    http_response_code(400);
    echo 'bad payload';
    exit;
}

$type = (string)($event['event'] ?? '');
$data = (array)($event['data'] ?? []);

/* Référence transmise dans metadata lors de l'initialisation */
$reference = '';
if (!empty($data['metadata']) && is_array($data['metadata'])) {
    $reference = (string)($data['metadata']['reference'] ?? '');
}

if ($reference === '') {
    http_response_code(200);
    echo 'no reference';
    exit;
}

try {
    $pdo = Database::connect();

    if ($type === 'payment.success') {
        try {
            $pdo->prepare("UPDATE paiements_inscription SET statut='paye', paid_at=NOW() WHERE reference=?")
                ->execute([$reference]);
        } catch (Throwable $e) {
            $pdo->prepare("UPDATE paiements_inscription SET statut='paye' WHERE reference=?")->execute([$reference]);
        }
        /* Reçu d'inscription par email — une seule fois (idempotent) */
        try {
            $r = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference=? LIMIT 1");
            $r->execute([$reference]);
            $prow = $r->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($prow && empty($prow['email_sent'])) {
                require_once __DIR__ . '/core/inscription_email.php';
                if (inscription_send_receipt($pdo, $prow)) {
                    try { $pdo->prepare("UPDATE paiements_inscription SET email_sent=1 WHERE reference=?")->execute([$reference]); } catch (Throwable $e) {}
                }
            }
            /* Notification affilié IBIG PARTNERS */
            if ($prow) {
                require_once __DIR__ . '/includes/ibig-affiliate.php';
                $formationSlug = (string)($prow['formation_slug'] ?? $prow['slug'] ?? 'formation');
                $montant       = (int)($prow['montant'] ?? $prow['amount'] ?? 0);
                $nomClient     = trim(($prow['prenom'] ?? '') . ' ' . ($prow['nom'] ?? '')) ?: ($prow['nom_complet'] ?? '');
                $emailClient   = (string)($prow['email'] ?? '');
                // Le ref affilié est stocké dans la ligne ou dans les metadata Moneroo
                $ibigRef = (string)($prow['ibig_ref'] ?? $data['metadata']['ibig_ref'] ?? '');
                if ($ibigRef) {
                    $_COOKIE['ibig_ref'] = strtoupper($ibigRef);
                }
                ibig_affiliate_report_sale($formationSlug, $reference, $montant, $nomClient, $emailClient);
            }
        } catch (Throwable $e) { error_log('[WEBHOOK RECU] ' . $e->getMessage()); }
    } elseif (in_array($type, ['payment.failed', 'payment.cancelled'], true)) {
        $pdo->prepare("UPDATE paiements_inscription SET statut='echoue' WHERE reference=?")->execute([$reference]);
    }

    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    error_log('[MONEROO WEBHOOK] ' . $e->getMessage());
    http_response_code(500);
    echo 'error';
}
