<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'active',
        'source',
        'token',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * (Re)activate an existing subscriber, used when an unsubscribed email
     * re-subscribes through the form.
     */
    public function reactivate(?string $name = null): void
    {
        $this->forceFill([
            'active' => true,
            'unsubscribed_at' => null,
            'name' => $name ?: $this->name,
        ])->save();
    }
}
