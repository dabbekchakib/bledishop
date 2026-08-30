<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\LocalizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.catalogue');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.product_plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'translations',
            'brand.translations',
            'categories.translations',
        ])->withSum(['variants as variants_stock_quantity_sum' => fn ($query) => $query->withTrashed()], 'stock_quantity');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(LocalizationService::class);

        return $schema
            ->schema([
                Tabs::make('product_tabs')
                    ->columnSpanFull()
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        Tab::make('Informations')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                Section::make('Détails du produit')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('type')
                                            ->label('Type de produit')
                                            ->options(ProductType::options())
                                            ->default(ProductType::Simple->value)
                                            ->live()
                                            ->required(),
                                        Select::make('status')
                                            ->label('Statut')
                                            ->options(ProductStatus::options())
                                            ->default(ProductStatus::Draft->value)
                                            ->required(),
                                        Select::make('brand_id')
                                            ->label('Marque')
                                            ->options(fn (): array => static::brandOptions())
                                            ->searchable()
                                            ->nullable(),
                                        TextInput::make('sku')
                                            ->label('Référence SKU')
                                            ->maxLength(100)
                                            ->helperText('Unique pour les produits simples.')
                                            ->visible(fn (Get $get): bool => $get('type') !== ProductType::Variable->value),
                                        Toggle::make('featured')
                                            ->label('Produit vedette')
                                            ->default(false),
                                        DateTimePicker::make('published_at')
                                            ->label('Date de publication')
                                            ->default(now()),
                                    ]),
                            ]),
                        Tab::make('Prix')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->schema([
                                Section::make('Tarification')
                                    ->description('Montants en HT, calculs effectués côté serveur')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('price')
                                            ->label('Prix de vente')
                                            ->numeric()
                                            ->prefix(setting('shop.currency_symbol', ''))
                                            ->minValue(0),
                                        TextInput::make('compare_at_price')
                                            ->label('Prix barré')
                                            ->numeric()
                                            ->prefix(setting('shop.currency_symbol', ''))
                                            ->minValue(0)
                                            ->helperText('Ancien prix avant remise.'),
                                        TextInput::make('cost_price')
                                            ->label('Prix de revient')
                                            ->numeric()
                                            ->prefix(setting('shop.currency_symbol', ''))
                                            ->minValue(0),
                                    ]),
                            ]),
                        Tab::make('Stock')
                            ->icon(Heroicon::OutlinedArchiveBox)
                            ->visible(fn (Get $get): bool => $get('type') !== ProductType::Variable->value)
                            ->schema([
                                Section::make('Gestion du stock')
                                    ->description('Gestion du stock pour un produit simple')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('manage_stock')
                                            ->label('Gérer le stock')
                                            ->default(true)
                                            ->live(),
                                        Select::make('stock_status')
                                            ->label('Statut du stock')
                                            ->options(StockStatus::options())
                                            ->visible(fn (Get $get): bool => ! (bool) $get('manage_stock')),
                                        TextInput::make('stock_quantity')
                                            ->label('Quantité en stock')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->visible(fn (Get $get): bool => (bool) $get('manage_stock')),
                                        TextInput::make('low_stock_threshold')
                                            ->label('Seuil d\'alerte stock faible')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->visible(fn (Get $get): bool => (bool) $get('manage_stock')),
                                    ]),
                            ]),
                        Tab::make('Dimensions')
                            ->icon(Heroicon::OutlinedScale)
                            ->schema([
                                Section::make('Poids et dimensions')
                                    ->description('Facultatif — utilisé pour le calcul de livraison')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('weight')->label('Poids (kg)')->numeric()->minValue(0),
                                        TextInput::make('length')->label('Longueur (cm)')->numeric()->minValue(0),
                                        TextInput::make('width')->label('Largeur (cm)')->numeric()->minValue(0),
                                        TextInput::make('height')->label('Hauteur (cm)')->numeric()->minValue(0),
                                    ]),
                            ]),
                        Tab::make('Catégories')
                            ->icon(Heroicon::OutlinedFolder)
                            ->schema([
                                Section::make('Associations')
                                    ->description('Associer le produit à une ou plusieurs catégories')
                                    ->schema([
                                        Select::make('category_ids')
                                            ->label('Catégories')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn (): array => static::categoryOptions()),
                                    ]),
                            ]),
                        Tab::make('Attributs & Variantes')
                            ->icon(Heroicon::OutlinedSwatch)
                            ->visible(fn (Get $get): bool => $get('type') === ProductType::Variable->value)
                            ->schema([
                                Section::make('Attributs & Variantes')
                                    ->description('Les attributs définissent les variantes (ex : Taille, Couleur)')
                                    ->schema([
                                        Select::make('attribute_ids')
                                            ->label('Attributs')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn (): array => static::attributeOptions()),
                                        Repeater::make('variants')
                                            ->label('Variantes')
                                            ->columns(2)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => static::variantItemLabel($state))
                                            ->schema([
                                                Repeater::make('selection')
                                                    ->label('Sélection')
                                                    ->schema([
                                                        Select::make('attribute_id')
                                                            ->label('Attribut')
                                                            ->options(fn (): array => static::attributeOptions())
                                                            ->searchable()
                                                            ->live()
                                                            ->required()
                                                            ->distinct(),
                                                        Select::make('attribute_value_id')
                                                            ->label('Valeur')
                                                            ->options(fn (Get $get): array => static::attributeValueOptions((int) $get('../../attribute_id')))
                                                            ->searchable()
                                                            ->required()
                                                            ->distinct(),
                                                    ])
                                                    ->columns(2)
                                                    ->addActionLabel('Ajouter un attribut'),
                                                TextInput::make('sku')->label('SKU')->maxLength(100),
                                                Grid::make(2)->schema([
                                                    TextInput::make('price')->label('Prix')->numeric()->minValue(0),
                                                    TextInput::make('compare_at_price')->label('Prix barré')->numeric()->minValue(0),
                                                    TextInput::make('cost_price')->label('Coût')->numeric()->minValue(0),
                                                    TextInput::make('weight')->label('Poids (kg)')->numeric()->minValue(0),
                                                ]),
                                                Toggle::make('manage_stock')->label('Gérer le stock')->default(true)->live(),
                                                TextInput::make('stock_quantity')->label('Stock')->numeric()->minValue(0)->default(0),
                                                TextInput::make('low_stock_threshold')->label('Seuil stock faible')->numeric()->minValue(0)->default(0),
                                                FileUpload::make('image')->label('Image de variante')->image()->disk('public')->directory('catalog/products'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Images')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                Section::make('Galerie')
                                    ->description('Galerie du produit — la première image cochée "principale" est mise en avant')
                                    ->schema([
                                        Repeater::make('images')
                                            ->label('Images')
                                            ->collapsible()
                                            ->schema([
                                                FileUpload::make('path')
                                                    ->label('Fichier')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('catalog/products'),
                                                Toggle::make('is_primary')
                                                    ->label('Image principale'),
                                                TextInput::make('alt')
                                                    ->label('Texte alternatif')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Traductions')
                            ->icon(Heroicon::OutlinedLanguage)
                            ->schema([
                                Section::make('Traductions')
                                    ->description('Contenu localisé FR / AR / EN — le nom est obligatoire dans la langue par défaut')
                                    ->schema([
                                        Tabs::make('translations')
                                            ->tabs(
                                                collect($locales->availableLocales())
                                                    ->map(fn (string $locale): Tab => static::translationTab($locale, $locales))
                                                    ->all(),
                                            ),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(['translations.name', 'translations.slug'])
                    ->getStateUsing(fn (Product $record): string => $record->translatedName($defaultLocale))
                    ->description(fn (Product $record): string => $record->sku ?? $record->translatedSlug($defaultLocale)),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ProductType $state): string => $state->label())
                    ->color('gray'),
                TextColumn::make('brand.name')
                    ->label('Marque')
                    ->getStateUsing(fn (Product $record): string => $record->brand?->translatedName($defaultLocale) ?? '—'),
                TextColumn::make('price')
                    ->label('Prix')
                    ->money('TND')
                    ->sortable(),
                TextColumn::make('realStockQuantity')
                    ->label('Stock')
                    ->state(fn (Product $record): int => $record->isVariable()
                        ? (int) $record->variants_stock_quantity_sum
                        : (int) $record->stock_quantity),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label())
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Active => 'success',
                        ProductStatus::Inactive, ProductStatus::Archived => 'gray',
                        ProductStatus::Draft => 'warning',
                    }),
                IconColumn::make('featured')
                    ->label('Vedette')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ProductStatus::options()),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(ProductType::options()),
                TernaryFilter::make('featured')
                    ->label('Vedette'),
                SelectFilter::make('brand_id')
                    ->label('Marque')
                    ->options(fn (): array => static::brandOptions()),
                SelectFilter::make('categories')
                    ->label('Catégorie')
                    ->relationship('categories', 'id')
                    ->multiple()
                    ->options(fn (): array => static::categoryOptions()),
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    private static function translationTab(string $locale, LocalizationService $locales): Tab
    {
        $defaultLocale = $locales->defaultLocale();

        return Tab::make($locales->localeLabel($locale) ?? $locale)
            ->schema([
                TextInput::make("translations.{$locale}.name")
                    ->label('Nom')
                    ->required(fn (): bool => $locale === $defaultLocale)
                    ->maxLength(255),
                TextInput::make("translations.{$locale}.slug")
                    ->label('Slug')
                    ->maxLength(255)
                    ->helperText('Laisser vide pour générer automatiquement depuis le nom.'),
                Textarea::make("translations.{$locale}.short_description")
                    ->label('Description courte')
                    ->rows(2),
                RichEditor::make("translations.{$locale}.description")
                    ->label('Description')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'orderedList',
                        'bulletList',
                        'blockquote',
                        'attachFiles',
                    ]),
                TextInput::make("translations.{$locale}.meta_title")
                    ->label('Titre SEO')
                    ->maxLength(255),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label('Description SEO')
                    ->rows(2),
                TextInput::make("translations.{$locale}.meta_keywords")
                    ->label('Mots-clés SEO')
                    ->maxLength(255)
                    ->helperText('Séparés par des virgules.'),
                Select::make("translations.{$locale}.robots")
                    ->label('Balise robots')
                    ->options([
                        'index, follow' => 'index, follow',
                        'index, nofollow' => 'index, nofollow',
                        'noindex, follow' => 'noindex, follow',
                        'noindex, nofollow' => 'noindex, nofollow',
                    ])
                    ->default('index, follow'),
                FileUpload::make("translations.{$locale}.og_image")
                    ->label('Image de partage (Open Graph)')
                    ->image()
                    ->directory('seo/products')
                    ->visibility('public'),
            ]);
    }

    private static function variantItemLabel(array $state): ?string
    {
        $label = 'Variante';

        $selection = $state['selection'] ?? [];
        if (is_array($selection)) {
            $parts = [];
            foreach ($selection as $row) {
                $valueId = (int) ($row['attribute_value_id'] ?? 0);
                if ($valueId > 0) {
                    $value = AttributeValue::with('translations')->find($valueId);
                    if ($value !== null) {
                        $parts[] = $value->translatedLabel(app(LocalizationService::class)->defaultLocale());
                    }
                }
            }
            if (count($parts) > 0) {
                $label = implode(' / ', $parts);
            }
        }

        return $label;
    }

    /**
     * @return array<int, string>
     */
    private static function brandOptions(): array
    {
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return Brand::query()
            ->with('translations')
            ->get()
            ->mapWithKeys(fn (Brand $brand): array => [
                $brand->id => $brand->translatedName($defaultLocale),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function categoryOptions(): array
    {
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return Category::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->translatedName($defaultLocale),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function attributeOptions(): array
    {
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return Attribute::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Attribute $attribute): array => [
                $attribute->id => $attribute->translatedName($defaultLocale),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function attributeValueOptions(int $attributeId): array
    {
        if ($attributeId <= 0) {
            return [];
        }

        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return AttributeValue::query()
            ->where('attribute_id', $attributeId)
            ->where('status', ContentStatus::Active->value)
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (AttributeValue $value): array => [
                $value->id => $value->translatedLabel($defaultLocale),
            ])
            ->all();
    }
}
