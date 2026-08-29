<?php

return [
    'title' => 'Commande',
    'meta_description' => 'Finalisez votre commande.',
    'intro' => 'Vos informations, puis confirmation de votre commande.',
    'form_errors_title' => 'Veuillez corriger les erreurs ci-dessous.',

    'contact_title' => 'Informations de contact',
    'first_name' => 'Prénom',
    'last_name' => 'Nom',
    'email_label' => 'Adresse e-mail',
    'phone' => 'Téléphone',

    'shipping_title' => 'Adresse de livraison',
    'address' => 'Adresse',
    'city' => 'Ville',
    'postal_code' => 'Code postal',
    'country' => 'Pays',
    'notes' => 'Note pour la commande (facultatif)',

    'account_title' => 'Créer un compte',
    'account_hint' => 'Activez cette option pour créer un compte et suivre vos commandes plus tard.',
    'account_create_label' => 'Je souhaite créer un compte avec mon adresse e-mail.',
    'password' => 'Mot de passe',
    'password_confirmation' => 'Confirmation du mot de passe',

    'summary' => 'Récapitulatif',
    'qty_label' => '{0} quantité|{1} :count article|[2,*] :count articles',
    'subtotal' => 'Sous-total',
    'total' => 'Total',
    'shipping_note' => 'La livraison et la TVA, si applicable, seront gérées ultérieurement selon les conditions du vendeur.',
    'place_order' => 'Confirmer la commande',
    'privacy_hint' => 'Votre commande est enregistrée sans paiement en ligne.',

    'confirmation_title' => 'Commande enregistrée',
    'confirmation_status' => 'Statut de la commande :',
    'confirmation_email_hint' => 'Un e-mail de confirmation a été envoyé à :email.',
    'order_number_label' => 'Numéro de commande',
    'totals_title' => 'Montants',
    'items_label' => 'Articles',
    'discount' => 'Remise',
    'shipping' => 'Livraison',
    'tax' => 'TVA',
    'sku' => 'Réf.',
    'continue_shopping' => 'Continuer mes achats',

    'status' => [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'processing' => 'En traitement',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
        'on_hold' => 'En attente de traitement',
    ],

    'validation' => [
        'first_name_required' => 'Le prénom est obligatoire.',
        'last_name_required' => 'Le nom est obligatoire.',
        'phone_required' => 'Le téléphone est obligatoire.',
        'email_required' => 'L\'adresse e-mail est obligatoire.',
        'email_invalid' => 'L\'adresse e-mail est invalide.',
        'address_required' => 'L\'adresse est obligatoire.',
        'password_required' => 'Le mot de passe est obligatoire pour créer un compte.',
        'password_confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'password_min' => 'Le mot de passe doit contenir au moins :min caractères.',
    ],

    'errors' => [
        'cart_empty' => 'Votre panier est vide. Ajoutez des articles avant de passer commande.',
        'login_required' => 'Veuillez vous connecter pour passer commande.',
        'product_unavailable' => 'L\'un des produits de votre panier n\'est plus disponible.',
        'variant_unavailable' => 'L\'une des variantes de votre panier n\'est plus disponible.',
        'stock_changed' => 'Le stock de « :name » a changé. Seulement :available unité(s) disponible(s).',
    ],

    'stock_reason' => 'Commande :order',

    'notification' => [
        'new_order' => 'Une nouvelle commande a été créée.',
    ],

    'email' => [
        'subject' => 'Confirmation de commande :order',
        'heading' => 'Commande :order',
        'body' => 'Merci ! Votre commande a bien été enregistrée. Voici le récapitulatif :',
        'column_product' => 'Produit',
        'column_qty' => 'Qté',
        'column_price' => 'Prix',
        'discount' => 'Remise',
        'shipping' => 'Livraison',
        'total' => 'Total',
        'footer' => 'Nous traitons votre commande dans les plus brefs délais.',
    ],
];
