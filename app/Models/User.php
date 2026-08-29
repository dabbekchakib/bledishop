<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active', 'locale', 'first_name', 'last_name', 'phone', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Determine whether the user is allowed to access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return true;
        }

        return $this->is_active && $this->hasRole(Role::adminPanelRoles());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    public function hasAdminRole(): bool
    {
        return $this->hasRole(Role::adminPanelRoles());
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * The customer's first name, falling back to the legacy single name field.
     */
    public function firstName(): string
    {
        if (filled($this->first_name)) {
            return $this->first_name;
        }

        return (string) preg_split('/\s+/', trim((string) $this->name), 2)[0] ?? '';
    }

    /**
     * The customer's last name, falling back to the legacy single name field.
     */
    public function lastName(): string
    {
        if (filled($this->last_name)) {
            return $this->last_name;
        }

        $parts = preg_split('/\s+/', trim((string) $this->name), 2);

        return count($parts) > 1 ? (string) $parts[1] : '';
    }

    /**
     * Full display name (first + last), falling back to the legacy name field.
     */
    public function fullName(): string
    {
        if (filled($this->first_name) || filled($this->last_name)) {
            return trim($this->first_name.' '.$this->last_name);
        }

        return (string) $this->name;
    }
}
