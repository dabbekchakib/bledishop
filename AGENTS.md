# AGENTS.md

# E-Commerce Laravel Multilingue — Instructions de développement

## 1. Objectif du projet

Développer une plateforme e-commerce moderne, sécurisée, multilingue et entièrement paramétrable avec Laravel.

Le système doit permettre à un administrateur de gérer entièrement la boutique depuis un back-office Filament sans modifier le code.

La boutique doit supporter :

- Français
- العربية
- English

Le site doit fonctionner correctement en RTL pour l'arabe.

Le système ne doit PAS intégrer de paiement en ligne.

Le client peut commander sans créer de compte.

La création d'un compte client est optionnelle.

---

# 2. Stack technique obligatoire

Utiliser exclusivement, sauf nécessité justifiée :

- PHP 8.4+
- Laravel 13
- MySQL 8+
- Filament
- Livewire
- Tailwind CSS
- Vite
- Laravel Breeze pour l'authentification
- Composer
- NPM

Ne pas introduire un framework frontend supplémentaire comme :

- React
- Vue
- Angular
- Next.js
- Nuxt

sauf demande explicite.

L'application doit rester principalement basée sur :

Laravel + Blade + Livewire + Filament + Tailwind CSS.

---

# 3. Principes généraux

Le code doit être :

- propre
- maintenable
- sécurisé
- modulaire
- extensible
- documenté lorsque nécessaire
- conforme aux conventions Laravel
- compatible avec les futures évolutions

Toujours privilégier les fonctionnalités natives de Laravel avant d'ajouter une dépendance externe.

Ne jamais ajouter une dépendance Composer ou NPM sans raison technique valable.

Avant d'ajouter un package :

1. vérifier si Laravel fournit déjà la fonctionnalité ;
2. vérifier si Filament ou Livewire fournit déjà la fonctionnalité ;
3. vérifier la compatibilité avec Laravel 13 ;
4. vérifier la maintenance du package ;
5. éviter les packages abandonnés.

---

# 4. Architecture générale

Le projet doit être organisé selon une architecture Laravel standard.

Structure principale :

app/
├── Actions/
├── Console/
├── Enums/
├── Filament/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Livewire/
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Services/
└── Support/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
├── lang/
└── views/
    ├── components/
    ├── layouts/
    ├── livewire/
    └── shop/

routes/
├── web.php
└── console.php

---

# 5. Back-office

Le back-office doit être développé avec Filament.

Utiliser les fonctionnalités natives de Filament pour :

- Resources
- Pages
- Forms
- Tables
- Filters
- Actions
- Widgets
- Notifications
- Relation managers
- Navigation
- Permissions

Ne pas recréer manuellement les fonctionnalités déjà disponibles dans Filament.

L'interface d'administration doit respecter le design natif de Filament.

Ne pas créer une interface admin parallèle avec Bootstrap ou un autre framework CSS.

---

# 6. Navigation administrateur

Prévoir une navigation logique.

## Dashboard

- Dashboard

## Catalogue

- Produits
- Catégories
- Marques
- Attributs
- Variantes
- Stocks

## Commandes

- Commandes
- Clients
- Adresses clients

## Marketing

- Promotions
- Coupons
- Bannières
- Produits vedettes

## Contenu

- Pages
- Menus
- Traductions

## Configuration

- Configuration générale
- Boutique
- Devise
- Livraison
- TVA
- Langues
- SEO
- Réseaux sociaux

## Administration

- Utilisateurs
- Rôles
- Permissions

---

# 7. Système de configuration

La plateforme doit être entièrement paramétrable depuis l'administration.

Ne pas coder en dur les paramètres qui doivent pouvoir être modifiés par l'administrateur.

Créer un système de settings centralisé.

Table recommandée :

settings

Champs :

- id
- key
- value
- type
- group
- is_public
- created_at
- updated_at

Exemples :

site.name
site.description
site.logo
site.favicon
site.email
site.phone
site.address

shop.currency
shop.currency_symbol
shop.currency_position
shop.tax_enabled
shop.tax_rate

shipping.enabled
shipping.default_cost
shipping.free_shipping_enabled
shipping.free_shipping_threshold

localization.default_locale
localization.available_locales
localization.timezone

seo.title
seo.description
seo.keywords

social.facebook
social.instagram
social.linkedin
social.youtube

---

# 8. Helper de configuration

Créer un accès simple aux paramètres.

Exemple :

setting('site.name')

setting('shop.currency')

setting('shop.tax_rate')

setting('shipping.enabled')

Le système doit gérer correctement :

