<?php

namespace App\Filament\Resources;

use App\Enums\AttributeType;
use App\Enums\ContentStatus;
use App\Filament\Resources\AttributeResource\Pages\CreateAttribute;
use App\Filament\Resources\AttributeResource\Pages\EditAttribute;
use App\Filament\Resources\AttributeResource\Pages\ListAttributes;
use App\Models\Attribute;
use App\Services\LocalizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $modelLabel = 'attribut';

    protected static ?string $pluralModelLabel = 'attributs';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'values.translations']);
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(LocalizationService::class);

        return $schema
            ->schema([
                Section::make('Informations')
                    ->description('Paramètres généraux de l\'attribut')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->maxLength(100)
                            ->required()
                            ->helperText('Identifiant technique unique, ex : taille, couleur.'),
                        Select::make('type')
                            ->label('Type')
                            ->options(AttributeType::options())
                            ->default(AttributeType::Select->value)
                            ->live()
                            ->required(),
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
                    ]),
                Section::make('Valeurs')
                    ->description('Les valeurs proposées pour cet attribut')
                    ->schema([
                        Repeater::make('values')
                            ->label('Valeurs')
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['value'] ?? 'Valeur')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Valeur')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('sort_order')
                                    ->label('Ordre')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                ColorPicker::make('color_code')
                                    ->label('Couleur')
                                    ->visible(fn (Get $get): bool => $get('../../type') === AttributeType::Color->value),
                                Toggle::make('status_is_active')
                                    ->label('Active')
                                    ->default(true),
                                Tabs::make('value_translations')
                                    ->label('Étiquettes localisées')
                                    ->tabs(
                                        collect($locales->availableLocales())
                                            ->map(function (string $locale) use ($locales): Tab {
                                                return Tab::make($locales->localeLabel($locale) ?? $locale)
                                                    ->schema([
                                                        TextInput::make("translations.{$locale}.label")
                                                            ->label('Étiquette')
                                                            ->maxLength(100),
                                                    ]);
                                            })
                                            ->all(),
                                    ),
                            ]),
                    ]),
                Section::make('Traductions')
                    ->description('Nom de l\'attribut FR / AR / EN — obligatoire dans la langue par défaut')
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
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(['translations.name'])
                    ->getStateUsing(fn (Attribute $record): string => $record->translatedName($defaultLocale)),
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (AttributeType $state): string => $state->label())
                    ->color('gray'),
                TextColumn::make('values_count')
                    ->label('Valeurs')
                    ->counts('values'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ContentStatus $state): string => $state->label())
                    ->color(fn (ContentStatus $state): string => $state->isActive() ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(AttributeType::options()),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ContentStatus::options()),
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
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
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
            ]);
    }
}
