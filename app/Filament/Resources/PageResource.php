<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Models\Page;
use App\Services\LocalizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'pages';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(LocalizationService::class);

        return $schema
            ->components([
                Section::make('Publication')
                    ->description('Paramètres généraux de la page')
                    ->columns(2)
                    ->schema([
                        Select::make('template')
                            ->label('Modèle')
                            ->options([
                                'default' => 'Par défaut',
                                'wide' => 'Pleine largeur',
                            ])
                            ->default('default'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        DateTimePicker::make('published_at')
                            ->label('Date de publication')
                            ->helperText('Laisser vide pour publier immédiatement.'),
                    ]),
                Section::make('Traductions')
                    ->description('Contenu localisé FR / AR / EN — le titre est obligatoire dans la langue par défaut')
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
                TextColumn::make('title')
                    ->label('Titre')
                    ->getStateUsing(fn (Page $record): string => $record->translatedTitle($defaultLocale))
                    ->searchable(['translations.title', 'translations.slug'])
                    ->description(fn (Page $record): string => '/'.ltrim($record->translatedSlug($defaultLocale), '/')),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Modifiée')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('updated_at', 'desc')
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    private static function translationTab(string $locale, LocalizationService $locales): Tab
    {
        $defaultLocale = $locales->defaultLocale();

        return Tab::make($locales->localeLabel($locale) ?? $locale)
            ->schema([
                TextInput::make("translations.{$locale}.title")
                    ->label('Titre')
                    ->required(fn (): bool => $locale === $defaultLocale)
                    ->maxLength(255),
                TextInput::make("translations.{$locale}.slug")
                    ->label('Slug')
                    ->maxLength(255)
                    ->helperText('Laisser vide pour générer automatiquement depuis le titre.'),
                Textarea::make("translations.{$locale}.excerpt")
                    ->label('Extrait')
                    ->rows(2),
                RichEditor::make("translations.{$locale}.content")
                    ->label('Contenu')
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('cms'),
                Section::make('Optimisation SEO')
                    ->description('Préférences de référencement pour cette langue')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
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
                            ->label('Directive robots')
                            ->options([
                                'index, follow' => 'Indexer — Suivre',
                                'noindex, follow' => 'Ne pas indexer — Suivre',
                                'noindex, nofollow' => 'Ne pas indexer — Ne pas suivre',
                            ])
                            ->placeholder('Utiliser le paramètre global'),
                        FileUpload::make("translations.{$locale}.og_image")
                            ->label('Image Open Graph')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('cms/og')
                            ->maxSize(2048),
                    ]),
            ]);
    }
}
