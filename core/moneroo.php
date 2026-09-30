<?php
declare(strict_types=1);

/* =========================================================
   MONEROO — IBIG EDUFORM
   Intégration paiement (agrégateur africain).
   Clés dans secrets.php :
     MONEROO_SECRET_KEY      (Bearer, côté serveur — NE JAMAIS exposer)
     MONEROO_WEBHOOK_SECRET  (vérification de signature du webhook)
   Doc : https://docs.moneroo.io
========================================================= */

if (!function_exists('moneroo_secret')) {
    function moneroo_secret(): string
    {
        return defined('MONEROO_SECRET_KEY') ? (string)MONEROO_SECRET_KEY : '';
    }
}

if (!function_exists('payment_gross_amount')) {
    /**
     * Montant à FACTURER au client pour que l'institut reçoive le NET.
     * gross = net / (1 − taux), arrondi au multiple de 5 supérieur.
     */
    function payment_gross_amount(int $net): int
    {
        $rate = defined('PAYMENT_FEE_RATE') ? (float)PAYMENT_FEE_RATE : 0.0;
        if ($rate <= 0 || $rate >= 1 || $net <= 0) {
            return $net;
        }
        $gross = $net / (1 - $rate);
        return (int)(ceil($gross / 5) * 5);
    }
}

if (!function_exists('moneroo_request')) {
    /** Requête HTTP JSON vers l'API Moneroo. Retourne [httpCode, arrayDecodé]. */
    function moneroo_request(string $method, string $path, array $body = null): array
    {
        $url = 'https://api.moneroo.io' . $path;
        $headers = [
            'Authorization: Bearer ' . moneroo_secret(),
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
            error_log('[MONEROO] cURL: ' . $err);
            return [0, []];
        }
        $data = json_decode($raw, true);
        return [$code, is_array($data) ? $data : []];
    }
}

if (!function_exists('moneroo_initialize')) {
    /**
     * Crée une transaction et renvoie ['id'=>..., 'checkout_url'=>...] ou null.
     * Montant en FCFA entier (XOF, 0 décimale).
     */
    function moneroo_initialize(
        int $amount,
        string $description,
        array $customer,
        string $returnUrl,
        array $metadata = [],
        string $currency = 'XOF'
    ): ?array {
        if (moneroo_secret() === '') {
            error_log('[MONEROO] clé secrète absente');
            return null;
        }

        // metadata : valeurs en chaînes uniquement
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
                'email'      => (string)($customer['email'] ?? ''),
                'first_name' => (string)($customer['first_name'] ?? ''),
                'last_name'  => (string)($customer['last_name'] ?? ''),
                'phone'      => (string)($customer['phone'] ?? ''),
            ],
            'metadata'    => $meta,
        ];

        [$code, $data] = moneroo_request('POST', '/v1/payments/initialize', $payload);

        if ($code >= 200 && $code < 300 && !empty($data['data']['checkout_url'])) {
            return [
                'id'           => (string)($data['data']['id'] ?? ''),
                'checkout_url' => (string)$data['data']['checkout_url'],
            ];
        }
        error_log('[MONEROO] init échec (' . $code . ') : ' . json_encode($data));
        return null;
    }
}

if (!function_exists('moneroo_verify')) {
    /** Vérifie une transaction. Retourne le statut ('success','failed','pending'...) ou null. */
    function moneroo_verify(string $paymentId): ?string
    {
        if ($paymentId === '' || moneroo_secret() === '') {
            return null;
        }
        [$code, $data] = moneroo_request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/verify');
        if ($code >= 200 && $code < 300) {
            return (string)($data['data']['status'] ?? $data['status'] ?? '') ?: null;
        }
        return null;
    }
}

if (!function_exists('moneroo_verify_webhook')) {
    /** Vérifie la signature HMAC-SHA256 du webhook (header X-Moneroo-Signature). */
    function moneroo_verify_webhook(string $rawBody, string $signature): bool
    {
        $secret = defined('MONEROO_WEBHOOK_SECRET') ? (string)MONEROO_WEBHOOK_SECRET : '';
        if ($secret === '' || $signature === '') {
            return false;
        }
        $signature = trim($signature);
        /* On accepte hex OU base64 selon l'encodage du fournisseur */
        $hex = hash_hmac('sha256', $rawBody, $secret);
        $b64 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($hex, $signature) || hash_equals($b64, $signature);
    }
}