- string
- integer
- float
- boolean
- array
- JSON

Éviter les requêtes SQL répétitives pour les settings.

Mettre en place un cache approprié pour les paramètres.

Lorsqu'un paramètre est modifié dans Filament, le cache correspondant doit être invalidé.

---

# 9. Multilingue

Les langues obligatoires sont :

- fr
- ar
- en

Le français est la langue par défaut sauf configuration contraire.

L'administrateur doit pouvoir activer ou désactiver les langues depuis Configuration > Langues.

Le système doit gérer :

- langue courante
- langue par défaut
- langues disponibles
- fallback language

La langue arabe doit automatiquement utiliser :

direction: rtl

Les langues française et anglaise utilisent :

direction: ltr

---

# 10. Traduction des données

Les données métier multilingues doivent utiliser des tables de traduction.

Ne pas créer :

name_fr
name_ar
name_en

Préférer :

products
product_translations

Exemple :

products

- id
- sku
- price
- compare_price
- stock
- status
- featured
- created_at
- updated_at

product_translations

- id
- product_id
- locale
- name
- slug
- short_description
- description
- seo_title
- seo_description

Ajouter une contrainte unique :

product_id + locale

---

# 11. Entités multilingues

Le système de traduction doit pouvoir être utilisé pour :

- Produits
- Catégories
- Marques
- Pages CMS
- éventuellement menus
- éventuellement bannières

Les traductions doivent être facilement accessibles depuis Laravel.

Exemple :

$product->translation('fr')
$product->translation('ar')
$product->translation('en')

Prévoir un fallback automatique vers la langue par défaut lorsqu'une traduction manque.

---

# 12. Produits

Chaque produit doit pouvoir gérer :

- référence SKU
- nom
- slug
- description courte
- description complète
- images
- galerie
- prix
- ancien prix
- prix promotionnel
- stock
- stock minimum
- gestion du stock
- statut
- produit vedette
- catégorie
- sous-catégorie
- marque
- poids
- dimensions
- attributs
- variantes
- SEO

Statuts :

- draft
- active
- inactive

Utiliser des Enums PHP lorsque cela est pertinent.

---

# 13. Catégories

Les catégories doivent supporter :

- catégorie parent
- sous-catégories
- nom multilingue
- slug multilingue
- description
- image
- statut
- ordre
- SEO

Prévoir une relation parent/enfants.

---

# 14. Marques

Une marque peut avoir :

- nom
- slug
- logo
- description
- statut
- SEO

Le nom et la description doivent supporter les traductions.

---

# 15. Attributs et variantes

Prévoir un système permettant de créer :

Attributs :

- Couleur
- Taille
- Matière
- etc.

Valeurs :

- Rouge
- Bleu
- Noir
- S
- M
- L

Un produit peut avoir plusieurs variantes.

Chaque variante peut avoir :

- SKU
- prix
- prix promotionnel
- stock
- image
- poids
- statut

---

# 16. Gestion des images

Les images doivent être stockées avec le système de filesystem Laravel.

Ne jamais stocker directement des images binaires dans MySQL.

Prévoir :

- image principale
- galerie
- images de variantes

Utiliser Storage Laravel.

Valider :

- type MIME
- taille maximale
- dimensions lorsque nécessaire

Optimiser les images lorsque cela est possible.

---

# 17. Panier

Le panier doit fonctionner sans authentification.

Un visiteur doit pouvoir :

- ajouter un produit
- modifier la quantité
- supprimer un produit
- vider le panier
- consulter le panier

Le panier doit être associé à la session pour les visiteurs.

Le panier doit gérer :

- produit
- variante
- quantité
- prix
- sous-total

Ne jamais faire confiance au prix envoyé par le navigateur.

Lors de la validation de commande, toujours récupérer le prix depuis la base de données.

---

# 18. Checkout

Le checkout ne nécessite PAS de compte.

Le client doit pouvoir commander en tant qu'invité.

Informations obligatoires :

- prénom
- nom
- téléphone
- adresse
- ville
- pays

Email :

- recommandé
- configurable comme obligatoire ou facultatif

Code postal :

- configurable

Note client :

- facultative

---

# 19. Création de compte optionnelle

Lors du checkout, proposer :

"Créer un compte"

Si le client ne souhaite pas créer de compte :

- la commande doit quand même être validée ;
- aucune authentification ne doit être obligatoire.

Si le client souhaite créer un compte :

- créer son utilisateur ;
- associer la commande à l'utilisateur ;
- conserver les informations du checkout ;
- permettre ensuite la consultation de ses commandes.

