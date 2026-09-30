<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Email de reçu d'inscription (HTML stylé)
========================================================= */
require_once __DIR__ . '/notifications.php';

if (!function_exists('inscription_receipt_html')) {
    function inscription_receipt_html(array $row, ?array $formation): string
    {
        $app    = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
        $logo   = $app . '/assets/images/logo.png';
        $titre  = $formation['titre'] ?? '';
        $montant = number_format((int)($row['montant'] ?? 0), 0, ',', ' ') . ' FCFA';
        $date   = !empty($row['paid_at']) ? date('d/m/Y à H:i', strtotime((string)$row['paid_at'])) : date('d/m/Y');
        $ref    = (string)($row['reference'] ?? '');
        $recuUrl = $app . '/recu-inscription.php?ref=' . urlencode($ref);
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        return '
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
          <div style="background:linear-gradient(135deg,#0F2742,#0B4AA6);color:#fff;padding:24px;text-align:center">
            <img src="' . $h($logo) . '" alt="IBIG EDUFORM" style="height:46px"><br>
            <h2 style="margin:10px 0 0;font-weight:800">Confirmation d\'inscription</h2>
          </div>
          <div style="padding:26px;color:#1f2937">
            <p>Bonjour ' . $h($row['customer_name'] ?? '') . ',</p>
            <p>Nous confirmons la bonne réception de votre paiement. Votre place est réservée. 🎉</p>
            <table style="width:100%;border-collapse:collapse;margin:18px 0">
              <tr><td style="padding:9px;border-bottom:1px solid #eee;color:#6b7280">Formation</td><td style="padding:9px;border-bottom:1px solid #eee;text-align:right;font-weight:700">' . $h($titre) . '</td></tr>
              <tr><td style="padding:9px;border-bottom:1px solid #eee;color:#6b7280">Référence</td><td style="padding:9px;border-bottom:1px solid #eee;text-align:right;font-weight:700">' . $h($ref) . '</td></tr>
              <tr><td style="padding:9px;border-bottom:1px solid #eee;color:#6b7280">Montant payé</td><td style="padding:9px;border-bottom:1px solid #eee;text-align:right;font-weight:900;color:#16a34a">' . $h($montant) . '</td></tr>
              <tr><td style="padding:9px;border-bottom:1px solid #eee;color:#6b7280">Date</td><td style="padding:9px;border-bottom:1px solid #eee;text-align:right">' . $h($date) . '</td></tr>
            </table>
            <p style="text-align:center;margin:22px 0">
              <a href="' . $h($recuUrl) . '" style="background:#0B4AA6;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:800">📄 Télécharger mon reçu</a>
            </p>
            <p style="color:#6b7280;font-size:13px">Notre équipe vous contactera avec les modalités pratiques (lien de connexion ou adresse). Une question ? WhatsApp : +225 07 78 88 25 92.</p>
          </div>
          <div style="background:#f3f4f6;padding:14px;text-align:center;color:#6b7280;font-size:12px">IBIG EDUFORM — Institut de formation professionnelle &amp; certifications</div>
        </div>';
    }
}

if (!function_exists('inscription_send_receipt')) {
    /** Envoie le reçu d'inscription au client (+ copie admin). Retourne true si envoyé. */
    function inscription_send_receipt(PDO $pdo, array $row): bool
    {
        $to = trim((string)($row['customer_email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) { return false; }

        $formation = null;
        if (!empty($row['formation_id'])) {
            try {
                $st = $pdo->prepare("SELECT titre FROM formations WHERE id = ? LIMIT 1");
                $st->execute([(int)$row['formation_id']]);
                $formation = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) { $formation = null; }
        }

        $subject = "Confirmation d'inscription — " . (string)($row['reference'] ?? '');
        $html    = inscription_receipt_html($row, $formation);

        $ok = notify_email($to, $subject, $html);
        /* Copie à l'institut (non bloquant) */
        if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
            try { notify_email(ADMIN_EMAIL, 'Nouvelle inscription payée — ' . (string)($row['reference'] ?? ''), $html); } catch (Throwable $e) {}
        }
        return $ok;
    }
}
