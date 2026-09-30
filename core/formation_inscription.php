<?php
declare(strict_types=1);

/**
 * core/formation_inscription.php
 * --------------------------------------------------
 * LOGIQUE OFFICIELLE DES FRAIS D’INSCRIPTION – IBIG EDUFORM
 *
 * RÈGLES (STRICTES) :
 * 1) L’ADMIN EST TOUJOURS PRIORITAIRE
 *    - Si frais_inscription > 0 → on UTILISE cette valeur
 *
 * 2) AUTOMATIQUE UNIQUEMENT SI frais_inscription = 0
 *    - Formation GROUPÉE  → 50 000 FCFA
 *    - Formation UNIQUE   → 15 000 FCFA
 *
 * 3) AUCUNE LOGIQUE AUTOMATIQUE DANS L’ADMIN
 *    - CE FICHIER EST EXCLUSIVEMENT POUR LE PUBLIC
 *
 * 4) AUCUN ÉCRASEMENT DE DONNÉES
 * --------------------------------------------------
 */

/**
 * Retourne la configuration d’inscription d’une formation
 *
 * @param array $f  Données formation (ligne SQL)
 * @return array{
 *   montant:int,
 *   lien:string,
 *   source:string
 * }
 */
function getInscriptionConfig(array $f): array
{
    /* ===============================
       1️⃣ PRIORITÉ ABSOLUE : ADMIN
    =============================== */
    if (
        isset($f['frais_inscription'])
        && (int)$f['frais_inscription'] > 0
    ) {
        return [
            'montant' => (int)$f['frais_inscription'],
            'lien'    => !empty($f['paiement_lien'])
                ? (string)$f['paiement_lien']
                : 'https://pay.intermark-business.com/inscription',
            'source'  => 'admin'
        ];
    }

    /* ===============================
       2️⃣ AUTOMATIQUE (ADMIN = 0)
    =============================== */

    // Sécurisation du type de formation
    $type = strtoupper((string)($f['type_formation'] ?? 'GROUPEE'));

    // Formation UNIQUE
    if ($type === 'UNIQUE') {
        return [
            'montant' => 15000,
            'lien'    => 'https://pay.intermark-business.com/inscription-unique',
            'source'  => 'auto_unique'
        ];
    }

    // Formation GROUPÉE (par défaut)
    return [
        'montant' => 50000,
        'lien'    => 'https://pay.intermark-business.com/inscription',
        'source'  => 'auto_groupee'
    ];
}
