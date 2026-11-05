<?php

namespace App\Modules\Cms\Support;

/**
 * Keeps only simple formatting from CMS page bodies. Scripts, event handlers and
 * javascript: links are removed, so a compromised editor account cannot inject active content.
 */
class Sanitizer
{
    private const ALLOWED = '<p><br><strong><em><b><i><u><ul><ol><li><h2><h3><h4><a><blockquote>';

    public static function html(?string $html): string
    {
        // Remove script and style blocks together with their contents, not just the tags.
        $clean = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1\s*>#is', '', (string) $html);
        $clean = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*/?>#i', '', $clean);
        $clean = strip_tags($clean, self::ALLOWED);
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*"\s*javascript:[^"]*"/i', 'href="#"', $clean);
        $clean = preg_replace("/href\s*=\s*'\s*javascript:[^']*'/i", "href='#'", $clean);

        return $clean;
    }
}
