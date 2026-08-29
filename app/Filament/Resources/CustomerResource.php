<?php

namespace App\Filament\Resources;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Enums\Role as UserRole;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Commandes';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'client';

    protected static ?string $pluralModelLabel = 'clients';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role(UserRole::Customer->value)
            ->withCount('orders')
            ->withSum(
                ['orders' => fn (Builder $query): Builder => $query->where('status', '!=', OrderStatus::Cancelled)],
                'total',
            );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations client')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom complet')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->disabled(),
                        Select::make('locale')
                            ->label('Langue préférée')
                            ->options(Locale::options())
                            ->disabled(),
                        Toggle::make('is_active')
                            ->label('Compte actif')
                            ->disabled(),
                    ]),
                Section::make('Activité')
                    ->columns(2)
                    ->schema([
                        TextInput::make('orders_count')
                            ->label('Nombre de commandes')
                            ->disabled(),
                        TextInput::make('orders_sum_total')
                            ->label('Total dépensé')
                            ->disabled()
                            ->formatStateUsing(fn ($state): string => format_price(((int) $state) / 100)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Commandes')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('orders_sum_total')
                    ->label('Total dépensé')
                    ->getStateUsing(fn (User $record): string => format_price(((int) $record->orders_sum_total) / 100))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('orders_sum_total', $direction))
                    ->alignEnd(),
                TextColumn::make('locale')
                    ->label('Langue')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Membre depuis')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
