<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Category;
use App\Services\LocalizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $modelLabel = 'catégorie';

    protected static ?string $pluralModelLabel = 'catégories';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'parent.translations']);
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(LocalizationService::class);

        return $schema
            ->components([
                Section::make('Informations')
                    ->description('Paramètres généraux de la catégorie')
                    ->columns(2)
                    ->schema([
                        Select::make('parent_id')
                            ->label('Catégorie parente')
                            ->options(fn (?Category $record): array => static::parentOptions($record))
                            ->searchable()
                            ->placeholder('Aucune — catégorie racine'),
                        Select::make('status')
                            ->label('Statut')
                            ->options(ContentStatus::options())
                            ->default(ContentStatus::Active->value)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Toggle::make('is_featured')
                            ->label('Mise en avant'),
                        FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('catalog/categories')
                            ->maxSize(2048),
                        TextInput::make('icon')
                            ->label('Icône')
                            ->maxLength(100)
                            ->helperText('Identifiant neutre, ex : device-mobile'),
                    ]),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        $locales = app(LocalizationService::class);
        $defaultLocale = $locales->defaultLocale();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->getStateUsing(fn (Category $record): string => $record->translatedName($defaultLocale))
                    ->searchable(['translations.name', 'translations.slug'])
                    ->description(fn (Category $record): ?string => $record->parent
                        ? 'Sous-catégorie de « '.$record->parent->translatedName($defaultLocale).' »'
                        : null),
                IconColumn::make('is_featured')
                    ->label('Vedette')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ContentStatus $state): string => $state->label())
                    ->color(fn (ContentStatus $state): string => $state->isActive() ? 'success' : 'gray'),
                TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ContentStatus::options()),
                SelectFilter::make('parent')
                    ->label('Catégorie parente')
                    ->attribute('parent_id')
                    ->options(fn (): array => static::parentOptions(null)),
                TernaryFilter::make('is_featured')
                    ->label('Vedette'),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
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
                Textarea::make("translations.{$locale}.description")
                    ->label('Description')
                    ->rows(3),
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
            ]);
    }

    /**
     * Candidate parents: every category except the record itself and its own
     * descendants (which would create a hierarchy cycle).
     *
     * @return array<int, string>
     */
    private static function parentOptions(?Category $except): array
    {
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return Category::query()
            ->with('translations')
            ->when(
                $except !== null,
                fn (Builder $query) => $query
                    ->whereKeyNot($except->id)
                    ->whereNotIn('id', $except->descendantIds()),
            )
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->translatedName($defaultLocale),
            ])
            ->all();
    }
}
