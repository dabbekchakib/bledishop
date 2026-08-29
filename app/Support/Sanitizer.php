<?php

namespace App\Support;

use Illuminate\Support\Str;
use Mews\Purifier\Purifier;

/**
 * Thin wrapper around HTMLPurifier used before storing or rendering admin
 * authored rich content. Never trust raw HTML coming from the back-office:
 * everything passes through the sanitizer to strip script and event-handler
 * payloads (XSS protection).
 */
class Sanitizer
{
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return (string) app(Purifier::class)->clean($html);
    }

    /**
     * A plain-text excerpt derived from rich content, safe to inject into
     * meta descriptions or excerpt fields.
     */
    public static function excerptFromHtml(?string $html, int $limit = 160): string
    {
        if ($html === null) {
            return '';
        }

        $text = trim((string) app(Purifier::class)->clean($html, ['HTML.Allowed' => '']));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        return Str::limit($text, $limit);
    }
}
