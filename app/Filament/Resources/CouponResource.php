<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Filament\Resources\Concerns\UsesCatalogSelects;
use App\Filament\Resources\CouponResource\Pages\CreateCoupon;
use App\Filament\Resources\CouponResource\Pages\EditCoupon;
use App\Filament\Resources\CouponResource\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CouponResource extends Resource
{
    use UsesCatalogSelects;

    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.marketing.names.coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.marketing.names.coupon_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('code')
                            ->label(__('admin.marketing.coupon.code'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                        TextInput::make('name')
                            ->label(__('admin.marketing.coupon.name'))
                            ->maxLength(255),
                    ]),
                    Textarea::make('description')
                        ->label(__('admin.marketing.coupon.description'))
                        ->rows(2),
                ]),
                Section::make(__('admin.marketing.coupon.discount_section'))->schema([
                    Grid::make(3)->schema([
                        Select::make('type')
                            ->label(__('admin.marketing.coupon.type'))
                            ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $t): array => [$t->value => $t->label()])->all())
                            ->default(DiscountType::Percentage->value)
                            ->required(),
                        TextInput::make('value')
                            ->label(__('admin.marketing.coupon.value'))
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Toggle::make('cumulative')
                            ->label(__('admin.marketing.coupon.cumulative')),
                    ]),
                ]),
                Section::make(__('admin.marketing.coupon.restrictions_section'))->schema([
                    Grid::make(2)->schema([
                        TextInput::make('min_subtotal')
                            ->label(__('admin.marketing.coupon.min_subtotal'))
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('max_subtotal')
                            ->label(__('admin.marketing.coupon.max_subtotal'))
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('usage_limit')
                            ->label(__('admin.marketing.coupon.usage_limit'))
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('per_customer_limit')
                            ->label(__('admin.marketing.coupon.per_customer'))
                            ->numeric()
                            ->minValue(1),
                    ]),
                    self::productSelect('product_ids', __('admin.marketing.coupon.products')),
                    Grid::make(2)->schema([
                        self::categorySelect('category_ids', __('admin.marketing.coupon.categories')),
                        self::brandSelect('brand_ids', __('admin.marketing.coupon.brands')),
                    ]),
                    Grid::make(2)->schema([
                        self::productSelect('excluded_product_ids', __('admin.marketing.coupon.excluded_products')),
                        self::categorySelect('excluded_category_ids', __('admin.marketing.coupon.excluded_categories')),
                    ]),
                ]),
                Section::make(__('admin.marketing.coupon.schedule_section'))->schema([
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('admin.marketing.coupon.starts_at')),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin.marketing.coupon.ends_at')),
                    ]),
                    Toggle::make('active')
                        ->label(__('admin.marketing.coupon.active'))
                        ->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.marketing.coupon.code'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin.marketing.coupon.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.marketing.coupon.type'))
                    ->badge()
                    ->formatStateUsing(fn (?DiscountType $state): string => $state?->label() ?? ''),
                TextColumn::make('value')
                    ->label(__('admin.marketing.coupon.value'))
                    ->formatStateUsing(fn (Coupon $record): string => $record->type === DiscountType::Percentage
                        ? number_format((float) $record->value, 0).'%'
                        : (string) (float) $record->value),
                TextColumn::make('usage_count')
                    ->label(__('admin.marketing.coupon.usage_count'))
                    ->sortable(),
                IconColumn::make('active')
                    ->label(__('admin.marketing.coupon.active'))
                    ->boolean(),
                TextColumn::make('ends_at')
                    ->label(__('admin.marketing.coupon.ends_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.marketing.coupon.type'))
                    ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $t): array => [$t->value => $t->label()])->all()),
                TernaryFilter::make('active')
                    ->label(__('admin.marketing.coupon.active')),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