Ne jamais bloquer une commande invitée à cause de l'absence de compte.

---

# 20. Commandes

Créer une table orders.

Informations recommandées :

- id
- order_number
- user_id nullable
- customer_first_name
- customer_last_name
- customer_email
- customer_phone
- shipping_address
- shipping_city
- shipping_postal_code
- shipping_country
- customer_note
- subtotal
- shipping_cost
- tax_amount
- discount_amount
- total
- currency
- status
- created_at
- updated_at

Les informations client doivent être conservées dans la commande afin de préserver l'historique même si le profil client est modifié ultérieurement.

---

# 21. Numéro de commande

Générer un numéro de commande unique.

Exemple :

ORD-2026-000001

Le format doit être centralisé et facilement modifiable.

Ne jamais utiliser uniquement l'ID numérique comme numéro visible au client.

---

# 22. Lignes de commande

Créer :

order_items

Champs :

- id
- order_id
- product_id nullable
- variant_id nullable
- product_name
- sku
- quantity
- unit_price
- subtotal

Conserver le nom et le prix au moment de la commande.

Ne jamais dépendre uniquement des données actuelles du produit pour afficher une ancienne commande.

---

# 23. Paiement

IMPORTANT :

Le projet ne doit PAS intégrer de paiement en ligne.

Ne pas intégrer :

- Stripe
- PayPal
- Paymee
- Flouci
- cartes bancaires
- paiement en ligne

La commande est simplement enregistrée.

Prévoir éventuellement un champ :

payment_method

avec par exemple :

- cash_on_delivery
- manual
- unspecified

Le système peut être préparé pour intégrer ultérieurement un paiement, mais aucune passerelle de paiement ne doit être développée maintenant.

---

# 24. Livraison

Prévoir une configuration de livraison.

Paramètres :

- livraison activée
- coût standard
- livraison gratuite
- seuil de livraison gratuite
- pays disponibles
- zones de livraison

Le coût doit être calculé côté serveur.

Ne jamais accepter un montant de livraison envoyé directement par le frontend.

---

# 25. TVA

La TVA doit être configurable.

Paramètres :

- TVA activée
- taux TVA
- affichage TTC/HT

Le calcul doit être effectué côté serveur.

Ne jamais faire confiance aux montants calculés par JavaScript.

Utiliser des calculs précis pour les montants financiers.

Éviter les erreurs de floating point.

---

# 26. Devise

La devise doit être configurable.

Prévoir :

- code devise
- symbole
- position du symbole
- nombre de décimales

Exemples :

TND
EUR
USD

Exemple d'affichage configurable :

10,000 TND

ou :

€10.00

Tous les prix doivent être stockés de manière cohérente en base de données.

---

# 27. Clients

Un client peut exister sans compte.

Il faut distinguer :

- visiteur
- client invité
- client enregistré

Les commandes invitées doivent rester consultables depuis l'administration.

Prévoir éventuellement un modèle Customer séparé si l'architecture l'exige.

Ne pas créer automatiquement un compte utilisateur pour chaque commande invitée.

---

# 28. Authentification

Utiliser Laravel Breeze pour l'authentification client.

Prévoir :

- connexion
- inscription
- déconnexion
- mot de passe oublié
- réinitialisation du mot de passe

L'inscription est optionnelle.

L'administration utilise Filament Authentication.

---

# 29. Espace client

Prévoir un espace client pour les utilisateurs enregistrés.

Fonctionnalités :

- Dashboard
- Profil
- Modifier informations personnelles
- Modifier mot de passe
- Mes commandes
- Détails d'une commande
- Mes adresses

Les commandes invitées ne nécessitent pas d'espace client.

---

# 30. Statuts de commande

Utiliser un Enum.

Statuts initiaux :

- pending
- confirmed
- processing
- shipped
- delivered
- cancelled

Prévoir la possibilité d'ajouter des statuts personnalisés ultérieurement.

L'administrateur doit pouvoir modifier le statut depuis Filament.

Les changements importants de statut doivent pouvoir être historisés.

---

# 31. Historique des commandes

Prévoir éventuellement une table :

order_status_histories

avec :

- order_id
- old_status
- new_status
- changed_by
- note
- created_at

Cela permet de connaître l'historique d'une commande.

---

# 32. Coupons

Prévoir un système de coupons administrables.

Un coupon peut avoir :

- code
- type
- valeur
- minimum amount
- maximum discount
- usage limit
- usage per customer
- starts_at
- expires_at
- active

Types :

- fixed
- percentage

