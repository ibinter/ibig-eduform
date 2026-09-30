<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Parrainage (remise parrain & filleul, % configurable via setting referral_percent, défaut 5%)
========================================================= */

if (!defined('REFERRAL_PERCENT')) { define('REFERRAL_PERCENT', 10); } // % (parrain 10% + filleul 10% par formation)
if (!defined('REFERRAL_COOKIE_DAYS')) { define('REFERRAL_COOKIE_DAYS', 30); }

if (!function_exists('referral_percent')) {
    function referral_percent(): int {
        return function_exists('setting_int') ? setting_int('referral_percent', REFERRAL_PERCENT) : REFERRAL_PERCENT;
    }
}

if (!function_exists('referral_code_new')) {
    function referral_code_new(): string {
        return 'IBIG' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 6));
    }
}

if (!function_exists('referral_lookup')) {
    /** Retourne la ligne parrain pour un code, ou null. */
    function referral_lookup(PDO $pdo, string $code): ?array {
        $code = strtoupper(trim($code));
        if ($code === '') return null;
        try {
            $st = $pdo->prepare("SELECT * FROM parrainages WHERE code = ? LIMIT 1");
            $st->execute([$code]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) { return null; }
    }
}

if (!function_exists('referral_create')) {
    /**
     * Crée (ou réutilise) un code de parrainage pour un contact donné.
     * @return array{code:string,nouveau:bool}|null
     */
    function referral_create(PDO $pdo, string $nom, string $contact): ?array {
        $nom = trim($nom); $contact = trim($contact);
        if ($nom === '' || $contact === '') return null;
        try {
            $st = $pdo->prepare("SELECT code FROM parrainages WHERE parrain_contact = ? LIMIT 1");
            $st->execute([$contact]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return ['code' => (string)$row['code'], 'nouveau' => false];
        } catch (Throwable $e) { /* table absente ? */ }

        for ($i = 0; $i < 6; $i++) {
            $code = referral_code_new();
            try {
                $st = $pdo->prepare("INSERT INTO parrainages (code, parrain_nom, parrain_contact, ip_address, created_at)
                                     VALUES (?,?,?,?,NOW())");
                $st->execute([
                    $code, mb_substr($nom, 0, 190), mb_substr($contact, 0, 190),
                    substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ]);
                return ['code' => $code, 'nouveau' => true];
            } catch (Throwable $e) { /* collision : on réessaie */ }
        }
        return null;
    }
}

if (!function_exists('referral_capture')) {
    /** Capte ?ref=CODE (s'il est valide) dans la session + un cookie 30 j. */
    function referral_capture(PDO $pdo): void {
        $ref = isset($_GET['ref']) ? strtoupper(trim((string)$_GET['ref'])) : '';
        if ($ref === '') return;
        if (referral_lookup($pdo, $ref)) {
            $_SESSION['ref_code'] = $ref;
            @setcookie('ibig_ref', $ref, time() + REFERRAL_COOKIE_DAYS * 86400, '/');
        }
    }
}

if (!function_exists('referral_active_code')) {
    /** Code de parrainage actif (session ou cookie), '' sinon. */
    function referral_active_code(): string {
        return strtoupper((string)($_SESSION['ref_code'] ?? ($_COOKIE['ibig_ref'] ?? '')));
    }
}

if (!function_exists('referral_remise')) {
    /** Remise filleul (FCFA) sur un montant, arrondie à 100. */
    function referral_remise(int $montant): int {
        if ($montant <= 0) return 0;
        $pct = referral_percent();
        $r = (int)(round(($montant * $pct / 100) / 100) * 100);
        return ($r > 0 && $r < $montant) ? $r : 0;
    }
}
