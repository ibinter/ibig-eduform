<?php
declare(strict_types=1);

/* =========================================
   FONCTIONS MÉTIER — IBIG EDUFORM
   ⚠️ AUCUNE FONCTION GÉNÉRIQUE ICI
========================================= */

function tarifSelonMode(array $f): int
{
    return match ($f['mode']) {
        'en_ligne'   => (int)$f['tarif_en_ligne'],
        'presentiel' => (int)$f['tarif_presentiel'],
        'hybride'    => max(
                            (int)$f['tarif_presentiel'],
                            (int)$f['tarif_en_ligne']
                        ),
        default      => 0
    };
}
