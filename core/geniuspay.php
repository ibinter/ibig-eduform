<?php
declare(strict_types=1);

/* =========================================================
   GENIUSPAY — IBIG EDUFORM
   Intégration paiement en ligne (opérateur ivoirien).
   Clés dans secrets.php :
     GENIUSPAY_SECRET_KEY      (Bearer, côté serveur — NE JAMAIS exposer)
     GENIUSPAY_WEBHOOK_SECRET  (vérification de signature)
   Doc : https://geniuspay.ci
   Frais : 1 % du montant + 100 FCFA par transaction
========================================================= */

if (!function_exists('geniuspay_secret')) {
    function geniuspay_secret(): string
    {
        return defined('GENIUSPAY_SECRET_KEY') ? (string)GENIUSPAY_SECRET_KEY : '';
    }
}

if (!function_exists('geniuspay_fee')) {
    /**
     * Frais de transaction GeniusPay pour un montant NET.
     * Formule : 1 % du montant + 100 FCFA, arrondi au FCFA supérieur.
     */
    function geniuspay_fee(int $net): int
    {
        if ($net <= 0) { return 0; }
        return (int)ceil($net * 0.01) + 100;
    }
}

if (!function_exists('geniuspay_gross')) {
    /**
     * Montant brut facturé au client : net + frais GeniusPay.
     * Arrondi au multiple de 5 FCFA supérieur pour le confort d'affichage.
     */
    function geniuspay_gross(int $net): int
    {
        if ($net <= 0) { return 0; }
        $gross = $net + geniuspay_fee($net);
        return (int)(ceil($gross / 5) * 5);
    }
}

if (!function_exists('geniuspay_request')) {
    /** Requête HTTP JSON vers l'API GeniusPay. Retourne [httpCode, arrayDecodé]. */
    function geniuspay_request(string $method, string $path, ?array $body = null): array
    {
        $base = defined('GENIUSPAY_API_URL') ? rtrim((string)GENIUSPAY_API_URL, '/') : 'https://api.geniuspay.ci/v1';
        $url  = $base . $path;

        $headers = [
            'Authorization: Bearer ' . geniuspay_secret(),
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('[GENIUSPAY] cURL: ' . $err);
            return [0, []];
        }
        $data = json_decode($raw, true);
        return [$code, is_array($data) ? $data : []];
    }
}

if (!function_exists('geniuspay_initialize')) {
    /**
     * Crée une transaction GeniusPay.
     * Retourne ['id'=>..., 'checkout_url'=>...] ou null.
     * Le montant passé est le montant BRUT (déjà grossé-up).
     */
    function geniuspay_initialize(
        int    $amount,
        string $description,
        array  $customer,
        string $returnUrl,
        array  $metadata = [],
        string $currency  = 'XOF'
    ): ?array {
        if (geniuspay_secret() === '') {
            error_log('[GENIUSPAY] clé secrète absente');
            return null;
        }

        $meta = [];
        foreach ($metadata as $k => $v) {
            $meta[(string)$k] = (string)$v;
        }

        $payload = [
            'amount'      => $amount,
            'currency'    => $currency,
            'description' => $description,
            'return_url'  => $returnUrl,
            'customer'    => [
                'email'      => (string)($customer['email']      ?? ''),
                'first_name' => (string)($customer['first_name'] ?? ''),
                'last_name'  => (string)($customer['last_name']  ?? ''),
                'phone'      => (string)($customer['phone']      ?? ''),
            ],
            'metadata'    => $meta,
        ];

        [$code, $data] = geniuspay_request('POST', '/payments/initialize', $payload);

        /* Accepter checkout_url à plusieurs niveaux possibles dans la réponse */
        $checkoutUrl = (string)(
            $data['data']['checkout_url'] ??
            $data['checkout_url'] ??
            $data['data']['payment_url'] ??
            $data['payment_url'] ??
            ''
        );
        $txId = (string)(
            $data['data']['id'] ??
            $data['data']['transaction_id'] ??
            $data['id'] ??
            ''
        );

        if ($code >= 200 && $code < 300 && $checkoutUrl !== '') {
            return ['id' => $txId, 'checkout_url' => $checkoutUrl];
        }
        error_log('[GENIUSPAY] init échec (' . $code . ') : ' . json_encode($data));
        return null;
    }
}

if (!function_exists('geniuspay_verify')) {
    /**
     * Vérifie le statut d'une transaction.
     * Retourne 'success' | 'pending' | 'failed' | 'cancelled' ou null.
     */
    function geniuspay_verify(string $transactionId): ?string
    {
        if ($transactionId === '' || geniuspay_secret() === '') {
            return null;
        }
        [$code, $data] = geniuspay_request('GET', '/payments/' . rawurlencode($transactionId) . '/verify');
        if ($code >= 200 && $code < 300) {
            $status = (string)(
                $data['data']['status'] ??
                $data['status'] ??
                ''
            );
            /* Normalisation : succès possible selon le prestataire */
            if (in_array(strtolower($status), ['success','successful','paid','completed'], true)) {
                return 'success';
            }
            if (in_array(strtolower($status), ['failed','failure','error'], true)) {
                return 'failed';
            }
            if (in_array(strtolower($status), ['cancelled','canceled','rejected'], true)) {
                return 'cancelled';
            }
            return 'pending';
        }
        error_log('[GENIUSPAY] verify échec (' . $code . ') id=' . $transactionId);
        return null;
    }
}

if (!function_exists('geniuspay_verify_webhook')) {
    /**
     * Vérifie la signature HMAC-SHA256 du webhook.
     * Header attendu : X-GeniusPay-Signature
     */
    function geniuspay_verify_webhook(string $rawBody, string $signature): bool
    {
        $secret = defined('GENIUSPAY_WEBHOOK_SECRET') ? (string)GENIUSPAY_WEBHOOK_SECRET : '';
        if ($secret === '' || $signature === '') {
            return false;
        }
        $signature = trim($signature);
        $hex = hash_hmac('sha256', $rawBody, $secret);
        $b64 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        /* Accepte hex brut OU préfixé "sha256=" OU base64 */
        return hash_equals($hex, $signature)
            || hash_equals('sha256=' . $hex, $signature)
            || hash_equals($b64, $signature);
    }
}
