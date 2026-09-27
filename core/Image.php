<?php

namespace Core;

/**
 * Gestion des uploads d'images : validation, déplacement, redimensionnement.
 */
final class Image
{
    private static array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Valide et sauvegarde un fichier uploadé. Retourne le nom de fichier (UUID.ext). */
    public static function upload(array $file, string $destDir, int $maxMb = 5, bool $makeThumb = false): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erreur d\'upload (code ' . $file['error'] . ').');
        }
        $maxBytes = $maxMb * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException("Fichier trop volumineux (max $maxMb Mo).");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::$allowedMimes[$mime])) {
            throw new \RuntimeException('Type de fichier non autorisé : ' . $mime);
        }
        $ext = self::$allowedMimes[$mime];

        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $filename = uuid() . '.' . $ext;
        $destPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Impossible de déplacer le fichier uploadé.');
        }

        if ($makeThumb) {
            self::makeThumbnail($destPath, $ext);
        }
        return $filename;
    }

    /** Génère une vignette 300px de large à côté de l'image. */
    public static function makeThumbnail(string $path, string $ext): ?string
    {
        $thumb = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '_thumb.' . $ext, $path);

        if (function_exists('imagecreatefromjpeg')) {
            $src = match ($ext) {
                'jpg' => @imagecreatefromjpeg($path),
                'png' => @imagecreatefrompng($path),
                'webp' => @imagecreatefromwebp($path),
                default => null,
            };
            if (!$src) {
                return null;
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $tw = 300;
            $th = (int) ($h * $tw / $w);
            $dst = imagecreatetruecolor($tw, $th);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
            match ($ext) {
                'jpg' => imagejpeg($dst, $thumb, 85),
                'png' => imagepng($dst, $thumb),
                'webp' => imagewebp($dst, $thumb, 85),
            };
            imagedestroy($src);
            imagedestroy($dst);
            return $thumb;
        }
        return null;
    }

    public static function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
        $thumb = preg_replace('/\.(jpg|png|webp)$/i', '_thumb.$1', $path);
        if (is_file($thumb)) {
            unlink($thumb);
        }
    }

    /** Télécharge une image depuis une URL et la sauvegarde localement. */
    public static function downloadFromUrl(string $url, string $destDir, int $maxMb = 5): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PokemonGOManager/1.0',
        ]);
        $body = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $httpCode !== 200) {
            throw new \RuntimeException('Téléchargement échoué (HTTP ' . $httpCode . ').');
        }

        $maxBytes = $maxMb * 1024 * 1024;
        if (strlen($body) > $maxBytes) {
            throw new \RuntimeException("Fichier trop volumineux (max $maxMb Mo).");
        }

        $ext = match (true) {
            str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            preg_match('/\.(jpg|jpeg|png|webp)$/i', $url, $m) => strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]),
            default => 'jpg',
        };

        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $filename = uuid() . '.' . $ext;
        $destPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($destPath, $body);

        self::makeThumbnail($destPath, $ext);
        return $filename;
    }
}
