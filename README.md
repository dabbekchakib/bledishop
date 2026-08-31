<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-red?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.4-777bb4?logo=php&logoColor=white" alt="PHP 8.4+">
  <img src="https://img.shields.io/badge/MySQL-8-4479a1?logo=mysql&logoColor=white" alt="MySQL 8+">
  <img src="https://img.shields.io/badge/Filament-5.7-orange?logo=filament&logoColor=white" alt="Filament 5.7">
  <img src="https://img.shields.io/badge/Tailwind-CSS-06b6d4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Livewire-3-4b56ed?logo=livewire&logoColor=white" alt="Livewire 3">
  <img src="https://img.shields.io/badge/license-MIT-brightgreen" alt="MIT License">
</p>

<h1 align="center">🛍️ BlediShop — Plateforme E-Commerce Multilingue</h1>

<p align="center">
  <strong>Une boutique en ligne moderne, sécurisée et 100 % paramétrable, développée avec Laravel 13, Filament, Livewire et Tailwind CSS.</strong>
</p>

<p align="center">
  🇫🇷 Français &nbsp;•&nbsp; 🇩🇿 العربية (RTL) &nbsp;•&nbsp; 🇬🇧 English
</p>

---

## 📖 Description du projet

**BlediShop** est une plateforme e-commerce complète, développée par **CHAKIB DABBEK**. Elle permet à un administrateur de gérer entièrement une boutique en ligne depuis un back-office **Filament**, sans toucher au code : catalogue, commandes, clients, marketing, contenu, SEO et configuration.

L'application est pensée pour être **modulaire**, **sécurisée** et **extensible**. Elle propose une expérience utilisateur moderne, responsive et accessible, avec un support natif du **multilingue (FR / AR / EN)** et du **RTL** pour l'arabe.

Elle est **exempte de paiement en ligne** : le client commande et la commande est simplement enregistrée (le module est préparé pour une future intégration). La création de compte client est **optionnelle** — le checkout en tant qu'invité est totalement supporté.

---

## ✨ Fonctionnalités principales

### 🛒 E-Commerce / Frontend
- Catalogue produits avec catégories, sous-catégories, marques et filtres
- Page produit complète : galerie d'images, attributs, variantes, prix, stock, SEO
- Recherche globale (nom, SKU, description, catégorie, marque) respectueuse de la langue
- **Panier** de session fonctionnant sans authentification (ajout, quantité, suppression, vidage)
- **Checkout invité** simple en 3 étapes : Panier → Informations client → Récapitulatif → Confirmation
- Création de compte optionnelle lors du checkout
- Récapitulatif et confirmation de commande
- **Espace client** : dashboard, profil, mot de passe, commandes, adresses
- **Homepage dynamique** : hero, catégories, produits vedettes, nouveautés, promotions, marques, bannières, barre promo
- **Compteur promotionnel (countdown)** sur les produits en promotion
- **Code promo / coupon** saisissable au panier
- Layout responsive mobile-first et **RTL natif** pour l'arabe

### 🎯 Marketing
- **Coupons** : code, type (fixe / pourcentage), valeur, montant minimum, plafond, limites d'usage, période, statut — calcul 100 % côté serveur
- **Règles de remise** (discount rules) granulaires
- **Promotions** : prix promotionnel par produit / catégorie / marque, période, flash sale, compteur à rebours, budget de quantité — sans modifier définitivement le prix normal
- **Campagnes** et **bannières** publicitaires (positions : page d'accueil, etc.)
- **Tableau de bord marketing** : statistiques et dernières remises appliquées
- Barre promotionnelle (promo bar) configurable
- Boutons/déclencheurs « marque produit vedette », notifications

### 🗂️ Administration (back-office Filament)
Navigation logique par groupes :
- **Dashboard** : commandes, chiffre d'affaires, produits, ruptures de stock, clients, commandes par statut, graphiques de revenus (par catégorie / marque), produits les plus vendus — avec filtres de période (aujourd'hui, semaine, mois, année, personnalisé)
- **Catalogue** : produits, catégories, marques, attributs, variantes, stocks
- **Commandes** : commandes, clients, adresses clients, exports, impressions
- **Marketing** : promotions, coupons, campagnes, bannières, tableau de bord marketing
- **Contenu** : pages CMS, menus
- **Configuration** : configuration générale, boutique, devise, livraison, TVA, langues, SEO, réseaux sociaux, vendeur, fonctionnalités
- **SEO** : pages, redirections (301), sitemap
- **Administration** : utilisateurs, rôles, permissions

