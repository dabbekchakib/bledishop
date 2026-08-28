<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings cache key
    |--------------------------------------------------------------------------
    */

    'cache_key' => 'bledishop.settings.cache',

    /*
    |--------------------------------------------------------------------------
    | Default settings
    |--------------------------------------------------------------------------
    |
    | Seeds the `settings` table and provides fallback values. Customized
    | values stored in the database always take precedence.
    |
    | Groups: general, seller, shop, localization, tax, shipping, seo,
    |         contact, social, theme
    |
    */

    'defaults' => [

        // Site general ----------------------------------------------------
        'site.name' => ['type' => 'string', 'value' => 'BlediShop', 'group' => 'general', 'is_public' => true, 'label' => 'Nom du site'],
        'site.description' => ['type' => 'text', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Description du site'],
        'site.logo' => ['type' => 'image', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Logo'],
        'site.favicon' => ['type' => 'image', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Favicon'],
        'site.email' => ['type' => 'email', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Email du site'],
        'site.phone' => ['type' => 'string', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Téléphone du site'],
        'site.address' => ['type' => 'text', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Adresse du site'],
        'site.city' => ['type' => 'string', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Ville'],
        'site.country' => ['type' => 'string', 'value' => '', 'group' => 'general', 'is_public' => true, 'label' => 'Pays'],

        // Seller ----------------------------------------------------------
        'seller.name' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Nom du vendeur'],
        'seller.legal_name' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Raison sociale'],
        'seller.vat_number' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Matricule fiscal'],
        'seller.trade_register' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Registre de commerce'],
        'seller.email' => ['type' => 'email', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Email du vendeur'],
        'seller.phone' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Téléphone du vendeur'],
        'seller.address' => ['type' => 'text', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Adresse du vendeur'],
        'seller.city' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Ville du vendeur'],
        'seller.country' => ['type' => 'string', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Pays du vendeur'],
        'seller.logo' => ['type' => 'image', 'value' => '', 'group' => 'seller', 'is_public' => false, 'label' => 'Logo du vendeur'],

        // Shop ------------------------------------------------------------
        'shop.enabled' => ['type' => 'boolean', 'value' => true, 'group' => 'shop', 'is_public' => true, 'label' => 'Boutique activée'],
        'shop.currency' => ['type' => 'string', 'value' => 'TND', 'group' => 'shop', 'is_public' => true, 'label' => 'Devise'],
        'shop.currency_symbol' => ['type' => 'string', 'value' => 'DT', 'group' => 'shop', 'is_public' => true, 'label' => 'Symbole de la devise'],
        'shop.currency_position' => ['type' => 'string', 'value' => 'after', 'group' => 'shop', 'is_public' => true, 'label' => 'Position du symbole'],
        'shop.decimal_places' => ['type' => 'integer', 'value' => 3, 'group' => 'shop', 'is_public' => true, 'label' => 'Nombre de décimales'],
        'shop.price_includes_tax' => ['type' => 'boolean', 'value' => true, 'group' => 'shop', 'is_public' => true, 'label' => 'Les prix affichés incluent la TVA'],
        'shop.products_per_page' => ['type' => 'integer', 'value' => 12, 'group' => 'shop', 'is_public' => true, 'label' => 'Produits par page'],
        'shop.show_out_of_stock' => ['type' => 'boolean', 'value' => false, 'group' => 'shop', 'is_public' => true, 'label' => 'Afficher les produits en rupture'],
        'shop.guest_checkout_enabled' => ['type' => 'boolean', 'value' => true, 'group' => 'shop', 'is_public' => true, 'label' => 'Commande invité autorisée'],
        'shop.wishlist_enabled' => ['type' => 'boolean', 'value' => false, 'group' => 'shop', 'is_public' => true, 'label' => 'Wishlist activée'],
        'shop.reviews_enabled' => ['type' => 'boolean', 'value' => false, 'group' => 'shop', 'is_public' => true, 'label' => 'Avis produits activés'],
        'shop.featured' => ['type' => 'boolean', 'value' => true, 'group' => 'shop', 'is_public' => true, 'label' => 'Afficher la section produits vedettes'],

        // Localization ----------------------------------------------------
        'localization.default_locale' => ['type' => 'string', 'value' => 'fr', 'group' => 'localization', 'is_public' => true, 'label' => 'Langue par défaut'],
        'localization.available_locales' => ['type' => 'array', 'value' => ['fr', 'ar', 'en'], 'group' => 'localization', 'is_public' => true, 'label' => 'Langues disponibles'],
        'localization.browser_detection_enabled' => ['type' => 'boolean', 'value' => true, 'group' => 'localization', 'is_public' => true, 'label' => 'Détection de la langue du navigateur'],
        'localization.timezone' => ['type' => 'string', 'value' => 'Africa/Tunis', 'group' => 'localization', 'is_public' => true, 'label' => 'Fuseau horaire'],
        'localization.date_format' => ['type' => 'string', 'value' => 'd/m/Y', 'group' => 'localization', 'is_public' => true, 'label' => 'Format de date'],
        'localization.time_format' => ['type' => 'string', 'value' => 'H:i', 'group' => 'localization', 'is_public' => true, 'label' => 'Format de l\'heure'],

        // Tax -------------------------------------------------------------
        'tax.enabled' => ['type' => 'boolean', 'value' => false, 'group' => 'tax', 'is_public' => false, 'label' => 'TVA activée'],
        'tax.rate' => ['type' => 'decimal', 'value' => 19, 'group' => 'tax', 'is_public' => false, 'label' => 'Taux de TVA'],
        'tax.label' => ['type' => 'string', 'value' => 'TVA', 'group' => 'tax', 'is_public' => false, 'label' => 'Libellé TVA'],
        'tax.included_in_price' => ['type' => 'boolean', 'value' => false, 'group' => 'tax', 'is_public' => false, 'label' => 'TVA incluse dans les prix'],

        // Shipping --------------------------------------------------------
        'shipping.enabled' => ['type' => 'boolean', 'value' => true, 'group' => 'shipping', 'is_public' => false, 'label' => 'Livraison activée'],
        'shipping.default_cost' => ['type' => 'decimal', 'value' => 0, 'group' => 'shipping', 'is_public' => false, 'label' => 'Coût standard de livraison'],
        'shipping.free_shipping_enabled' => ['type' => 'boolean', 'value' => false, 'group' => 'shipping', 'is_public' => false, 'label' => 'Livraison gratuite'],
        'shipping.free_shipping_threshold' => ['type' => 'decimal', 'value' => 0, 'group' => 'shipping', 'is_public' => false, 'label' => 'Seuil de livraison gratuite'],

        // SEO -------------------------------------------------------------
        'seo.title' => ['type' => 'string', 'value' => '', 'group' => 'seo', 'is_public' => true, 'label' => 'Titre SEO'],
        'seo.description' => ['type' => 'text', 'value' => '', 'group' => 'seo', 'is_public' => true, 'label' => 'Description SEO'],
        'seo.keywords' => ['type' => 'array', 'value' => [], 'group' => 'seo', 'is_public' => true, 'label' => 'Mots-clés SEO'],
        'seo.robots' => ['type' => 'string', 'value' => 'index, follow', 'group' => 'seo', 'is_public' => true, 'label' => 'Balise robots'],

        // Contact ---------------------------------------------------------
        'contact.email' => ['type' => 'email', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Email de contact'],
        'contact.phone' => ['type' => 'string', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Téléphone de contact'],
        'contact.address' => ['type' => 'text', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Adresse de contact'],
        'contact.city' => ['type' => 'string', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Ville de contact'],
        'contact.country' => ['type' => 'string', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Pays de contact'],
        'contact.hours' => ['type' => 'text', 'value' => '', 'group' => 'contact', 'is_public' => true, 'label' => 'Horaires'],

        // Social ----------------------------------------------------------
        'social.facebook' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'Facebook'],
        'social.instagram' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'Instagram'],
        'social.linkedin' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'LinkedIn'],
        'social.youtube' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'YouTube'],
        'social.tiktok' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'TikTok'],
        'social.x' => ['type' => 'url', 'value' => '', 'group' => 'social', 'is_public' => true, 'label' => 'X (Twitter)'],

        // Theme palette ---------------------------------------------------
        'theme.primary_color' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur principale'],
        'theme.primary_hover_color' => ['type' => 'color', 'value' => '#1D4ED8', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur principale (survol)'],
        'theme.secondary_color' => ['type' => 'color', 'value' => '#64748B', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur secondaire'],
        'theme.secondary_hover_color' => ['type' => 'color', 'value' => '#475569', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur secondaire (survol)'],
        'theme.accent_color' => ['type' => 'color', 'value' => '#F59E0B', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur d\'accent'],
        'theme.success_color' => ['type' => 'color', 'value' => '#16A34A', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur de succès'],
        'theme.warning_color' => ['type' => 'color', 'value' => '#D97706', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur d\'alerte'],
        'theme.danger_color' => ['type' => 'color', 'value' => '#DC2626', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur de danger'],
        'theme.info_color' => ['type' => 'color', 'value' => '#0284C7', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur d\'information'],

        // Theme text & background ----------------------------------------
        'theme.text_color' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur du texte'],
        'theme.text_muted_color' => ['type' => 'color', 'value' => '#64748B', 'group' => 'theme', 'is_public' => true, 'label' => 'Texte secondaire'],
        'theme.heading_color' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur des titres'],
        'theme.background_color' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Arrière-plan principal'],
        'theme.surface_color' => ['type' => 'color', 'value' => '#F8FAFC', 'group' => 'theme', 'is_public' => true, 'label' => 'Surface'],
        'theme.border_color' => ['type' => 'color', 'value' => '#E2E8F0', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur des bordures'],
        'theme.link_color' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur des liens'],
        'theme.link_hover_color' => ['type' => 'color', 'value' => '#1D4ED8', 'group' => 'theme', 'is_public' => true, 'label' => 'Couleur des liens (survol)'],

        // Theme header / navigation / footer ------------------------------
        'theme.header_color' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Header - arrière-plan'],
        'theme.header_text_color' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Header - texte'],
        'theme.nav_background' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Navigation - arrière-plan'],
        'theme.nav_text' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Navigation - texte'],
        'theme.nav_active' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Navigation - actif'],
        'theme.footer_color' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Footer - arrière-plan'],
        'theme.footer_text_color' => ['type' => 'color', 'value' => '#E2E8F0', 'group' => 'theme', 'is_public' => true, 'label' => 'Footer - texte'],

        // Theme components -------------------------------------------------
        'theme.card_background' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Cartes - arrière-plan'],
        'theme.card_border' => ['type' => 'color', 'value' => '#E2E8F0', 'group' => 'theme', 'is_public' => true, 'label' => 'Cartes - bordure'],
        'theme.button_primary_background' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Bouton principal - arrière-plan'],
        'theme.button_primary_text' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Bouton principal - texte'],
        'theme.button_secondary_background' => ['type' => 'color', 'value' => '#64748B', 'group' => 'theme', 'is_public' => true, 'label' => 'Bouton secondaire - arrière-plan'],
        'theme.button_secondary_text' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Bouton secondaire - texte'],
        'theme.input_background' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Champs de saisie - arrière-plan'],
        'theme.input_text' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Champs de saisie - texte'],
        'theme.input_border' => ['type' => 'color', 'value' => '#CBD5E1', 'group' => 'theme', 'is_public' => true, 'label' => 'Champs de saisie - bordure'],
        'theme.input_focus' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Champs de saisie - focus'],
        'theme.badge_background' => ['type' => 'color', 'value' => '#2563EB', 'group' => 'theme', 'is_public' => true, 'label' => 'Badges - arrière-plan'],
        'theme.badge_text' => ['type' => 'color', 'value' => '#FFFFFF', 'group' => 'theme', 'is_public' => true, 'label' => 'Badges - texte'],

        // Theme dark mode (prepared, not activated) ----------------------
        'theme.dark_mode_enabled' => ['type' => 'boolean', 'value' => false, 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre activé'],
        'theme.dark_background_color' => ['type' => 'color', 'value' => '#0F172A', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - arrière-plan'],
        'theme.dark_surface_color' => ['type' => 'color', 'value' => '#1E293B', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - surface'],
        'theme.dark_text_color' => ['type' => 'color', 'value' => '#E2E8F0', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - texte'],
        'theme.dark_text_muted_color' => ['type' => 'color', 'value' => '#94A3B8', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - texte secondaire'],
        'theme.dark_border_color' => ['type' => 'color', 'value' => '#334155', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - bordures'],
        'theme.dark_heading_color' => ['type' => 'color', 'value' => '#F8FAFC', 'group' => 'theme', 'is_public' => true, 'label' => 'Mode sombre - titres'],
    ],

];
