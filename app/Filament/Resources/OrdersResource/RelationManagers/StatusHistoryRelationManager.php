<?php

namespace App\Filament\Resources\OrdersResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $recordTitleAttribute = 'new_status';

    protected function canCreate(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => (string) $record->new_status)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('old_status')
                    ->label('Statut précédent')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? OrderStatus::tryFrom($state)?->label() ?? $state
                        : '—'),
                TextColumn::make('new_status')
                    ->label('Nouveau statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => OrderStatus::tryFrom($state)?->badgeColor() ?? 'gray'),
                TextColumn::make('changer.name')
                    ->label('Par')
                    ->placeholder('—'),
                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
