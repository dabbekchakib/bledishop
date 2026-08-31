<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Enums\PromotionStatus;
use App\Filament\Resources\Concerns\UsesCatalogSelects;
use App\Filament\Resources\PromotionResource\Pages\CreatePromotion;
use App\Filament\Resources\PromotionResource\Pages\EditPromotion;
use App\Filament\Resources\PromotionResource\Pages\ListPromotions;
use App\Models\Promotion;
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

class PromotionResource extends Resource
{
    use UsesCatalogSelects;

    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.marketing.names.promotion');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.marketing.names.promotion_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('admin.marketing.promotion.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('countdown_title')
                            ->label(__('admin.marketing.promotion.countdown_title'))
                            ->maxLength(255),
                    ]),
                    Textarea::make('description')
                        ->label(__('admin.marketing.promotion.description'))
                        ->rows(2),
                ]),
                Section::make(__('admin.marketing.coupon.discount_section'))->schema([
                    Grid::make(3)->schema([
                        Select::make('type')
                            ->label(__('admin.marketing.promotion.type'))
                            ->options(collect(DiscountType::cases())
                                ->reject(fn (DiscountType $t): bool => $t === DiscountType::FreeShipping)
                                ->mapWithKeys(fn (DiscountType $t): array => [$t->value => $t->label()])
                                ->all())
                            ->default(DiscountType::Percentage->value)
                            ->required(),
                        TextInput::make('value')
                            ->label(__('admin.marketing.promotion.value'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->helperText(__('admin.marketing.promotion.value_help')),
                        Toggle::make('is_flash_sale')
                            ->label(__('admin.marketing.promotion.flash_sale')),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('is_countdown')
                            ->label(__('admin.marketing.promotion.countdown')),
                        TextInput::make('promo_quantity')
                            ->label(__('admin.marketing.promotion.promo_quantity'))
                            ->numeric()
                            ->minValue(1),
                    ]),
                ]),
                Section::make(__('admin.marketing.promotion.scope_section'))->schema([
                    self::productSelect('product_ids', __('admin.marketing.coupon.products')),
                    Grid::make(2)->schema([
                        self::categorySelect('category_ids', __('admin.marketing.coupon.categories')),
                        self::brandSelect('brand_ids', __('admin.marketing.coupon.brands')),
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
                TextColumn::make('name')
                    ->label(__('admin.marketing.promotion.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.marketing.promotion.type'))
                    ->badge()
                    ->formatStateUsing(fn (?DiscountType $state): string => $state?->label() ?? ''),
                TextColumn::make('value')
                    ->label(__('admin.marketing.promotion.value'))
                    ->formatStateUsing(fn (Promotion $record): string => $record->type === DiscountType::Percentage
                        ? number_format((float) $record->value, 0).'%'
                        : (string) (float) $record->value),
                IconColumn::make('is_flash_sale')
                    ->label(__('admin.marketing.promotion.flash_sale'))
                    ->boolean(),
                TextColumn::make('status')
                    ->label(__('admin.marketing.promotion.status'))
                    ->badge()
                    ->getStateUsing(fn (Promotion $record): PromotionStatus => $record->status())
                    ->formatStateUsing(fn (PromotionStatus $state): string => $state->label())
                    ->color(fn (PromotionStatus $state): string => $state->color()),
                TextColumn::make('ends_at')
                    ->label(__('admin.marketing.coupon.ends_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.marketing.promotion.type'))
                    ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $t): array => [$t->value => $t->label()])->all()),
                TernaryFilter::make('is_flash_sale')
                    ->label(__('admin.marketing.promotion.flash_sale')),
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
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }
}
