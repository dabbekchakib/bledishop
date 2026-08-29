<?php

namespace App\Filament\Resources;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Enums\Role as UserRole;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
            )->withMax('orders', 'created_at');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('customers.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('customers.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('customers.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('customers.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('customers.delete') ?? false;
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
                TextColumn::make('full_name')
                    ->label(__('admin.customers.column_name'))
                    ->getStateUsing(fn (User $record): string => $record->fullName())
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw(
                            "COALESCE(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) {$direction}"
                        );
                    })
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->label(__('admin.customers.column_email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('admin.customers.column_phone'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('is_active')
                    ->label(__('admin.customers.column_status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('admin.customers.active')
                        : __('admin.customers.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('orders_count')
                    ->label(__('admin.customers.column_orders'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('orders_sum_total')
                    ->label(__('admin.customers.column_total'))
                    ->getStateUsing(fn (User $record): string => format_price(((int) $record->orders_sum_total) / 100))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('orders_sum_total', $direction))
                    ->alignEnd(),
                TextColumn::make('orders_max_created_at')
                    ->label(__('admin.customers.column_last_order'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('locale')
                    ->label(__('admin.customers.column_locale'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.customers.column_joined'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->last_login_at
                        ? __('admin.customers.last_login').' : '.$record->last_login_at->format('d/m/Y H:i')
                        : null),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label(__('admin.customers.activation_toggle'))
                    ->trueLabel(__('admin.customers.filter_active'))
                    ->falseLabel(__('admin.customers.filter_inactive'))
                    ->queries(
                        fn (Builder $query): Builder => $query->where('is_active', true),
                        fn (Builder $query): Builder => $query->where('is_active', false),
                    ),
                TernaryFilter::make('has_orders')
                    ->label('Commandes')
                    ->trueLabel(__('admin.customers.filter_with_orders'))
                    ->falseLabel(__('admin.customers.filter_without_orders'))
                    ->queries(
                        fn (Builder $query): Builder => $query->has('orders'),
                        fn (Builder $query): Builder => $query->doesntHave('orders'),
                    ),
                Filter::make('created_at')
                    ->label(__('admin.customers.filter_registration_date'))
                    ->columns(2)
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('users.created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('users.created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('customers.update') ?? false),
            ])
            ->toolbarActions([
                Action::make('export')
                    ->label(__('admin.customers.export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color(Color::Gray)
                    ->visible(fn (): bool => auth()->user()?->can('customers.export') ?? false)
                    ->url(fn (): string => url('/admin/customer-exports'.self::exportQueryString()))
                    ->openUrlInNewTab(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Preserve the current table filters/query on the CSV export URL.
     */
    protected static function exportQueryString(): string
    {
        $active = request()->query('tableFilters.active.value');
        $hasOrders = request()->query('tableFilters.has_orders.value');
        $from = request()->query('tableFilters.created_at.from');
        $until = request()->query('tableFilters.created_at.until');

        $params = [];

        if (in_array($active, ['true', 'false'], true)) {
            $params['active'] = $active;
        }

        if (in_array($hasOrders, ['true', 'false'], true)) {
            $params['with_orders'] = $hasOrders;
        }

        if ($from) {
            $params['from'] = (string) $from;
        }

        if ($until) {
            $params['until'] = (string) $until;
        }

        $query = http_build_query($params);

        return $query ? '?'.$query : '';
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
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
