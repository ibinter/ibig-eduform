<?php
declare(strict_types=1);

/* =========================================================
   WHATSAPP — IBIG EDUFORM
   - whatsapp_notify_admin() : envoi AUTOMATIQUE côté serveur
     (via CallMeBot) au numéro de l'institut. Échoue en silence
     (ne casse jamais l'enregistrement).
   - whatsapp_wa_link()      : lien wa.me pré-rempli (clic prospect).
   Config :
     WHATSAPP_ADMIN_PHONE        (config.php)  ex: 2250778882592
     WHATSAPP_CALLMEBOT_APIKEY   (secrets.php) clé fournie par CallMeBot
========================================================= */

if (!function_exists('whatsapp_admin_phone')) {
    function whatsapp_admin_phone(): string
    {
        // Numéro éditable en base (settings), repli sur la constante config
        if (function_exists('setting')) {
            $s = preg_replace('/\D/', '', (string)setting('whatsapp_number', ''));
            if ($s !== '') { return $s; }
        }
        // Numéro international SANS le « + » ni espaces
        return defined('WHATSAPP_ADMIN_PHONE') ? preg_replace('/\D/', '', WHATSAPP_ADMIN_PHONE) : '';
    }
}

if (!function_exists('whatsapp_wa_link')) {
    /** Lien wa.me pré-rempli. $phone vide => numéro admin. */
    function whatsapp_wa_link(string $text, string $phone = ''): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?: whatsapp_admin_phone();
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    }
}

if (!function_exists('whatsapp_notify_admin')) {
    /**
     * Envoi automatique au WhatsApp de l'institut via CallMeBot.
     * @return bool true si l'appel a été tenté avec succès HTTP.
     */
    function whatsapp_notify_admin(string $text): bool
    {
        $apikey = defined('WHATSAPP_CALLMEBOT_APIKEY') ? (string)WHATSAPP_CALLMEBOT_APIKEY : '';
        $phone  = whatsapp_admin_phone();

        // Pas configuré -> on n'envoie rien (mais la demande reste en base)
        if ($apikey === '' || $phone === '') {
            return false;
        }

        $url = 'https://api.callmebot.com/whatsapp.php'
             . '?phone=' . rawurlencode($phone)
             . '&text='  . rawurlencode($text)
             . '&apikey=' . rawurlencode($apikey);

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 8,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                curl_exec($ch);
                $ok = (curl_errno($ch) === 0);
                curl_close($ch);
                return $ok;
            }

            // Fallback sans cURL
            $ctx = stream_context_create(['http' => ['timeout' => 8]]);
            return @file_get_contents($url, false, $ctx) !== false;

        } catch (Throwable $e) {
            error_log('[WHATSAPP] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('whatsapp_message_preinscription')) {
    /** Construit le message de demande (admin + prospect). */
    function whatsapp_message_preinscription(array $d): string
    {
        $l = [];
        $l[] = "📥 Nouvelle préinscription — IBIG EDUFORM";
        if (!empty($d['formation']))  $l[] = "Formation : " . $d['formation'];
        $nom = trim(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? ''));
        if ($nom !== '')              $l[] = "Candidat : " . $nom;
        if (!empty($d['telephone']))  $l[] = "Téléphone : " . $d['telephone'];
        if (!empty($d['email']))      $l[] = "Email : " . $d['email'];
        if (!empty($d['mode']))       $l[] = "Mode : " . $d['mode'];
        if (!empty($d['ville']))      $l[] = "Ville : " . $d['ville'];
        if (!empty($d['message']))    $l[] = "Message : " . $d['message'];
        if (!empty($d['preinscription_id'])) $l[] = "Réf. #" . $d['preinscription_id'];
        return implode("\n", $l);
    }
}
