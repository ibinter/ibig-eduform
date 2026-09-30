<?php
declare(strict_types=1);

/* Upload d'image hero. Retourne [nomFichier, erreur|null]. */
if (!function_exists('hero_handle_upload')) {
    function hero_handle_upload(string $fallbackName): array
    {
        $dir = dirname(__DIR__, 2) . '/assets/images/hero';
        $fallback = basename($fallbackName ?: 'hero-1.jpg');

        $f = $_FILES['image_file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [$fallback, null]; // aucun upload → on garde l'image choisie
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return [$fallback, "Échec de l’upload (code {$f['error']})."];
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            return [$fallback, "Image trop lourde (max 5 Mo)."];
        }

        $info = @getimagesize($f['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!$info || !isset($allowed[$info['mime']])) {
            return [$fallback, "Format non autorisé (JPEG, PNG ou WebP)."];
        }

        $ext  = $allowed[$info['mime']];
        $name = 'hero-' . bin2hex(random_bytes(6)) . '.' . $ext;

        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        if (!@move_uploaded_file($f['tmp_name'], "$dir/$name")) {
            return [$fallback, "Impossible d’enregistrer l’image sur le serveur."];
        }
        return [$name, null];
    }
}
