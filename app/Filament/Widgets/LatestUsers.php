<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestUsers extends TableWidget
{
    protected static ?string $heading = 'Derniers utilisateurs';

    public static function canView(): bool
    {
        return auth()->user()?->can('users.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with('roles')->latest()->limit(5))
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rôle')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime(),
            ])
            ->paginated(false);
    }
}
