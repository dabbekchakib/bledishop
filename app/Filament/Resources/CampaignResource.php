<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use App\Filament\Resources\CampaignResource\Pages\EditCampaign;
use App\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Coupon;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.marketing.names.campaign');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.marketing.names.campaign_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('admin.marketing.campaign.name'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label(__('admin.marketing.coupon.active'))
                            ->default(true),
                    ]),
                    Textarea::make('description')
                        ->label(__('admin.marketing.campaign.description'))
                        ->rows(2),
                ]),
                Section::make(__('admin.marketing.campaign.assets_section'))->schema([
                    static::assetSelect('promotion_ids', __('admin.marketing.campaign.promotions'), Promotion::class),
                    Grid::make(2)->schema([
                        static::assetSelect('coupon_ids', __('admin.marketing.campaign.coupons'), Coupon::class),
                        static::assetSelect('banner_ids', __('admin.marketing.campaign.banners'), Banner::class, 'title'),
                    ]),
                ]),
                Section::make(__('admin.marketing.coupon.schedule_section'))->schema([
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('admin.marketing.coupon.starts_at')),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin.marketing.coupon.ends_at')),
                    ]),
                ]),
            ]);
    }

    /**
     * @param  class-string  $model
     */
    private static function assetSelect(string $name, string $label, string $model, string $labelField = 'name'): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->options(fn (): array => $model::query()->limit(200)->pluck($labelField, 'id')->all())
            ->getSearchResultsUsing(fn (string $search): array => $model::query()
                ->where($labelField, 'like', '%'.$search.'%')
                ->limit(50)
                ->pluck($labelField, 'id')
                ->all())
            ->getOptionLabelUsing(fn ($value): ?string => optional($model::query()->find($value))?->{$labelField});
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.marketing.campaign.name'))
                    ->searchable(),
                IconColumn::make('active')
                    ->label(__('admin.marketing.coupon.active'))
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label(__('admin.marketing.coupon.starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('admin.marketing.coupon.ends_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
