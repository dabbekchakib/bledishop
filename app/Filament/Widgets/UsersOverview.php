<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Aperçu des utilisateurs';

    public static function canView(): bool
    {
        return auth()->user()?->can('users.view') ?? false;
    }

    protected function getStats(): array
    {
        $superAdmins = User::role(Role::SuperAdmin->value)->count();
        $admins = User::role([Role::SuperAdmin->value, Role::Admin->value])->count();
        $customers = User::role(Role::Customer->value)->count();

        return [
            Stat::make('Utilisateurs', User::count())
                ->icon(Heroicon::OutlinedUsers)
                ->description('Tous les comptes')
                ->color('primary'),

            Stat::make('Administrateurs', $admins)
                ->icon(Heroicon::OutlinedShieldCheck)
                ->description("dont {$superAdmins} super-admin")
                ->color('warning'),

            Stat::make('Clients', $customers)
                ->icon(Heroicon::OutlinedUserGroup)
                ->description('Comptes avec le rôle client')
                ->color('info'),

            Stat::make('Comptes actifs', User::where('is_active', true)->count())
                ->icon(Heroicon::OutlinedCheckCircle)
                ->description('Utilisateurs actifs') // @phpstan-ignore-line
                ->color('success'),
        ];
    }
}