### 🌐 Multilingue & i18n
- 3 langues : **Français** (défaut), **العربية**, **English**
- Commutateur de langue en frontend et en admin
- Arabisation avec **direction RTL** automatique
- Données métier multilingues via **tables de traduction** (`products` / `product_translations`, etc.) avec fallback automatique
- Tous les messages de l'interface traduits et centralisés dans `lang/`
- URLs propres et localisées (`/fr`, `/ar`, `/en`)

### ⚙️ Système de configuration centralisé
- Table `settings` (clé / valeur / type / groupe / whether public)
- Accès unifié via `setting('site.name')`, `setting('shop.currency')`, etc.
- Types gérés : string, integer, float, boolean, array, JSON
- **Mise en cache** des paramètres avec invalidation automatique à la modification
- Configuration du **vendeur**, de la **devise** (code, symbole, position, décimales), de la **TVA**, de la **livraison** (coût, seuil gratuit, zones), des **langues** et des **fonctionnalités** (toggles)

### 🔐 Sécurité & Architecture
- Contrôle d'accès : **rôles et permissions** (Spatie) — `super-admin`, `admin`, `manager`, `staff`, `customer`
- **Policies Laravel** appliquées côté serveur (Product, Order, User, Setting, Coupon, Promotion, etc.)
- Validation via **Form Requests**, protection **CSRF**, rate limiting, uploads validés
- Prix, TVA et livraison **toujours recalculés côté serveur** (jamais de confiance envers le frontend)
- **Intégrité des commandes** : totaux stockés en centimes, historique conservé, transaction DB pour la création de commande + gestion du stock
- Données sanitizées (purifier), protection XSS / SQL injection / IDOR
- Logs propres, sans données sensibles

### 📦 Gestion produit avancée
- SKU, nom, slug, descriptions courte/complète, images + galerie, prix, ancien prix, prix promo, stock, stock min, gestion du stock, statut, vedette
- Catégorie / sous-catégorie, marque, poids, dimensions
- **Attributs** (couleur, taille, matière…) et **valeurs**
- **Variantes** de produits (SKU, prix, prix promo, stock, image, poids, statut)
- **Mouvements de stock** tracés, décrémentation en transaction, restitution idempotente

### 📄 CMS, SEO & Notifications
- **Pages CMS** multilingues (À propos, CGV, confidentialité, livraison, contact…) avec statut et SEO
- **Menus** configurables (principal, footer, liens personnalisés, réordonnables)
- SEO par entité : SEO title, description, slug ; **sitemap.xml** et **robots.txt** ; redirections 301
- **Notifications** administrateur (base de données) et préparation des emails (confirmation, expédiée, livrée, compte…)
- **Newsletter** (abonnés avec statut)

### 🧪 Tests & Qualité
- Suite de **tests automatisés** (PHPUnit) couvrant panier, checkout invité/utilisateur, commandes, calculs (total / TVA / livraison), coupons, stock, compte optionnel, multilingue, permissions et accès admin
- Code conforme **PSR-12** et conventions Laravel
- Factories et seeders de démonstration pour installation rapide

---

## 🧰 Stack technique

| Technologie | Usage |
|-------------|-------|
| **PHP 8.4+** | Langage backend |
| **Laravel 13** | Framework principal |
| **MySQL 8+** | Base de données |
| **Filament 5.7** | Back-office administrateur |
| **Livewire 3** | Composants interactifs |
| **Tailwind CSS** | Style du frontend |
| **Alpine.js** | Interactions UI côté client |
| **Vite** | Build des assets |
| **Laravel Breeze** | Authentification |
| **Spatie Laravel Permission** | Rôles & permissions |
| **mews/purifier** | Sanitisation HTML |

