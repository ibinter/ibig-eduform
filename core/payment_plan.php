<?php
declare(strict_types=1);

/* =========================================================
   PAYMENT PLAN — Calcul des échéances d'inscription
   Règles IBIG EDUFORM :
     ≤ 100 000 FCFA  → paiement unique à l'inscription
     100 001–200 000 → 50 % à l'inscription + 50 % solde
     > 200 000       → 40 % inscription | 30 % début formation | 30 % avant fin
   Les clients peuvent toujours payer le tout ou un montant
   supérieur au minimum requis.
========================================================= */

if (!function_exists('payment_plan')) {
    /**
     * Retourne le plan d'échéances pour un montant total de formation.
     *
     * Retour :
     *   type        : 'unique' | '2tranches' | '3tranches'
     *   acompte     : montant NET minimum dû à l'inscription (FCFA)
     *   tranches    : tableau de ['num', 'pct', 'label', 'montant', 'echeance']
     */
    function payment_plan(int $total): array
    {
        if ($total <= 0) {
            return ['type' => 'unique', 'acompte' => 0, 'tranches' => []];
        }

        if ($total <= 100000) {
            /* Paiement unique */
            return [
                'type'    => 'unique',
                'acompte' => $total,
                'tranches' => [
                    ['num' => 1, 'pct' => 100, 'label' => 'Paiement intégral à l\'inscription', 'montant' => $total, 'echeance' => 'inscription'],
                ],
            ];
        }

        if ($total <= 200000) {
            /* 2 tranches : 50 % / 50 % */
            $t1 = payment_plan_round((int)round($total * 0.5));
            $t2 = $total - $t1;
            return [
                'type'    => '2tranches',
                'acompte' => $t1,
                'tranches' => [
                    ['num' => 1, 'pct' => 50, 'label' => '50 % à l\'inscription',           'montant' => $t1, 'echeance' => 'inscription'],
                    ['num' => 2, 'pct' => 50, 'label' => '50 % restant (avant la formation)', 'montant' => $t2, 'echeance' => 'avant_debut'],
                ],
            ];
        }

        /* 3 tranches : 40 % / 30 % / 30 % */
        $t1 = payment_plan_round((int)round($total * 0.40));
        $t2 = payment_plan_round((int)round($total * 0.30));
        $t3 = $total - $t1 - $t2;
        return [
            'type'    => '3tranches',
            'acompte' => $t1,
            'tranches' => [
                ['num' => 1, 'pct' => 40, 'label' => '40 % à l\'inscription',          'montant' => $t1, 'echeance' => 'inscription'],
                ['num' => 2, 'pct' => 30, 'label' => '30 % au début de la formation',  'montant' => $t2, 'echeance' => 'debut_formation'],
                ['num' => 3, 'pct' => 30, 'label' => '30 % avant la fin de formation', 'montant' => $t3, 'echeance' => 'avant_fin'],
            ],
        ];
    }
}

if (!function_exists('payment_plan_round')) {
    /** Arrondit un montant au multiple de 500 le plus proche (confort FCFA). */
    function payment_plan_round(int $amount): int
    {
        return (int)(round($amount / 500) * 500);
    }
}

if (!function_exists('payment_plan_create')) {
    /**
     * Crée (ou retrouve) la table paiement_plans et insère un plan.
     * Retourne l'ID du plan créé.
     */
    function payment_plan_create(PDO $pdo, array $data): int
    {
        /* Auto-create tables si nécessaire */
        payment_plan_ensure_tables($pdo);

        $pdo->prepare("
            INSERT INTO paiement_plans
              (formation_id, etudiant_nom, etudiant_email, etudiant_phone,
               montant_total, plan_type, acompte_minimum, statut, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'en_cours', NOW())
        ")->execute([
            (int)$data['formation_id'],
            (string)$data['etudiant_nom'],
            (string)$data['etudiant_email'],
            (string)$data['etudiant_phone'],
            (int)$data['montant_total'],
            (string)$data['plan_type'],
            (int)$data['acompte_minimum'],
        ]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('payment_plan_ensure_tables')) {
    /** Crée les tables de plan si elles n'existent pas encore. */
    function payment_plan_ensure_tables(PDO $pdo): void
    {
        static $done = false;
        if ($done) { return; }
        $done = true;

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS paiement_plans (
                id               INT          NOT NULL AUTO_INCREMENT,
                formation_id     INT          NOT NULL DEFAULT 0,
                etudiant_nom     VARCHAR(150) NOT NULL DEFAULT '',
                etudiant_email   VARCHAR(200) NOT NULL DEFAULT '',
                etudiant_phone   VARCHAR(40)  NOT NULL DEFAULT '',
                montant_total    INT          NOT NULL DEFAULT 0,
                plan_type        VARCHAR(20)  NOT NULL DEFAULT 'unique',
                acompte_minimum  INT          NOT NULL DEFAULT 0,
                statut           VARCHAR(20)  NOT NULL DEFAULT 'en_cours',
                notes            TEXT,
                created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_plan_formation (formation_id),
                INDEX idx_plan_email (etudiant_email(80))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        /* Colonnes additionnelles sur paiements_inscription */
        foreach ([
            "ALTER TABLE paiements_inscription ADD COLUMN plan_id INT NULL DEFAULT NULL",
            "ALTER TABLE paiements_inscription ADD COLUMN echeance_num TINYINT NOT NULL DEFAULT 1",
            "ALTER TABLE paiements_inscription ADD COLUMN echeance_label VARCHAR(120) NOT NULL DEFAULT ''",
            "ALTER TABLE paiements_inscription ADD COLUMN montant_net INT NOT NULL DEFAULT 0",
            "ALTER TABLE paiements_inscription ADD INDEX idx_pi_plan (plan_id)",
        ] as $sql) {
            try { $pdo->exec($sql); } catch (Throwable $e) { /* colonne déjà présente */ }
        }
    }
}

if (!function_exists('payment_plan_label')) {
    /** Texte humain pour un type de plan. */
    function payment_plan_label(string $type): string
    {
        return match ($type) {
            '2tranches' => '2 tranches (50 % / 50 %)',
            '3tranches' => '3 tranches (40 % / 30 % / 30 %)',
            default     => 'Paiement unique',
        };
    }
}

if (!function_exists('payment_plan_paid_amount')) {
    /** Montant total payé (statut=paye) pour un plan donné. */
    function payment_plan_paid_amount(PDO $pdo, int $planId): int
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant_net),0) FROM paiements_inscription WHERE plan_id=? AND statut='paye'");
        $stmt->execute([$planId]);
        return (int)$stmt->fetchColumn();
    }
}
