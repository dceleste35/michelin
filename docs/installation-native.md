# Guide d'Installation en Local (Natifs / Sans Docker)

Ce guide décrit les étapes nécessaires pour installer et exécuter l'environnement de développement de **RideReady** directement sur votre machine locale (sans conteneurs Docker), en utilisant **Laravel Herd** (ou PHP local) et **PostgreSQL**.

---

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants :

1. **PHP 8.4** ou supérieur avec les extensions courantes (`pdo_pgsql`, `curl`, `mbstring`, `xml`, `zip`).
2. **Composer** (gestionnaire de dépendances PHP).
3. **Node.js** (v20+) & **NPM** (compilation des assets JS/CSS v4).
4. **PostgreSQL 16** avec l'extension **pgvector** installée sur votre système.
   * *Sur macOS (avec Homebrew)* : `brew install pgvector`
   * *Sur Windows* : Téléchargez la DLL `vector.dll` depuis le dépôt officiel [pgvector releases](https://github.com/pgvector/pgvector/releases) et placez-la dans le dossier `lib/` de votre installation PostgreSQL.
   * *Avec Laravel Herd (Pro)* : L'extension pgvector est activable directement en un clic dans les services.

---

## 🛠️ Étapes d'Installation

### 1. Cloner le Projet et Préparer l'Environnement
Placez-vous dans votre dossier de projets (ex. `PhpstormProjects`) et dupliquez le fichier d'environnement :

```bash
cp .env.example .env
```

### 2. Installer les Dépendances

Installez les packages PHP du framework et les bibliothèques frontend :

```bash
# Dépendances PHP (Laravel)
composer install

# Dépendances JavaScript (Tailwind v4, Livewire, etc.)
npm install
```

### 3. Générer la Clé d'Application Laravel

```bash
php artisan key:generate
```

### 4. Configurer la Base de Données

Créez une base de données vide nommée `michelin` dans votre instance PostgreSQL locale.  
Éditez ensuite votre fichier `.env` pour y insérer vos identifiants de connexion :

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=michelin
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

Ajustez également les clés API pour les services (OpenAI pour les embeddings, Anthropic pour le LLM) si vous souhaitez faire fonctionner les appels IA réels :

```ini
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
```

---

## 🗄️ Initialisation de la Démo (Seeding Déterministe)

Le projet dispose d'une commande personnalisée qui automatise la création des tables et le peuplement de l'état exact de démo pour le jury (notamment le profil de Marc à 86% d'usure) :

```bash
php artisan demo:reset
```

> [!TIP]
> Cette commande équivaut à un `php artisan migrate:fresh --seed`. Elle efface la base de données locale, réapplique les migrations (y compris l'activation de pgvector), peuple le catalogue de produits Michelin et injecte le profil de Marc avec ses 80 activités Strava simulées.

---

## 🌐 Serveur de Développement & Compilation

### 1. Hébergement Local avec Laravel Herd
Si vous utilisez **Laravel Herd**, le site est automatiquement accessible à l'adresse suivante :
👉 `http://michelin.test`

*Si vous n'utilisez pas Herd, vous pouvez lancer le serveur PHP classique de Laravel (bien que déconseillé par rapport à la configuration FrankenPHP/Herd) :*
```bash
php artisan serve
```

### 2. Compilation des Assets (Vite)
Pour compiler les styles Tailwind CSS v4 et activer le rafraîchissement à chaud (HMR) pendant le développement :

```bash
npm run dev
```

Pour compiler les ressources de manière optimisée pour la production/démo :

```bash
npm run build
```

---

## 🧪 Exécution des Tests

Le projet est couvert par une suite de tests unitaires et d'intégration utilisant **Pest PHP**. Vous pouvez la lancer pour vérifier votre installation :

```bash
php artisan test --compact
```
