<?php

namespace App\Support;

use Illuminate\Routing\UrlGenerator;

/**
 * URL-Generator, der allen Seiten-URLs ein „/" anhängt (wie im alten Projekt).
 *
 * Laravels Standard-format() macht am Ende ein trim($root.$path, '/') und
 * entfernt damit jeden Trailing Slash – formatPathUsing() greift deshalb nicht.
 * Wir überschreiben format() und hängen den Slash nach dem trim wieder an –
 * außer bei Wurzel, Assets/Dateien (mit Endung) und Admin/Livewire.
 */
class TrailingSlashUrlGenerator extends UrlGenerator
{
    public function format($root, $path, $route = null)
    {
        $url = parent::format($root, $path, $route);

        // Nur den Pfad-Teil betrachten (ohne ?query und #fragment).
        $pfad = parse_url($url, PHP_URL_PATH) ?? '';
        $trim = ltrim($pfad, '/');

        if ($trim === '') {
            return $url;                                  // Wurzel
        }
        if (str_starts_with($trim, 'admin') || str_starts_with($trim, 'livewire')) {
            return $url;                                  // Admin/Livewire unangetastet
        }
        if (str_contains(basename($trim), '.')) {
            return $url;                                  // Assets/Dateien (.css, .xml, .pdf …)
        }
        if (str_ends_with($pfad, '/')) {
            return $url;                                  // schon vorhanden
        }

        // „/" am Ende des Pfads einfügen – vor einem evtl. ?query / #fragment.
        return preg_replace('~^([^?#]*)~', '$1/', $url);
    }
}
