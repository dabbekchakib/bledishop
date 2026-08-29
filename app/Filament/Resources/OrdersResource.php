<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrdersResource\Pages\EditOrder;
use App\Filament\Resources\OrdersResource\Pages\ListOrders;
use App\Filament\Resources\OrdersResource\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['items', 'user']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Commande')
                    ->columns(2)
                    ->schema([
                        TextInput::make('order_number')
                            ->label('N° de commande')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Statut')
                            ->disabled()
                            ->formatStateUsing(fn (?string $state): string => $state ? OrderStatus::tryFrom($state)?->label() ?? $state : ''),
                        TextInput::make('customer_full_name')
                            ->label('Client')
                            ->disabled()
                            ->formatStateUsing(fn (Order $record): string => $record->customerFullName()),
                        TextInput::make('customer_email')
                            ->label('Email client')
                            ->disabled(),
                        TextInput::make('customer_phone')
                            ->label('Téléphone client')
                            ->disabled(),
                        TextInput::make('total')
                            ->label('Total')
                            ->disabled()
                            ->formatStateUsing(fn (Order $record): string => format_price($record->totalAmount())),
                        TextInput::make('currency')
                            ->label('Devise')
                            ->disabled(),
                        Textarea::make('shipping_address')
                            ->label('Adresse de livraison')
                            ->disabled()
                            ->rows(3),
                        TextInput::make('created_at')
                            ->label('Date')
                            ->disabled()
                            ->formatStateUsing(fn (Order $record): string => optional($record->created_at)->format('d/m/Y H:i') ?? ''),
                    ]),
                Section::make('Traitement')
                    ->description('Modifiez le statut de la commande et son statut de paiement')
                    ->schema([
                        Select::make('status')
                            ->label('Statut')
                            ->options(OrderStatus::options())
                            ->required(),
                        Select::make('payment_status')
                            ->label('Statut de paiement')
                            ->options(PaymentStatus::options())
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('N° commande')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('customer')
                    ->label('Client')
                    ->getStateUsing(fn (Order $record): string => $record->customerFullName())
                    ->searchable(['customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone'])
                    ->description(fn (Order $record): ?string => $record->customer_email),
                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Order $record): string => format_price($record->totalAmount()))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total', $direction))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->badgeColor()),
                TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->badgeColor()),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(OrderStatus::options()),
                SelectFilter::make('payment_status')
                    ->label('Paiement')
                    ->options(PaymentStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