Le calcul doit être effectué côté serveur.

---

# 33. Promotions

Prévoir un système de promotion.

Une promotion peut s'appliquer :

- à un produit
- à une catégorie
- à plusieurs produits

Prévoir :

- prix promotionnel
- période
- statut

Éviter de modifier définitivement le prix normal du produit pour appliquer une promotion.

---

# 34. Recherche

Prévoir une recherche globale dans la boutique.

Recherche sur :

- nom produit
- SKU
- description
- catégorie
- marque

La recherche doit respecter la langue courante lorsque possible.

---

# 35. SEO

Chaque produit, catégorie et page doit pouvoir définir :

- SEO title
- SEO description
- slug

Prévoir également :

- meta robots
- canonical URL
- Open Graph
- sitemap
- robots.txt

Les URLs doivent être propres.

---

# 36. Pages CMS

Prévoir un système de pages administrables :

- À propos
- Conditions générales
- Politique de confidentialité
- Livraison
- Retour
- Contact
- autres pages

Les pages doivent être multilingues.

Chaque page doit avoir :

- title
- slug
- content
- status
- SEO title
- SEO description

---

# 37. Menus

Les menus frontend doivent être configurables depuis l'administration.

Prévoir :

- menu principal
- footer menu
- liens personnalisés

Possibilité de :

- ajouter
- modifier
- supprimer
- réordonner

Les menus doivent supporter les traductions lorsque nécessaire.

---

# 38. Homepage

La page d'accueil doit être dynamique.

Prévoir des sections telles que :

- Hero
- catégories principales
- produits vedettes
- nouveautés
- promotions
- marques
- texte marketing
- newsletter éventuellement

Les sections doivent pouvoir être activées/désactivées depuis l'administration lorsque possible.

---

# 39. Newsletter

Prévoir éventuellement un système simple d'abonnement newsletter.

Table :

newsletter_subscribers

Champs :

- email
- status
- subscribed_at
- unsubscribed_at

Ne pas intégrer automatiquement un fournisseur externe.

---

# 40. Notifications

Prévoir des notifications administrateur et client.

Exemples :

Lorsqu'une commande est créée :

- notification admin

Lorsqu'une commande est confirmée :

- notification client

Lorsqu'une commande est expédiée :

- notification client

Lorsqu'une commande est livrée :

- notification client

Le système doit être conçu pour pouvoir utiliser :

- database notifications
- email notifications

---

# 41. Emails

Prévoir des templates d'emails configurables lorsque pertinent.

Emails possibles :

- confirmation commande
- commande confirmée
- commande expédiée
- commande livrée
- création de compte
- réinitialisation mot de passe

Les emails doivent respecter la langue du client lorsque cette information est disponible.

---

# 42. Sécurité

Respecter les bonnes pratiques Laravel.

Obligatoire :

- validation avec Form Requests
- CSRF
- authorization
- policies
- mass assignment protection
- rate limiting lorsque nécessaire
- validation des uploads
- sanitation du contenu HTML
- protection contre XSS
- protection contre SQL injection
- protection contre IDOR
- authentification sécurisée

Ne jamais faire confiance aux données provenant du frontend.

---

# 43. Autorisations

Utiliser un système de rôles et permissions.

Rôles initiaux :

- super-admin
- admin
- manager
- staff
- customer

Les permissions doivent être granulaires.

Exemples :

products.view
products.create
products.update
products.delete

orders.view
orders.update
orders.delete

settings.view
settings.update

users.view
users.create
users.update
users.delete

---

# 44. Policies

Créer des Policies Laravel pour les ressources sensibles.

Exemples :

- ProductPolicy
- OrderPolicy
- UserPolicy
- SettingPolicy
- CouponPolicy

Ne pas vérifier les permissions uniquement dans les composants frontend.

Les autorisations doivent également être appliquées côté serveur.

---

# 45. Validation

Toujours utiliser des règles de validation explicites.

Pour le checkout :

- first_name : required|string|max
- last_name : required|string|max
- phone : required|string|max
- email : email
- address : required|string
- city : required|string
- country : required|string

Adapter les règles selon la configuration de la boutique.

---

# 46. Calcul des totaux

Le total d'une commande doit être calculé côté serveur.

Ordre recommandé :

1. prix produits
2. quantité
3. sous-total
4. remise
5. frais de livraison
6. TVA
7. total final

Ne jamais accepter :

subtotal
tax
shipping
total

directement depuis le frontend.

Ces valeurs doivent être recalculées.

---

# 47. Transactions

