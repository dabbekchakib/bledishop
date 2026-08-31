<?php

namespace App\Filament\Resources;

use App\Enums\BannerPosition;
use App\Filament\Resources\BannerResource\Pages\CreateBanner;
use App\Filament\Resources\BannerResource\Pages\EditBanner;
use App\Filament\Resources\BannerResource\Pages\ListBanners;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.marketing.names.banner');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.marketing.names.banner_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label(__('admin.marketing.banner.title'))
                            ->maxLength(255),
                        Select::make('position')
                            ->label(__('admin.marketing.banner.position'))
                            ->options(collect(BannerPosition::cases())->mapWithKeys(fn (BannerPosition $p): array => [$p->value => $p->label()])->all())
                            ->default(BannerPosition::Homepage->value)
                            ->required(),
                    ]),
                    FileUpload::make('image')
                        ->label(__('admin.marketing.banner.image'))
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('marketing/banners')
                        ->maxSize(4096)
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextInput::make('button_label')
                            ->label(__('admin.marketing.banner.button_label'))
                            ->maxLength(100),
                        TextInput::make('link')
                            ->label(__('admin.marketing.banner.link'))
                            ->maxLength(255)
                            ->placeholder('/fr/products'),
                    ]),
                    Textarea::make('description')
                        ->label(__('admin.marketing.banner.description'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
                Section::make(__('admin.marketing.coupon.schedule_section'))->schema([
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('admin.marketing.coupon.starts_at')),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin.marketing.coupon.ends_at')),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('sort_order')
                            ->label(__('admin.marketing.banner.sort_order'))
                            ->numeric()
                            ->default(0),
                        Toggle::make('active')
                            ->label(__('admin.marketing.coupon.active'))
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label(__('admin.marketing.banner.image'))
                    ->disk('public'),
                TextColumn::make('title')
                    ->label(__('admin.marketing.banner.title'))
                    ->searchable(),
                TextColumn::make('position')
                    ->label(__('admin.marketing.banner.position'))
                    ->badge()
                    ->formatStateUsing(fn (?BannerPosition $state): string => $state?->label() ?? ''),
                TextColumn::make('sort_order')
                    ->label(__('admin.marketing.banner.sort_order'))
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
                SelectFilter::make('position')
                    ->label(__('admin.marketing.banner.position'))
                    ->options(collect(BannerPosition::cases())->mapWithKeys(fn (BannerPosition $p): array => [$p->value => $p->label()])->all()),
                TernaryFilter::make('active')
                    ->label(__('admin.marketing.coupon.active')),
            ])
            ->defaultSort('sort_order')
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
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
