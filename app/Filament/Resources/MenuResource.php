<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages\CreateMenu;
use App\Filament\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Resources\MenuResource\Pages\ListMenus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Services\LocalizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.menu');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.menu_plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('items');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Général')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du menu')
                            ->required()
                            ->maxLength(255),
                        Select::make('location')
                            ->label('Emplacement')
                            ->options([
                                'main' => 'Navigation principale',
                                'mobile' => 'Menu mobile',
                                'footer' => 'Pied de page',
                                'footer_secondary' => 'Pied de page — secondaire',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ]),
                Section::make('Éléments du menu')
                    ->description('Glissez-déposez pour hiérarchiser. Libellez chaque langue ou laissez vide pour utiliser le nom de l\'entité liée.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Éléments')
                            ->schema(static::itemSchema())
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => (string) ($state['label'][app(LocalizationService::class)->defaultLocale()] ?? $state['type'] ?? 'Élément'))
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('location')
                    ->label('Emplacement')
                    ->badge(),
                TextColumn::make('items_count')
                    ->label('Éléments')
                    ->counts('items'),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
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
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }

    /**
     * The schema of a menu item, including its nested children repeater.
     *
     * @return array<int, mixed>
     */
    public static function itemSchema(): array
    {
        $locales = app(LocalizationService::class);

        $labelFields = [];
        foreach ($locales->availableLocales() as $locale) {
            $labelFields[] = TextInput::make("label.{$locale}")
                ->label('Libellé '.strtoupper($locale))
                ->maxLength(255)
                ->columnSpan(1);
        }

        return [
            Hidden::make('id'),
            Select::make('type')
                ->label('Type')
                ->options([
                    'page' => 'Page',
                    'category' => 'Catégorie',
                    'product' => 'Produit',
                    'brand' => 'Marque',
                    'url' => 'Lien personnalisé',
                ])
                ->default('url')
                ->live()
                ->required(),
            ...$labelFields,
            Select::make('target_id')
                ->label('Cible')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['page', 'category', 'product', 'brand'], true))
                ->searchable()
                ->options(fn (Get $get): array => static::targetOptions($get('type') ?? 'page'))
                ->required(),
            TextInput::make('target_url')
                ->label('URL')
                ->placeholder('https://… ou /chemin-relatif')
                ->visible(fn (Get $get): bool => ($get('type') ?? 'url') === 'url')
                ->activeUrl()
                ->nullable(),
            Toggle::make('target_blank')
                ->label('Ouvrir dans un nouvel onglet'),
            Toggle::make('is_external')
                ->label('Lien externe'),
            TextInput::make('css_class')
                ->label('Classe CSS')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            Repeater::make('children')
                ->label('Sous-éléments')
                ->schema(static::childSchema())
                ->collapsible()
                ->reorderableWithButtons()
                ->default([]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function childSchema(): array
    {
        $locales = app(LocalizationService::class);

        $labelFields = [];
        foreach ($locales->availableLocales() as $locale) {
            $labelFields[] = TextInput::make("label.{$locale}")
                ->label('Libellé '.strtoupper($locale))
                ->maxLength(255)
                ->columnSpan(1);
        }

        return [
            Hidden::make('id'),
            Select::make('type')
                ->label('Type')
                ->options([
                    'page' => 'Page',
                    'category' => 'Catégorie',
                    'product' => 'Produit',
                    'brand' => 'Marque',
                    'url' => 'Lien personnalisé',
                ])
                ->default('url')
                ->live()
                ->required(),
            ...$labelFields,
            Select::make('target_id')
                ->label('Cible')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['page', 'category', 'product', 'brand'], true))
                ->searchable()
                ->options(fn (Get $get): array => static::targetOptions($get('type') ?? 'page'))
                ->required(),
            TextInput::make('target_url')
                ->label('URL')
                ->placeholder('https://… ou /chemin-relatif')
                ->visible(fn (Get $get): bool => ($get('type') ?? 'url') === 'url')
                ->activeUrl()
                ->nullable(),
            Toggle::make('target_blank')
                ->label('Nouvel onglet'),
            Toggle::make('is_external')
                ->label('Lien externe'),
            TextInput::make('css_class')
                ->label('Classe CSS')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ];
    }

    /**
     * Searchable target options for a given item type.
     *
     * @return array<int, string>
     */
    private static function targetOptions(string $type): array
    {
        $locale = app(LocalizationService::class)->defaultLocale();

        return match ($type) {
            'page' => Page::query()->with('translations')->get()
                ->mapWithKeys(fn (Page $page): array => [$page->id => $page->translatedTitle($locale)])
                ->all(),
            'category' => Category::query()->with('translations')->get()
                ->mapWithKeys(fn (Category $category): array => [$category->id => $category->translatedName($locale)])
                ->all(),
            'product' => Product::query()->with('translations')->get()
                ->mapWithKeys(fn (Product $product): array => [$product->id => $product->translatedName($locale)])
                ->all(),
            'brand' => Brand::query()->with('translations')->get()
                ->mapWithKeys(fn (Brand $brand): array => [$brand->id => $brand->translatedName($locale)])
                ->all(),
            default => [],
        };
    }
}