La création d'une commande doit utiliser une transaction database.

Exemple logique :

DB::transaction(function () {

    // créer commande
    // créer lignes
    // mettre à jour stock
    // appliquer coupon
});

Si une étape échoue, toute la commande doit être rollback.

---

# 48. Gestion du stock

Prévoir :

- stock activé/désactivé par produit
- quantité disponible
- stock minimum
- réservation éventuelle
- décrémentation lors de la validation de commande

Éviter les stocks négatifs.

Le stock doit être contrôlé dans une transaction pour éviter les problèmes de concurrence.

---

# 49. Architecture métier

Ne pas mettre toute la logique métier dans :

- Controllers
- Livewire Components
- Filament Resources

Créer des Services ou Actions lorsque la logique devient complexe.

Exemples :

OrderService
CartService
PricingService
ShippingService
TaxService
CouponService
ProductService
SettingsService

Exemples d'Actions :

CreateOrder
CalculateCart
ApplyCoupon
UpdateStock
CreateCustomerAccount

---

# 50. Eloquent

Utiliser correctement :

- relationships
- scopes
- casts
- accessors/mutators lorsque nécessaires
- model events lorsque justifiés

Éviter les requêtes N+1.

Utiliser :

with()
load()
withCount()

lorsque nécessaire.

---

# 51. Base de données

Toutes les modifications de structure doivent passer par des migrations.

Ne jamais modifier manuellement la structure de production sans migration.

Prévoir les indexes nécessaires.

Ajouter des contraintes :

- foreign keys
- unique
- nullable
- indexes

lorsqu'elles sont pertinentes.

---

# 52. Seeders

Créer des seeders pour permettre une installation rapide du projet.

Prévoir au minimum :

- langues
- rôles
- permissions
- administrateur
- paramètres par défaut
- catégories de démonstration
- produits de démonstration

Ne jamais placer de véritables mots de passe dans le repository.

Utiliser les variables d'environnement ou demander un mot de passe initial.

---

# 53. Factories

Créer des factories pour les principales entités :

- User
- Product
- Category
- Brand
- Order
- OrderItem
- Coupon

Les factories doivent permettre de générer facilement des données de test.

---

# 54. Tests

Créer des tests PHPUnit/Pest selon la configuration du projet.

Tester au minimum :

- ajout panier
- modification panier
- suppression panier
- checkout invité
- checkout utilisateur
- création commande
- calcul total
- calcul TVA
- calcul livraison
- coupon
- stock
- création compte optionnelle
- multilingue
- permissions
- accès administration

Les tests doivent être exécutables automatiquement.

---

# 55. Frontend

Le frontend doit être moderne, responsive et professionnel.

Utiliser :

- Blade
- Livewire
- Tailwind CSS

Ne pas utiliser Bootstrap.

Le frontend doit être complètement séparé visuellement du back-office Filament.

Filament est utilisé pour l'administration uniquement.

---

# 56. Responsive Design

Le site doit être responsive :

- mobile
- tablette
- desktop

Priorité mobile-first.

Tester les interfaces sur différentes tailles d'écran.

---

# 57. RTL

Lorsque la langue arabe est sélectionnée :

- html dir="rtl"
- textes alignés correctement
- navigation adaptée
- icônes correctement positionnées
- marges/paddings adaptés
- formulaires adaptés
- panier adapté
- checkout adapté

Ne jamais créer une version arabe comme simple traduction du texte sans adapter le layout RTL.

---

# 58. Accessibilité

Respecter autant que possible :

- HTML sémantique
- labels de formulaires
- aria-label lorsque nécessaire
- navigation clavier
- contraste
- focus states
- boutons accessibles

---

# 59. Performance

Optimiser :

- eager loading
- cache
- images
- pagination
- requêtes SQL
- assets Vite
- lazy loading

Éviter les requêtes inutiles dans les boucles Blade.

---

# 60. Cache

Mettre en cache lorsque pertinent :

- settings
- catégories
- menus
- configuration publique
- certaines données rarement modifiées

Toujours invalider le cache lorsqu'une donnée correspondante est modifiée.

---

# 61. URLs

Les URLs doivent être propres.

Prévoir une structure comme :

/fr
/fr/products
/fr/category/{slug}
/fr/product/{slug}
/fr/cart
/fr/checkout

/ar
/ar/products
/ar/category/{slug}
/ar/product/{slug}

/en
/en/products
/en/category/{slug}
/en/product/{slug}

Le système doit éviter les URLs dupliquées lorsque possible.

---

# 62. Locale Middleware

