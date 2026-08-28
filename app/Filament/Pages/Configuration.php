<?php

namespace App\Filament\Pages;

use App\Enums\Locale;
use App\Services\SettingsService;
use App\Services\ThemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Configuration extends Page
{
    protected string $view = 'filament.pages.configuration';

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?string $title = 'Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'configuration';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('settings.view') ?? false);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Général')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Général')
                                    ->description('Informations générales du site')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('site.name')
                                            ->label('Nom du site')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('site.description')
                                            ->label('Description')
                                            ->rows(3),
                                        FileUpload::make('site.logo')
                                            ->label('Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->maxSize(2048),
                                        FileUpload::make('site.favicon')
                                            ->label('Favicon')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->maxSize(1024),
                                        TextInput::make('site.email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('site.phone')
                                            ->label('Téléphone')
                                            ->tel(),
                                        Textarea::make('site.address')
                                            ->label('Adresse')
                                            ->rows(2),
                                        TextInput::make('site.city')
                                            ->label('Ville'),
                                        TextInput::make('site.country')
                                            ->label('Pays'),
                                    ]),
                            ]),
                        Tab::make('Vendeur')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
                                Section::make('Vendeur')
                                    ->description('Informations légales et commerciales du vendeur')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('seller.name')
                                            ->label('Nom'),
                                        TextInput::make('seller.legal_name')
                                            ->label('Raison sociale'),
                                        Textarea::make('seller.address')
                                            ->label('Adresse')
                                            ->rows(2),
                                        TextInput::make('seller.city')
                                            ->label('Ville'),
                                        TextInput::make('seller.country')
                                            ->label('Pays'),
                                        TextInput::make('seller.phone')
                                            ->label('Téléphone')
                                            ->tel(),
                                        TextInput::make('seller.email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('seller.vat_number')
                                            ->label('Matricule fiscal'),
                                        TextInput::make('seller.trade_register')
                                            ->label('Registre de commerce'),
                                        FileUpload::make('seller.logo')
                                            ->label('Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->maxSize(2048),
                                    ]),
                            ]),
                        Tab::make('Boutique')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
                                Section::make('Boutique')
                                    ->description('Comportement général de la boutique')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('shop.enabled')
                                            ->label('Boutique activée')
                                            ->default(true),
                                        Toggle::make('shop.show_out_of_stock')
                                            ->label('Afficher les produits en rupture'),
                                        Select::make('shop.products_per_page')
                                            ->label('Produits par page')
                                            ->options([
                                                8 => '8',
                                                12 => '12',
                                                16 => '16',
                                                24 => '24',
                                                36 => '36',
                                            ]),
                                        Toggle::make('shop.guest_checkout_enabled')
                                            ->label('Commande invité autorisée')
                                            ->default(true),
                                        Toggle::make('shop.wishlist_enabled')
                                            ->label('Wishlist activée'),
                                        Toggle::make('shop.reviews_enabled')
                                            ->label('Avis produits activés'),
                                        Toggle::make('shop.featured')
                                            ->label('Afficher la section produits vedettes'),
                                    ]),
                                Section::make('Devise et prix')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('shop.currency')
                                            ->label('Devise')
                                            ->options([
                                                'TND' => 'TND',
                                                'EUR' => 'EUR',
                                                'USD' => 'USD',
                                                'GBP' => 'GBP',
                                                'DZD' => 'DZD',
                                                'MAD' => 'MAD',
                                            ]),
                                        TextInput::make('shop.currency_symbol')
                                            ->label('Symbole de la devise')
                                            ->maxLength(10),
                                        Select::make('shop.currency_position')
                                            ->label('Position du symbole')
                                            ->options([
                                                'before' => 'Avant (€10.00)',
                                                'after' => 'Après (10.00 TND)',
                                            ]),
                                        Select::make('shop.decimal_places')
                                            ->label('Nombre de décimales')
                                            ->options([
                                                0 => '0',
                                                1 => '1',
                                                2 => '2',
                                                3 => '3',
                                                4 => '4',
                                            ]),
                                        Toggle::make('shop.price_includes_tax')
                                            ->label('Les prix affichés incluent la TVA'),
                                    ]),
                            ]),
                        Tab::make('Apparence')
                            ->icon(Heroicon::OutlinedMoon)
                            ->schema([
                                Section::make('Mode sombre')
                                    ->description('La bascule sera câblée lors du module multilingue')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('theme.dark_mode_enabled')
                                            ->label('Activer le mode sombre'),
                                    ]),
                            ]),
                        Tab::make('Couleurs')
                            ->icon(Heroicon::OutlinedPaintBrush)
                            ->schema([
                                Section::make('Couleurs du thème')
                                    ->description('Couleurs principales appliquées à tout le site')
                                    ->columns(3)
                                    ->schema([
                                        $this->colorField('theme.primary_color', 'Couleur principale'),
                                        $this->colorField('theme.primary_hover_color', 'Principal (survol)'),
                                        $this->colorField('theme.secondary_color', 'Couleur secondaire'),
                                        $this->colorField('theme.secondary_hover_color', 'Secondaire (survol)'),
                                        $this->colorField('theme.accent_color', 'Couleur d\'accent'),
                                        $this->colorField('theme.success_color', 'Succès'),
                                        $this->colorField('theme.warning_color', 'Avertissement'),
                                        $this->colorField('theme.danger_color', 'Danger'),
                                        $this->colorField('theme.info_color', 'Information'),
                                    ]),
                                Section::make('Texte et fond')
                                    ->columns(3)
                                    ->schema([
                                        $this->colorField('theme.text_color', 'Texte'),
                                        $this->colorField('theme.text_muted_color', 'Texte estompé'),
                                        $this->colorField('theme.heading_color', 'Titres'),
                                        $this->colorField('theme.background_color', 'Fond'),
                                        $this->colorField('theme.surface_color', 'Surfaces'),
                                        $this->colorField('theme.border_color', 'Bordures'),
                                    ]),
                                Section::make('Liens et navigation')
                                    ->columns(3)
                                    ->schema([
                                        $this->colorField('theme.link_color', 'Liens'),
                                        $this->colorField('theme.link_hover_color', 'Liens (survol)'),
                                        $this->colorField('theme.nav_background', 'Navigation (fond)'),
                                        $this->colorField('theme.nav_text', 'Navigation (texte)'),
                                        $this->colorField('theme.nav_active', 'Navigation (actif)'),
                                        $this->colorField('theme.header_color', 'En-tête (fond)'),
                                        $this->colorField('theme.header_text_color', 'En-tête (texte)'),
                                        $this->colorField('theme.footer_color', 'Pied de page (fond)'),
                                        $this->colorField('theme.footer_text_color', 'Pied de page (texte)'),
                                    ]),
                                Section::make('Composants')
                                    ->columns(3)
                                    ->schema([
                                        $this->colorField('theme.card_background', 'Cartes (fond)'),
                                        $this->colorField('theme.card_border', 'Cartes (bordure)'),
                                        $this->colorField('theme.badge_background', 'Badges (fond)'),
                                        $this->colorField('theme.badge_text', 'Badges (texte)'),
                                        $this->colorField('theme.button_primary_background', 'Bouton principal (fond)'),
                                        $this->colorField('theme.button_primary_text', 'Bouton principal (texte)'),
                                        $this->colorField('theme.button_secondary_background', 'Bouton secondaire (fond)'),
                                        $this->colorField('theme.button_secondary_text', 'Bouton secondaire (texte)'),
                                        $this->colorField('theme.input_background', 'Champs (fond)'),
                                        $this->colorField('theme.input_text', 'Champs (texte)'),
                                        $this->colorField('theme.input_border', 'Champs (bordure)'),
                                        $this->colorField('theme.input_focus', 'Champs (focus)'),
                                    ]),
                                Section::make('Couleurs du mode sombre')
                                    ->description('Utilisées uniquement si le mode sombre est activé')
                                    ->columns(3)
                                    ->schema([
                                        $this->colorField('theme.dark_background_color', 'Fond sombre'),
                                        $this->colorField('theme.dark_surface_color', 'Surfaces sombres'),
                                        $this->colorField('theme.dark_text_color', 'Texte sombre'),
                                        $this->colorField('theme.dark_text_muted_color', 'Texte estompé sombre'),
                                        $this->colorField('theme.dark_border_color', 'Bordures sombres'),
                                        $this->colorField('theme.dark_heading_color', 'Titres sombres'),
                                    ]),
                                Section::make('Aperçu')
                                    ->description('Aperçu en direct des couleurs du thème')
                                    ->schema([
                                        View::make('filament.pages.theme-preview')
                                            ->viewData(function (Component $component): array {
                                                $livewire = $component->getLivewire();
                                                $data = is_array($livewire->data) ? $livewire->data : [];

                                                $colors = [];
                                                foreach (config('settings.defaults', []) as $key => $meta) {
                                                    if (! Str::startsWith($key, 'theme.')) {
                                                        continue;
                                                    }

                                                    $suffix = (string) Str::after($key, 'theme.');
                                                    $value = data_get($data, $key, $meta['value'] ?? '');

                                                    $colors[$suffix] = ThemeService::validateColor((string) $value) ?? (string) ($meta['value'] ?? '');
                                                }

                                                return ['colors' => $colors];
                                            }),
                                    ]),
                            ]),
                        Tab::make('Localisation')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                Section::make('Localisation')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('localization.default_locale')
                                            ->label('Langue par défaut')
                                            ->options(Locale::options())
                                            ->required()
                                            ->rules([
                                                fn (Get $get): string => 'in:'.implode(
                                                    ',',
                                                    array_values((array) ($get('localization.available_locales') ?? ['fr'])),
                                                ),
                                            ]),
                                        Select::make('localization.available_locales')
                                            ->label('Langues disponibles')
                                            ->multiple()
                                            ->options(Locale::options())
                                            ->required(),
                                        Toggle::make('localization.browser_detection_enabled')
                                            ->label('Détection de la langue du navigateur')
                                            ->helperText('Utiliser la langue du navigateur pour le premier visiteur sans préférence.'),
                                        Select::make('localization.timezone')
                                            ->label('Fuseau horaire')
                                            ->searchable()
                                            ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())
                                                ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
                                                ->all()),
                                        Select::make('localization.date_format')
                                            ->label('Format de date')
                                            ->options([
                                                'd/m/Y' => '31/12/2026',
                                                'Y-m-d' => '2026-12-31',
                                                'm/d/Y' => '12/31/2026',
                                                'd M Y' => '31 déc 2026',
                                            ]),
                                        Select::make('localization.time_format')
                                            ->label('Format d\'heure')
                                            ->options([
                                                'H:i' => '14:30',
                                                'h:i A' => '02:30 PM',
                                            ]),
                                    ]),
                            ]),
                        Tab::make('TVA')
                            ->icon(Heroicon::OutlinedPercentBadge)
                            ->schema([
                                Section::make('TVA')
                                    ->description('Calculée côté serveur')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('tax.enabled')
                                            ->label('TVA activée'),
                                        Toggle::make('tax.included_in_price')
                                            ->label('Les prix affichés incluent la TVA'),
                                        TextInput::make('tax.rate')
                                            ->label('Taux (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01),
                                        TextInput::make('tax.label')
                                            ->label('Libellé')
                                            ->maxLength(50),
                                    ]),
                            ]),
                        Tab::make('Livraison')
                            ->icon(Heroicon::OutlinedTruck)
                            ->schema([
                                Section::make('Livraison')
                                    ->description('Coût calculé côté serveur au moment de la commande')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('shipping.enabled')
                                            ->label('Livraison activée'),
                                        Toggle::make('shipping.free_shipping_enabled')
                                            ->label('Livraison gratuite'),
                                        TextInput::make('shipping.default_cost')
                                            ->label('Coût standard')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.001),
                                        TextInput::make('shipping.free_shipping_threshold')
                                            ->label('Seuil de livraison gratuite')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.001),
                                    ]),
                            ]),
                        Tab::make('SEO')
                            ->icon(Heroicon::OutlinedMagnifyingGlassCircle)
                            ->schema([
                                Section::make('SEO')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('seo.title')
                                            ->label('Titre par défaut')
                                            ->maxLength(255),
                                        Textarea::make('seo.description')
                                            ->label('Meta description')
                                            ->rows(3),
                                        TagsInput::make('seo.keywords')
                                            ->label('Mots-clés')
                                            ->placeholder('mot-clé'),
                                        TextInput::make('seo.robots')
                                            ->label('Meta robots')
                                            ->placeholder('index, follow'),
                                    ]),
                            ]),
                        Tab::make('Contact')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make('Contact')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('contact.email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('contact.phone')
                                            ->label('Téléphone')
                                            ->tel(),
                                        Textarea::make('contact.address')
                                            ->label('Adresse')
                                            ->rows(2),
                                        TextInput::make('contact.city')
                                            ->label('Ville'),
                                        TextInput::make('contact.country')
                                            ->label('Pays'),
                                    ]),
                            ]),
                        Tab::make('Réseaux sociaux')
                            ->icon(Heroicon::OutlinedShare)
                            ->schema([
                                Section::make('Réseaux sociaux')
                                    ->description('Liens affichés dans le pied de page')
                                    ->schema([
                                        TextInput::make('social.facebook')
                                            ->label('Facebook')
                                            ->url(),
                                        TextInput::make('social.instagram')
                                            ->label('Instagram')
                                            ->url(),
                                        TextInput::make('social.linkedin')
                                            ->label('LinkedIn')
                                            ->url(),
                                        TextInput::make('social.youtube')
                                            ->label('YouTube')
                                            ->url(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        $state = $this->form->getState();

        foreach ($this->form->getFlatFields() as $field) {
            $key = $field->getStatePath(isAbsolute: false);

            if (blank($key)) {
                continue;
            }

            app(SettingsService::class)->set($key, data_get($state, $key, ''));
        }

        Notification::make()
            ->title('Configuration enregistrée')
            ->success()
            ->send();
    }

    public function resetTheme(): void
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        app(ThemeService::class)->reset();

        $state = $this->form->getState();
        $themeDefaults = [];

        foreach (config('settings.defaults', []) as $key => $meta) {
            if (! Str::startsWith($key, 'theme.')) {
                continue;
            }

            data_set($themeDefaults, (string) Str::after($key, 'theme.'), $meta['value'] ?? '');
        }

        Arr::set($state, 'theme', $themeDefaults);

        $this->form->fill($state);

        Notification::make()
            ->title('Couleurs réinitialisées')
            ->success()
            ->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveSettings')
                ->label('Enregistrer')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->visible(fn (): bool => (bool) auth()->user()?->can('settings.update')),
            Action::make('resetColors')
                ->label('Réinitialiser les couleurs')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Restaurer les couleurs par défaut du thème ?')
                ->action('resetTheme')
                ->visible(fn (): bool => (bool) auth()->user()?->can('settings.update')),
        ];
    }

    private function fillForm(): void
    {
        $values = app(SettingsService::class)->all();

        $data = [];

        foreach (config('settings.defaults', []) as $key => $meta) {
            $value = $values[$key] ?? $meta['value'] ?? '';

            data_set($data, $key, $value);
        }

        $this->form->fill($data);
    }

    private function colorField(string $key, string $label): ColorPicker
    {
        return ColorPicker::make($key)
            ->label($label)
            ->required()
            ->live()
            ->rules([ThemeService::hexRule()]);
    }
}
