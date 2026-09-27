<?php

namespace Core;

/**
 * Pagination des listes.
 */
final class Pagination
{
    public int $page;
    public int $perPage;
    public int $total;
    public int $lastPage;

    public function __construct(int $total, int $perPage, ?int $page)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = max(1, min($page ?? 1, $this->lastPage));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function onFirstPage(): bool
    {
        return $this->page <= 1;
    }

    public function onLastPage(): bool
    {
        return $this->page >= $this->lastPage;
    }

    public function render(string $baseUrl): string
    {
        if (!$this->hasPages()) {
            return '';
        }
        // Convertir les URLs au format ?r=/path
        $url = self::toQueryRoute($baseUrl);
        $sep = str_contains($url, '?') ? '&' : '?';
        $html = '<nav class="pagination"><ul>';
        if (!$this->onFirstPage()) {
            $html .= '<li><a href="' . e($url . $sep . 'page=' . ($this->page - 1)) . '">« Précédent</a></li>';
        }
        for ($i = 1; $i <= $this->lastPage; $i++) {
            if ($i === $this->page) {
                $html .= '<li class="active"><span>' . $i . '</span></li>';
            } elseif (abs($i - $this->page) <= 2 || $i === 1 || $i === $this->lastPage) {
                $html .= '<li><a href="' . e($url . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
            } elseif (abs($i - $this->page) === 3) {
                $html .= '<li class="ellipsis"><span>…</span></li>';
            }
        }
        if (!$this->onLastPage()) {
            $html .= '<li><a href="' . e($url . $sep . 'page=' . ($this->page + 1)) . '">Suivant »</a></li>';
        }
        $html .= '</ul></nav>';
        return $html;
    }

    /** Convertit une URL /path → ?r=/path (ou conserve si déjà en ?r=). */
    private static function toQueryRoute(string $url): string
    {
        if (str_starts_with($url, '?r=')) {
            return $url;
        }
        if (str_starts_with($url, '/')) {
            $parts = explode('?', $url, 2);
            $r = '?r=' . $parts[0];
            if (isset($parts[1])) {
                $r .= '&' . $parts[1];
            }
            return $r;
        }
        return $url;
    }
}