Créer un middleware pour gérer la langue courante.

Le middleware doit :

1. lire la locale dans l'URL ;
2. vérifier qu'elle est activée ;
3. définir App::setLocale() ;
4. définir la direction RTL/LTR ;
5. utiliser la langue par défaut si nécessaire.

Ne jamais accepter arbitrairement une locale inconnue.

---

# 63. Configuration publique

Les settings nécessaires au frontend peuvent être exposés via un service dédié.

Ne jamais exposer au frontend :

- secrets
- clés API privées
- mots de passe
- credentials
- variables sensibles

---

# 64. Logs

Utiliser Laravel Log pour les événements importants.

Ne jamais enregistrer dans les logs :

- mots de passe
- tokens
- données bancaires
- informations sensibles inutiles

---

# 65. Gestion des erreurs

Prévoir des pages :

- 404
- 403
- 419
- 429
- 500

Les erreurs frontend doivent être compréhensibles pour l'utilisateur.

Les erreurs techniques détaillées ne doivent pas être exposées en production.

---

# 66. Commandes artisan

Créer éventuellement des commandes utiles :

php artisan shop:install
php artisan shop:seed
php artisan shop:clear-settings-cache

Les commandes doivent être documentées.

---

# 67. .env

Utiliser `.env` uniquement pour :

- configuration environnement
- database
- mail
- filesystem
- cache
- queue
- secrets
- credentials techniques

Les paramètres commerciaux modifiables par l'administrateur doivent être stockés dans `settings`.

---

# 68. Git

Ne jamais versionner :

.env

vendor/

node_modules/

storage/logs/

fichiers uploadés privés

Ne jamais supprimer ou modifier l'historique Git sans demande explicite.

---

# 69. Code style

Respecter PSR-12 et les conventions Laravel.

Utiliser :

- noms de classes en PascalCase
- méthodes en camelCase
- variables en camelCase
- tables en snake_case
- colonnes en snake_case

Exemple :

Product
Order
OrderItem

et non :

productModel
orderModel

---

# 70. Commentaires

Ne pas sur-commenter le code.

Les commentaires doivent expliquer :

- pourquoi une logique existe
- une règle métier complexe
- une décision architecturale

Ne pas commenter les choses évidentes.

---

# 71. Filament Resources

Pour chaque ressource Filament :

- form()
- table()
- filters()
- actions()
- relations()

doivent rester propres et lisibles.

Lorsque la logique devient importante, la déplacer vers :

- Actions
- Services
- Models
- Policies

Ne pas transformer une Resource Filament en énorme fichier contenant toute la logique métier.

---

# 72. Dashboard administrateur

Le dashboard doit afficher au minimum :

- nombre de commandes
- commandes récentes
- chiffre d'affaires
- produits
- produits en rupture
- clients
- commandes par statut

Prévoir des filtres de période :

- aujourd'hui
- cette semaine
- ce mois
- cette année
- période personnalisée

---

# 73. Gestion du vendeur

Prévoir dans Configuration :

- nom vendeur
- raison sociale
- adresse
- téléphone
- email
- matricule fiscal
- registre de commerce
- logo
- informations légales

Ces informations pourront être utilisées dans :

- emails
- pages légales
- documents
- footer

---

# 74. Configuration des fonctionnalités

Prévoir des toggles pour activer/désactiver :

- inscription
- checkout invité
- wishlist
- coupons
- promotions
- stock
- livraison
- TVA
- newsletter
- avis produits
- recherche
- marques
- variantes

Les fonctionnalités désactivées ne doivent pas apparaître inutilement dans le frontend.

---

# 75. Avis produits

Prévoir éventuellement un système d'avis.

Un avis peut avoir :

- produit
- utilisateur
- note
- commentaire
- statut
- date

Statuts :

- pending
- approved
- rejected

Les avis ne doivent être affichés publiquement qu'après validation si la modération est activée.

---

# 76. Wishlist

Prévoir éventuellement une wishlist.

Pour les utilisateurs connectés :

- ajouter produit
- supprimer produit
- consulter wishlist

Pour les visiteurs :

- éventuellement stockage en session/local storage

Cette fonctionnalité doit pouvoir être désactivée.

---

# 77. Architecture extensible

Même si certaines fonctionnalités sont optionnelles, l'architecture doit permettre leur ajout ultérieur.

Ne pas construire une architecture qui bloque :

- paiement en ligne
- livraison avancée
- marketplace
- multi-vendeur
- ERP
- API mobile
- application mobile

Cependant, ne pas développer ces fonctionnalités maintenant sans demande explicite.

