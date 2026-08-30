<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages\CreateRedirect;
use App\Filament\Resources\RedirectResource\Pages\EditRedirect;
use App\Filament\Resources\RedirectResource\Pages\ListRedirects;
use App\Models\UrlRedirect;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = UrlRedirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.seo');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.redirect');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.redirect_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Redirection')
                    ->description('Redirige un ancien chemin vers une nouvelle adresse (301 permanent / 302 temporaire)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('source')
                            ->label('Chemin source')
                            ->required()
                            ->placeholder('/ancien-chemin')
                            ->helperText('Saisir sans le domaine ni le préfixe de langue (ex : /ancien-chemin).')
                            ->afterStateHydrated(fn (TextInput $component, ?string $state) => $component->state($state))
                            ->dehydrateStateUsing(fn (string $state): string => '/'.ltrim($state, '/')),
                        Select::make('status_code')
                            ->label('Type')
                            ->options([
                                301 => '301 — Permanent',
                                302 => '302 — Temporaire',
                            ])
                            ->default(301)
                            ->required(),
                        TextInput::make('destination')
                            ->label('Destination')
                            ->required()
                            ->placeholder('https://exemple.com/nouvelle-page ou /nouvelle-page')
                            ->helperText('Peut être une URL complète (http/https) ou un chemin relatif commençant par /.')
                            ->rules([
                                'regex:/^(\/|https?:\/\/)/',
                                function (string $attribute, $value, \Closure $fail): void {
                                    if (is_string($value) && preg_match('/[\\\\\x00-\x1F\x7F]|^\/\//', $value)) {
                                        $fail('La destination est invalide : chemin relatif ou URL http(s) uniquement.');
                                    }
                                },
                            ]),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source')
                    ->label('Chemin source')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable(),
                TextColumn::make('status_code')
                    ->label('Code')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => "{$state}")
                    ->color(fn (int $state): string => $state === 301 ? 'success' : 'warning'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}
