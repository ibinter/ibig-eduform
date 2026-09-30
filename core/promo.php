<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Offre de réduction
   Fenêtre glissante : mois en cours + mois suivant.
   Éligible si inscription au moins 3 jours avant le début.
   Remise fixe : -20 000 FCFA en ligne | -25 000 FCFA présentiel
========================================================= */

define('PROMO_REMISE_EN_LIGNE',   20000);
define('PROMO_REMISE_PRESENTIEL', 25000);

if (!function_exists('promo_earlybird')) {
    /**
     * @return array{eligible:bool,deadline:int,start:int}
     */
    function promo_earlybird(array $f): array
    {
        $now       = time();
        $curM      = (int)date('n', $now);
        $curY      = (int)date('Y', $now);
        $nextM     = $curM === 12 ? 1 : $curM + 1;
        $nextY     = $curM === 12 ? $curY + 1 : $curY;

        $eligible = false;
        $deadline = 0;
        $start    = 0;

        if (!empty($f['date_debut'])) {
            $startTs = strtotime((string)$f['date_debut'] . ' 09:00:00');
            if ($startTs) {
                $start    = $startTs;
                $debutM   = (int)date('n', $startTs);
                $debutY   = (int)date('Y', $startTs);
                $inWindow = ($debutM === $curM && $debutY === $curY)
                         || ($debutM === $nextM && $debutY === $nextY);
                if ($inWindow) {
                    $deadline = mktime(23, 59, 59, $debutM, (int)date('j', $startTs) - 3, $debutY);
                    $eligible = $deadline > $now;
                }
            }
        }

        return [
            'eligible' => $eligible,
            'deadline' => $deadline,
            'start'    => $start,
        ];
    }
}

if (!function_exists('formation_passee')) {
    function formation_passee(array $f): bool
    {
        $ref = !empty($f['date_fin']) ? $f['date_fin'] : ($f['date_debut'] ?? null);
        if (empty($ref)) { return false; }
        $t = strtotime((string)$ref . ' 23:59:59');
        return $t !== false && $t < time();
    }
}

if (!function_exists('places_disponibles')) {
    function places_disponibles(array $f): ?int
    {
        if (formation_passee($f)) { return null; }
        if (empty($f['date_debut'])) { return null; }
        $start = strtotime((string)$f['date_debut'] . ' 09:00:00');
        if (!$start) { return null; }
        $days = (int) ceil(($start - time()) / 86400);
        if ($days > 15) { return 15; }
        if ($days > 5)  { return 10; }
        if ($days > 1)  { return 5;  }
        return 1;
    }
}

/* Fonctions de compatibilité avec l'ancien code */
if (!function_exists('promo_earlybird_amount')) {
    function promo_earlybird_amount(array $f): int { return PROMO_REMISE_PRESENTIEL; }
}
if (!function_exists('promo_remise')) {
    function promo_remise(int $montant, array $promo): int { return 0; }
}
if (!function_exists('promo_net')) {
    function promo_net(int $montant, array $promo): int { return $montant; }
}