---

# 78. API

Ne pas créer une API complète inutilement au début.

L'architecture doit cependant être compatible avec une future API REST.

Si une API est créée ultérieurement :

- utiliser Laravel Sanctum
- utiliser API Resources
- versionner l'API

Exemple :

/api/v1/products

---

# 79. Administration et frontend

Ne jamais mélanger :

Filament admin

et

frontend boutique.

Le back-office peut utiliser :

/admin

Le frontend doit utiliser les routes publiques multilingues.

---

# 80. Internationalisation des messages

Tous les messages visibles par l'utilisateur doivent être traduisibles.

Ne pas écrire directement :

"Produit ajouté au panier"

dans le code.

Utiliser les fichiers de traduction Laravel.

Exemple :

__('shop.product_added')

Prévoir les traductions :

resources/lang/fr/
resources/lang/ar/
resources/lang/en/

---

# 81. Dates et heures

Utiliser le timezone configuré.

Ne jamais supposer que le timezone est UTC dans l'interface utilisateur.

Stocker les dates correctement et les présenter selon la configuration.

---

# 82. Prix

Les prix doivent être représentés avec une précision suffisante.

Ne jamais utiliser de calcul financier approximatif côté JavaScript comme source de vérité.

Le serveur est toujours la source de vérité.

---

# 83. Workflow de développement

Pour chaque fonctionnalité :

1. analyser les besoins ;
2. vérifier l'architecture existante ;
3. vérifier les modèles existants ;
4. vérifier les migrations ;
5. créer ou modifier les migrations ;
6. créer/modifier les Models ;
7. créer les Enums si nécessaire ;
8. créer les Services/Actions ;
9. créer les Policies ;
10. créer les Filament Resources ;
11. créer les composants Livewire ;
12. créer les vues Blade ;
13. ajouter les traductions ;
14. ajouter les tests ;
15. exécuter les tests ;
16. corriger les erreurs ;
17. vérifier le responsive ;
18. vérifier FR/AR/EN.

Ne jamais développer uniquement l'interface sans créer la logique backend correspondante.

---

# 84. Ordre recommandé de développement

Développer le projet dans cet ordre :

## Phase 1

- installation Laravel
- configuration MySQL
- authentification
- Filament
- rôles
- permissions

## Phase 2

- système settings
- langues
- configuration boutique
- devise
- TVA
- livraison

## Phase 3

- catégories
- marques
- produits
- traductions
- attributs
- variantes
- images
- stock

## Phase 4

- frontend
- layout
- header
- footer
- homepage
- catalogue
- recherche
- filtres
- page produit

## Phase 5

- panier
- checkout
- commande invité
- création compte optionnelle
- calcul prix
- livraison
- TVA
- coupons

## Phase 6

- administration commandes
- clients
- statuts
- historique
- notifications

## Phase 7

- espace client
- commandes client
- profil
- adresses

## Phase 8

- pages CMS
- menus
- SEO
- marketing

## Phase 9

- tests
- optimisation
- sécurité
- responsive
- RTL
- performance

---

# 85. Règle importante pour l'agent IA

Avant de créer une fonctionnalité, vérifier si elle existe déjà dans le projet.

Ne pas créer de doublon.

Toujours rechercher :

- Models
- migrations
- Services
- Actions
- Components
- Resources
- routes
- policies
- translations

avant de créer un nouveau fichier.

---

# 86. Règle de non-régression

Lorsqu'une fonctionnalité est ajoutée :

- ne pas casser les fonctionnalités existantes ;
- ne pas supprimer une fonctionnalité existante sans autorisation ;
- ne pas modifier une migration déjà exécutée en production ;
- créer une nouvelle migration pour modifier la base de données ;
- conserver la compatibilité avec les données existantes.

---

# 87. Règle concernant les packages

Avant d'installer un package externe :

1. expliquer brièvement pourquoi il est nécessaire ;
2. vérifier sa compatibilité ;
3. éviter les packages redondants ;
4. privilégier Laravel/Filament natif.

Ne pas installer automatiquement plusieurs packages pour résoudre un même problème.

---

# 88. Règle concernant les données

Ne jamais supprimer définitivement les données importantes sans confirmation.

Pour les produits et commandes, privilégier :

- soft deletes lorsque pertinent
- archivage
- statuts actifs/inactifs

Les commandes doivent rester historiquement cohérentes.

---

# 89. Règle concernant les commandes

Une commande validée doit conserver son état historique.

Si le prix d'un produit change après une commande :

la commande existante ne doit PAS changer.

