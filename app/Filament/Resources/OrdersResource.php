<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrdersResource\Concerns\HasOrderDetails;
use App\Filament\Resources\OrdersResource\Pages\EditOrder;
use App\Filament\Resources\OrdersResource\Pages\ListOrders;
use App\Filament\Resources\OrdersResource\Pages\ViewOrder;
use App\Filament\Resources\OrdersResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrdersResource\RelationManagers\StatusHistoryRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrdersResource extends Resource
{
    use HasOrderDetails;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withSum('items as items_sum_quantity', 'quantity');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('orders.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('orders.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('orders.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::orderInformationSection(),
                static::orderProcessingSection(),
                static::orderAddressSection(),
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
                    ->weight('semibold')
                    ->description(fn (Order $record): ?string => $record->isGuestOrder() ? __('admin.orders.guest') : __('admin.orders.registered')),
                TextColumn::make('customer')
                    ->label('Client')
                    ->getStateUsing(fn (Order $record): string => $record->customerFullName())
                    ->searchable(['customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone'])
                    ->description(fn (Order $record): ?string => optional($record->created_at)->format('d/m/Y H:i')),
                TextColumn::make('shipping_city')
                    ->label('Ville')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Order $record): string => format_price($record->totalAmount()))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total', $direction))
                    ->alignEnd(),
                TextColumn::make('items_sum_quantity')
                    ->label('Articles')
                    ->getStateUsing(fn (Order $record): int => (int) $record->items_sum_quantity)
                    ->alignCenter(),
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
                SelectFilter::make('customer_type')
                    ->label('Type de client')
                    ->options([
                        'registered' => __('admin.orders.registered'),
                        'guest' => __('admin.orders.guest'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'guest') {
                            return $query->whereNull('user_id');
                        }

                        if ($value === 'registered') {
                            return $query->whereNotNull('user_id');
                        }

                        return $query;
                    }),
                Filter::make('date')
                    ->label('Date')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('total')
                    ->label('Total')
                    ->columns(2)
                    ->schema([
                        TextInput::make('min')->label('Min')->numeric()->minValue(0),
                        TextInput::make('max')->label('Max')->numeric()->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['min']),
                                fn (Builder $q, $min): Builder => $q->where('total', '>=', (int) round(((float) $min) * 100)),
                            )
                            ->when(
                                filled($data['max']),
                                fn (Builder $q, $max): Builder => $q->where('total', '<=', (int) round(((float) $max) * 100)),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            StatusHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
