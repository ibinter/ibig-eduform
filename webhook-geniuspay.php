<?php
declare(strict_types=1);

/* =====================================================
   WEBHOOK GENIUSPAY — confirmation automatique
   À configurer dans le tableau de bord GeniusPay :
     URL : https://ibig-eduform.com/webhook-geniuspay.php
   Sécurité : vérification HMAC-SHA256.
===================================================== */

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/geniuspay.php';
require_once __DIR__ . '/core/payment_plan.php';

$raw = file_get_contents('php://input') ?: '';
$sig = $_SERVER['HTTP_X_GENIUSPAY_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '';

/* Signature invalide → refus */
if (!geniuspay_verify_webhook($raw, (string)$sig)) {
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

$type = strtolower((string)($event['event'] ?? $event['type'] ?? ''));
$data = (array)($event['data'] ?? $event);

/* Référence dans metadata */
$reference = '';
if (!empty($data['metadata']) && is_array($data['metadata'])) {
    $reference = (string)($data['metadata']['reference'] ?? '');
}
if ($reference === '') {
    $reference = (string)($data['reference'] ?? '');
}

if ($reference === '') {
    http_response_code(200);
    echo 'no reference';
    exit;
}

try {
    $pdo = Database::connect();
    payment_plan_ensure_tables($pdo);

    $isSuccess   = in_array($type, ['payment.success','payment.successful','payment.paid','transaction.success'], true);
    $isFailure   = in_array($type, ['payment.failed','payment.failure','payment.cancelled','transaction.failed'], true);

    if ($isSuccess) {
        /* Marquer la ligne payée */
        try {
            $pdo->prepare("UPDATE paiements_inscription SET statut='paye', paid_at=NOW() WHERE reference=?")
                ->execute([$reference]);
        } catch (Throwable $e) {
            try { $pdo->prepare("UPDATE paiements_inscription SET statut='paye' WHERE reference=?")->execute([$reference]); } catch (Throwable $e2) {}
        }

        /* Re-lire la ligne */
        $r = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference=? LIMIT 1");
        $r->execute([$reference]);
        $prow = $r->fetch(PDO::FETCH_ASSOC) ?: null;

        /* Reçu email — idempotent */
        if ($prow && empty($prow['email_sent'])) {
            try {
                require_once __DIR__ . '/core/inscription_email.php';
                if (inscription_send_receipt($pdo, $prow)) {
                    try { $pdo->prepare("UPDATE paiements_inscription SET email_sent=1 WHERE reference=?")->execute([$reference]); } catch (Throwable $e) {}
                }
            } catch (Throwable $e) { error_log('[WH GP RECU] ' . $e->getMessage()); }
        }

        /* Vérifier si le plan est complet */
        if ($prow) {
            $planId = (int)($prow['plan_id'] ?? 0);
            if ($planId > 0) {
                try {
                    $ps = $pdo->prepare("SELECT montant_total FROM paiement_plans WHERE id=? LIMIT 1");
                    $ps->execute([$planId]);
                    $planRow = $ps->fetch(PDO::FETCH_ASSOC);
                    if ($planRow) {
                        $paid = payment_plan_paid_amount($pdo, $planId);
                        if ($paid >= (int)$planRow['montant_total']) {
                            $pdo->prepare("UPDATE paiement_plans SET statut='complete' WHERE id=?")
                                ->execute([$planId]);
                        }
                    }
                } catch (Throwable $e) { error_log('[WH GP PLAN] ' . $e->getMessage()); }
            }
        }

        /* Notification affilié IBIG PARTNERS */
        try {
            require_once __DIR__ . '/includes/ibig-affiliate.php';
            $ibigRef = (string)($prow['ibig_ref'] ?? $data['metadata']['ibig_ref'] ?? '');
            if ($ibigRef) { $_COOKIE['ibig_ref'] = strtoupper($ibigRef); }
            $formationSlug = (string)($prow['formation_slug'] ?? $prow['slug'] ?? 'formation');
            $montant       = (int)($prow['montant'] ?? 0);
            $nomClient     = (string)($prow['customer_name'] ?? '');
            $emailClient   = (string)($prow['customer_email'] ?? '');
            ibig_affiliate_report_sale($formationSlug, $reference, $montant, $nomClient, $emailClient);
        } catch (Throwable $e) { error_log('[WH GP AFFILIATE] ' . $e->getMessage()); }

    } elseif ($isFailure) {
        $pdo->prepare("UPDATE paiements_inscription SET statut='echoue' WHERE reference=?")
            ->execute([$reference]);
    }

    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    error_log('[GENIUSPAY WEBHOOK] ' . $e->getMessage());
    http_response_code(500);
    echo 'error';
}
