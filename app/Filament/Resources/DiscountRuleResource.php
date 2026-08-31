<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Filament\Resources\Concerns\UsesCatalogSelects;
use App\Filament\Resources\DiscountRuleResource\Pages\CreateDiscountRule;
use App\Filament\Resources\DiscountRuleResource\Pages\EditDiscountRule;
use App\Filament\Resources\DiscountRuleResource\Pages\ListDiscountRules;
use App\Models\DiscountRule;
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

class DiscountRuleResource extends Resource
{
    use UsesCatalogSelects;

    protected static ?string $model = DiscountRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.marketing.names.discount_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.marketing.names.discount_rule_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('admin.marketing.discount_rule.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('priority')
                            ->label(__('admin.marketing.discount_rule.priority'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('admin.marketing.discount_rule.priority_help')),
                    ]),
                    Textarea::make('description')
                        ->label(__('admin.marketing.discount_rule.description'))
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
                Section::make(__('admin.marketing.discount_rule.conditions_section'))->schema([
                    Grid::make(2)->schema([
                        TextInput::make('min_subtotal')
                            ->label(__('admin.marketing.discount_rule.min_subtotal'))
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('min_items')
                            ->label(__('admin.marketing.discount_rule.min_items'))
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('min_quantity')
                            ->label(__('admin.marketing.discount_rule.min_quantity'))
                            ->numeric()
                            ->minValue(1),
                    ]),
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
                    ->label(__('admin.marketing.discount_rule.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.marketing.coupon.type'))
                    ->badge()
                    ->formatStateUsing(fn (?DiscountType $state): string => $state?->label() ?? ''),
                TextColumn::make('value')
                    ->label(__('admin.marketing.coupon.value'))
                    ->formatStateUsing(fn (DiscountRule $record): string => $record->type === DiscountType::Percentage
                        ? number_format((float) $record->value, 0).'%'
                        : (string) (float) $record->value),
                TextColumn::make('priority')
                    ->label(__('admin.marketing.discount_rule.priority'))
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
            ->defaultSort('priority', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => ListDiscountRules::route('/'),
            'create' => CreateDiscountRule::route('/create'),
            'edit' => EditDiscountRule::route('/{record}/edit'),
        ];
    }
}
