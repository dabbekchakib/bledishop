<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'destination',
        'status_code',
        'is_active',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Normalise an incoming path into the stored "source" format so lookups
     * are consistent regardless of trailing slashes or the leading slash.
     */
    public static function normalizeSource(string $path): string
    {
        $path = ltrim(trim($path), '/');

        return rtrim($path, '/');
    }
}
