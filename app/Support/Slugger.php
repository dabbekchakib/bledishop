<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Multilingual slug generator.
 *
 * French and English slags are ASCII-safe via Str::slug(). Arabic keeps its
 * script (Unicode letters), only the diacritics are stripped and whitespace
 * is collapsed into separators, so URLs stay readable and RTL-friendly.
 */
class Slugger
{
    public static function make(string $value, string $locale): string
    {
        $value = trim($value);

        if ($locale === 'ar') {
            return static::unicodeSlug($value);
        }

        return Str::slug($value);
    }

    /**
     * Ensure the slug is unique for the given (locale, table) couple by
     * appending a numeric suffix when a collision exists.
     */
    public static function unique(string $slug, string $locale, string $table, ?int $ignoreId = null): string
    {
        $slug = $slug === '' ? '-' : $slug;

        $base = $slug;
        $suffix = 2;

        while (static::slugExists($slug, $locale, $table, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function slugExists(string $slug, string $locale, string $table, ?int $ignoreId): bool
    {
        $query = DB::table($table)
            ->where('locale', $locale)
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private static function unicodeSlug(string $value): string
    {
        $value = (string) preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $value);
        $value = mb_strtolower($value);
        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $value);

        return trim($value, '-');
    }
}