---

## 🏗️ Architecture du projet

```
app/
├── Actions/            # Actions métier atomiques
├── Enums/              # Enums PHP (statuts, types, etc.)
├── Filament/           # Resources, Pages, Widgets, Concerns
├── Http/
│   ├── Controllers/    # Shop, Checkout, Cart, Account (client)
│   ├── Middleware/     # Locale, etc.
│   └── Requests/       # Form Requests de validation
├── Livewire/           # Composants Livewire
├── Models/             # Modèles Eloquent (+ traductions, traits)
├── Notifications/
├── Policies/           # Product, Order, User, Setting, Coupon…
├── Providers/
├── Services/           # Cart, Order, Pricing, Shipping, Tax, Settings, Seo…
└── Support/            # Helpers, DiscountResult
database/
├── factories/
├── migrations/
└── seeders/
resources/
├── css|js|lang/        # Traductions FR / AR / EN
└── views/
    ├── components/
    ├── layouts/
    ├── livewire/
    └── shop/           # Frontend boutique
routes/
├── web.php             # Frontend + admin
└── console.php
```

---

## 🚀 Installation

### Prérequis
- PHP 8.4+
- Composer
- Node.js & NPM
- MySQL 8+

### Étapes

```bash
# 1. Cloner le dépôt
git clone <url-du-depot> bledishop
cd bledishop

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env.example .env
#   → renseigner votre base de données MySQL dans .env

# 4. Générer la clé d'application
php artisan key:generate

# 5. Exécuter les migrations + seeders (installation complète)
php artisan migrate --seed

# 6. Installer et compiler les assets frontend
npm install
npm run build
#   (en développement : npm run dev)

# 7. Lancer le serveur
php artisan serve
```

### Commandes utiles
```bash
php artisan shop:install          # Installation du projet
php artisan shop:seed             # Données de démonstration
php artisan shop:clear-settings-cache   # Vider le cache des paramètres
```

---

## 🔑 Accès

- **Boutique (frontend)** : `http://localhost:8000` (ou URL du site)
- **Administration** : `http://localhost:8000/admin`
- **Compte administrateur** : créé via le seeder `SuperAdminSeeder` (mot de passe défini dans l'environnement, jamais en dur dans le code)

---

## 🧪 Tests

```bash
composer test
# ou
php artisan test
```

La suite couvre : panier, checkout invité et utilisateur, création de commande, calcul des totaux / TVA / livraison, coupons, gestion du stock, création de compte optionnelle, multilingue, permissions et accès administration.

---

## 📁 Fonctionnalités clés par Statut

| Module | Statut |
|--------|--------|
| Catalogue (produits, catégories, marques) | ✅ Terminé |
| Attributs & variantes | ✅ Terminé |
| Gestion du stock | ✅ Terminé |
| Panier en session | ✅ Terminé |
| Checkout invité + compte optionnel | ✅ Terminé |
| Commandes & historique de statut | ✅ Terminé |
| Clients & espace client | ✅ Terminé |
| Marketing (coupons, promotions, campagnes, bannières) | ✅ Terminé |
| Pages CMS & menus | ✅ Terminé |
| SEO (sitemap, robots, redirections) | ✅ Terminé |
| Multilingue FR / AR / EN + RTL | ✅ Terminé |
| Configuration centralisée | ✅ Terminé |
| Dashboard admin & reports | ✅ Terminé |
| Paiement en ligne | 🚫 Exclu (préparé pour futur) |
| Newsletter | 🔜 Extensible |
| Avis produits | 🔜 Extensible |
| Wishlist | 🔜 Extensible |

---

## 🤝 Auteur

**CHAKIB DABBEK** — développeur de la plateforme BlediShop.

---

## 📄 Licence

Projet sous **licence MIT**.

---

<p align="center">Développé avec ❤️ par <strong>CHAKIB DABBEK</strong> — Laravel 13 · Filament · Livewire · Tailwind CSS</p>