Si le nom du produit change :

le nom historique de la ligne de commande doit rester celui enregistré au moment de la commande.

---

# 90. Règle concernant le stock

Une commande annulée peut éventuellement restituer le stock selon le statut et la logique métier.

Éviter de décrémenter deux fois ou de restituer deux fois le stock.

Le changement de stock doit être idempotent lorsque possible.

---

# 91. Règle concernant le checkout

Le checkout doit être extrêmement simple.

Objectif :

Panier
→ Informations client
→ Récapitulatif
→ Confirmation

Pas de page de paiement.

Pas de création de compte obligatoire.

Pas de processus inutile.

---

# 92. Règle concernant le design

Frontend :

- moderne
- professionnel
- minimal
- responsive
- accessible

Admin :

- Filament natif
- cohérent avec Filament
- ne pas réinventer le design

Le frontend ne doit pas ressembler au back-office.

---

# 93. Règle concernant l'arabe

Toujours tester l'application en arabe.

Vérifier :

- direction
- menus
- formulaires
- tableaux
- boutons
- icônes
- marges
- pagination
- panier
- checkout

L'arabe n'est pas seulement une traduction linguistique : le layout doit également être adapté au RTL.

---

# 94. Règle concernant les traductions

Ne jamais supprimer une traduction existante lors de l'ajout d'une nouvelle langue.

Si une traduction manque :

utiliser le fallback configuré.

Ne jamais afficher une clé technique comme :

product.name

à l'utilisateur final.

---

# 95. Règle concernant les secrets

Ne jamais écrire dans le code :

- mot de passe
- API key
- secret key
- token
- credentials

Utiliser `.env`.

Ne jamais afficher les secrets dans les réponses ou logs.

---

# 96. Vérifications avant livraison

Avant de considérer une fonctionnalité comme terminée :

- composer test
- migrations propres
- seeders fonctionnels
- routes fonctionnelles
- validation backend
- permissions
- responsive
- FR
- AR
- EN
- RTL
- erreurs 404/403
- sécurité
- absence de requêtes N+1 évidentes
- absence de données hardcodées inutilement

---

# 97. Definition of Done

Une fonctionnalité est considérée comme terminée uniquement si :

1. Backend terminé
2. Base de données terminée
3. Validation terminée
4. Authorization terminée
5. Filament terminé si nécessaire
6. Frontend terminé
7. Traductions FR/AR/EN terminées
8. RTL vérifié
9. Responsive vérifié
10. Tests ajoutés lorsque pertinent
11. Tests exécutés
12. Aucun bug connu bloquant
13. Aucun secret dans le code
14. Aucun doublon architectural
15. Code conforme aux conventions Laravel

---

# 98. Priorité absolue

En cas de conflit entre rapidité de développement et qualité :

privilégier :

1. sécurité
2. intégrité des commandes
3. intégrité des prix
4. intégrité du stock
5. maintenabilité
6. expérience utilisateur
7. performance
8. rapidité de développement

---

# 99. Résumé fonctionnel

Le produit final est une boutique e-commerce Laravel multilingue :

LANGUES :
FR / AR / EN

AUTHENTIFICATION :
Compte optionnel

CHECKOUT :
Commande invité autorisée

PAIEMENT :
Aucun paiement en ligne

ADMIN :
Filament

FRONTEND :
Blade + Livewire + Tailwind

DATABASE :
MySQL

CONFIGURATION :
100 % administrable

CATALOGUE :
Produits + catégories + marques + variantes + stock

COMMANDES :
Gestion complète

CLIENTS :
Invités + comptes

SEO :
Multilingue

RTL :
Arabe natif

ARCHITECTURE :
Laravel propre, modulaire et extensible

---

# 100. Instruction finale pour l'agent

Ne pas générer toute l'application en une seule étape.

Développer progressivement par modules.

Avant chaque étape :

- inspecter le code existant ;
- comprendre les dépendances ;
- respecter AGENTS.md ;
- ne pas recréer ce qui existe ;
- implémenter uniquement la fonctionnalité demandée ;
- tester la fonctionnalité ;
- corriger les erreurs ;
- conserver la compatibilité avec les fonctionnalités précédentes.

Ne jamais considérer une fonctionnalité comme terminée uniquement parce que l'interface graphique existe.

Une fonctionnalité doit être fonctionnelle de bout en bout :

Database
→ Model
→ Business Logic
→ Authorization
→ Filament
→ Livewire
→ Blade
→ Translation
→ Tests

Toujours privilégier une implémentation simple, robuste et maintenable.