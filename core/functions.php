<?php
declare(strict_types=1);

/* =========================================
   FONCTIONS MÉTIER — IBIG EDUFORM
   ⚠️ AUCUNE FONCTION GÉNÉRIQUE ICI
========================================= */

function tarifSelonMode(array $f): int
{
    if ($f['mode'] === 'en_ligne')   { return (int)$f['tarif_en_ligne']; }
    if ($f['mode'] === 'presentiel') { return (int)$f['tarif_presentiel']; }
    if ($f['mode'] === 'hybride')    { return max((int)$f['tarif_presentiel'], (int)$f['tarif_en_ligne']); }
    return 0;
}
