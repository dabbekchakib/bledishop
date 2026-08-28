<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Filament\Resources\BrandResource\Pages\CreateBrand;
use App\Filament\Resources\BrandResource\Pages\EditBrand;
use App\Filament\Resources\BrandResource\Pages\ListBrands;
use App\Models\Brand;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $modelLabel = 'marque';

    protected static ?string $pluralModelLabel = 'marques';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(LocalizationService::class);

        return $schema
            ->components([
                Section::make('Informations')
                    ->description('Paramètres généraux de la marque')
                    ->columns(2)
                    ->schema([
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
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('catalog/brands')
                            ->maxSize(2048),
                        TextInput::make('website')
                            ->label('Site web')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://exemple.com'),
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
        $defaultLocale = app(LocalizationService::class)->defaultLocale();

        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->getStateUsing(fn (Brand $record): string => $record->translatedName($defaultLocale))
                    ->searchable(['translations.name', 'translations.slug']),
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
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
}
