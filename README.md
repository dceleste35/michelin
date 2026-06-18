# RideReady &mdash; Suivi d'usure et Recommandation de Pneus Michelin

[![tests](https://github.com/dceleste35/michelin/actions/workflows/tests.yml/badge.svg)](https://github.com/dceleste35/michelin/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-13.0-red.svg)](https://laravel.com/)
[![TailwindCSS Version](https://img.shields.io/badge/TailwindCSS-v4.0-38bdf8.svg)](https://tailwindcss.com/)

**RideReady** est une application web intelligente conçue pour les cyclistes, permettant de suivre en temps réel l'usure de leurs pneus vélo Michelin en se basant sur leurs activités réelles Strava, et de leur recommander le pneu de remplacement idéal grâce à un système RAG (Retrieval-Augmented Generation) et un LLM alimenté par le catalogue de produits Michelin.

---

## 🔗 Liens du Projet

* **🔗 Application en ligne :** [https://michelin-main-0kkx4c.laravel.cloud/qr](https://michelin-main-0kkx4c.laravel.cloud/qr)
* **🎬 Vidéo démo :** TODO 

---

## 🚀 Fonctionnalités Clés

1. **Dashboard de Suivi en Temps Réel** : Visualisation de l'état d'usure (en % et kilomètres restants estimables) des pneus avant et arrière.
2. **Synchronisation Strava** : Importation automatique des sorties de l'utilisateur (distance, dénivelé, vitesse) pour évaluer précisément l'usure selon le type de terrain.
3. **Algorithme d'Usure Physique (SCORE)** : Calcul de l'usure réelle pondéré par des coefficients de surface (asphalte, chemin damé, mixte, boue), le poids du cycliste et son style de pilotage.
4. **Recommandations Intelligentes (RAG + LLM)** : Génération personnalisée d'une recommandation de pneu en cas d'usure critique, justifiée par une comparaison technique générée par IA à partir du catalogue officiel Michelin.

---

## 🛠️ Stack Technique

* **Framework Principal** : [Laravel 13](https://laravel.com)
* **PHP** : version **8.4**
* **Node.js** : version **22**
* **Frontend** : [Livewire 4](https://livewire.laravel.com) & [Livewire Volt](https://livewire.laravel.com/docs/volt) (Single File Components)
* **Design System** : [Flux UI](https://fluxui.dev) & [Tailwind CSS v4](https://tailwindcss.com)
* **Base de Données** : PostgreSQL avec l'extension vectorielle [pgvector](https://github.com/pgvector/pgvector)
* **IA & RAG** : 
  * OpenAI (`text-embedding-3-small`) pour la vectorisation du catalogue.
  * Anthropic (`claude-sonnet-4-6`) pour la génération de la justification technique personnalisée.
* **Intégrations** : API Strava OAuth.
* **Environnement Hybride** : [NativePHP](https://nativephp.com) pour le support de l'application de bureau/mobile.

---

## 📁 Structure du Projet

Voici l'organisation des principaux composants et services développés pour l'application :

```
├── app/
│   ├── Jobs/
│   │   ├── ComputeWearJob.php       # Calcul périodique de l'usure (SCORE)
│   │   └── EmbedChunksJob.php       # Ingestion/vectorisation du catalogue en tâche de fond
│   └── Services/
│       ├── EmbeddingService.php     # Connexion OpenAI Embeddings
│       ├── LlmService.php           # Rédaction de la justification (Anthropic Claude)
│       ├── ProfileInferenceService.php # Inférence segment, style et surfaces Strava
│       ├── RagService.php           # Recherche vectorielle pgvector
│       ├── RecommenderService.php   # Pipeline RAG + LLM de recommandation
│       └── WearService.php          # Moteur de calcul d'usure SCORE
├── database/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php       # Seeder principal
│   │   ├── KnowledgeChunksSeeder.php # Textes techniques Michelin (RAG)
│   │   ├── MarcSeeder.php           # Données de démo du persona Marc
│   │   ├── ProductCatalogSeeder.php  # Catalogue des pneus Michelin
│   │   └── WearCoefficientsSeeder.php # Coefficients d'usure par surface
├── resources/
│   └── views/
│       ├── components/
│       │   ├── ⚡tire-recommendation.blade.php # Comparateur IA réactif (Volt)
│       │   └── ⚡tire-wear-card.blade.php      # Jauge d'usure & simulateur (Volt)
│       ├── pages/
│       │   ├── ⚡activities.blade.php           # Liste des sorties Strava
│       │   └── ⚡profile.blade.php              # Profil & segment utilisateur
│       ├── dashboard.blade.php      # Écran principal
│       └── welcome.blade.php        # Écran d'accueil
└── docs/
    ├── architecture.md              # Documentation technique globale
    ├── installation-native.md       # Installation en local sans Docker (Herd)
    ├── deployment.md                # Guide de déploiement (Laravel Cloud)
    ├── user-guide.md                # Guide d'utilisation client
    ├── presentation-slides.md       # Support de pitch Hackathon (Marp)
    └── user-journey-mockups.md      # Parcours utilisateur & maquettes
```

---

## 🗄️ Initialisation de la Démo

Pour réinitialiser et tester l'application dans son état déterministe exact (Marc avec un pneu arrière usé à 86%) :

### Avec Docker :
```bash
docker compose -f ops/docker-compose.yml exec app php artisan demo:reset
```

### Sans Docker (Local / Herd) :
```bash
php artisan demo:reset
```

> [!NOTE]
> La commande `demo:reset` est une commande personnalisée qui effectue un nettoyage complet de la base de données (`migrate:fresh`), active l'extension pgvector, charge le catalogue de pneus Michelin (`ProductCatalogSeeder`), importe les données textuelles pour le RAG (`KnowledgeChunksSeeder`) et simule le profil complet de Marc avec 80 activités Strava (`MarcSeeder`).

---

## 📖 Naviguer dans la Documentation

Toute la documentation du projet est structurée ci-dessous selon votre profil :

### 💻 Espace Développeur
* **Architecture globale** : [docs/architecture.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/architecture.md) &mdash; Fonctionnement de la détection de surface, de l'usure SCORE, et du pipeline RAG.
* **Installation avec Docker (FrankenPHP)** : [ops/README.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/ops/README.md) &mdash; Lancement rapide avec l'environnement conteneurisé complet.
* **Installation en local (Native)** : [docs/installation-native.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/installation-native.md) &mdash; Configuration pas-à-pas avec Laravel Herd et PostgreSQL local.
* **Déploiement en Production** : [docs/deployment.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/deployment.md) &mdash; Guide de mise en ligne sur Laravel Cloud.
* **Décisions d'Architecture (ADR)** : Découvrez les raisons de nos choix techniques dans le dossier [adr/](file:///C:/Users/Guillaume/PhpstormProjects/michelin/adr) (FrankenPHP, pgvector, choix de la stack).

### 👥 Espace Utilisateur & Démo
* **Guide Utilisateur** : [docs/user-guide.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/user-guide.md) &mdash; Comment connecter son compte Strava, comprendre l'usure et naviguer dans l'application.
* **Parcours Utilisateurs & Maquettes** : [docs/user-journey-mockups.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/user-journey-mockups.md) &mdash; Diagrammes des flux utilisateurs et structures d'écrans.
* **Slides de Présentation** : [docs/presentation-slides.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/docs/presentation-slides.md) &mdash; Présentation du projet au format Pitch / Hackathon.

---

## 🧪 Lancement Rapide des Tests

Pour valider l'intégrité du code source, vous pouvez exécuter la suite de tests (Pest PHP) :

```bash
# Si vous utilisez Docker :
docker compose -f ops/docker-compose.yml exec app php artisan test

# Si vous utilisez un environnement local natif :
php artisan test
```

---

*Développé avec passion pour le Hackathon Michelin 2026.*
