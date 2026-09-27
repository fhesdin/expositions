<?php

namespace Core;

use Parsedown;

/**
 * Rendu de templates PHP avec layout.
 */
final class View
{
    private static string $templateDir = __DIR__ . '/../templates';
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $data = array_merge(self::$shared, $data);
        $data['base'] = '';
        $content = self::capture($template, $data);

        if ($layout === null) {
            echo self::rewriteLinks($content);
            return;
        }
        $data['content'] = $content;
        $output = self::captureFile(self::$templateDir . '/' . $layout . '.php', $data);
        echo self::rewriteLinks($output);
    }

    /**
     * Réécrit les liens pour le routage par query string :
     * - Les assets (css/, js/, lib/, uploads/, assets/) deviennent relatifs
     * - Les routes (/login, /events, ...) deviennent ?r=/path
     */
    private static function rewriteLinks(string $html): string
    {
        // 1. Assets : rendre relatifs (enlever le / initial)
        $html = preg_replace(
            '/(href|src)="\/(css\/|js\/|lib\/|uploads\/|assets\/)/',
            '$1="$2',
            $html
        );
        // 2. Routes : convertir /path → ?r=/path (en gérant les query strings)
        $html = preg_replace_callback(
            '/(href|action)="\/([^"]*)"/',
            function ($m) {
                $url = $m[2];
                $parts = explode('?', $url, 2);
                $path = $parts[0];
                $query = $parts[1] ?? '';
                if ($query !== '') {
                    return $m[1] . '="?r=/' . $path . '&' . $query . '"';
                }
                return $m[1] . '="?r=/' . $path . '"';
            },
            $html
        );
        return $html;
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        return self::captureFile(self::$templateDir . '/' . $template . '.php', $data);
    }

    private static function captureFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException("Template introuvable : $file");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** Rendu Markdown sécurisé (fallback si Parsedown absent). */
    public static function markdown(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        if (class_exists(\Parsedown::class)) {
            $parsedown = new \Parsedown();
            $parsedown->setSafeMode(true);
            return $parsedown->text($text);
        }
        // Fallback : échappement HTML + sauts de ligne
        return nl2br(e($text));
    }
}
