# RideReady &mdash; Suivi d'usure et Recommandation de Pneus Michelin

**RideReady** est une application web intelligente conçue pour les cyclistes, permettant de suivre en temps réel l'usure de leurs pneus vélo Michelin en se basant sur leurs activités réelles Strava, et de leur recommander le pneu de remplacement idéal grâce à un système RAG (Retrieval-Augmented Generation) et un LLM alimenté par le catalogue de produits Michelin.

---

## 🚀 Fonctionnalités Clés

1. **Dashboard de Suivi en Temps Réel** : Visualisation de l'état d'usure (en % et kilomètres restants estimables) des pneus avant et arrière.
2. **Synchronisation Strava** : Importation automatique des sorties de l'utilisateur (distance, dénivelé, vitesse) pour évaluer précisément l'usure selon le type de terrain.
3. **Algorithme d'Usure Physique (SCORE)** : Calcul de l'usure réelle pondéré par des coefficients de surface (asphalte, chemin damé, mixte, boue), le poids du cycliste et son style de pilotage.
4. **Recommandations Intelligentes (RAG + LLM)** : Génération personnalisée d'une recommandation de pneu en cas d'usure critique, justifiée par une comparaison technique générée par IA à partir du catalogue officiel Michelin.

---

## 🛠️ Stack Technique

* **Framework Principal** : [Laravel 13](https://laravel.com)
* **Frontend** : [Livewire 4](https://livewire.laravel.com) & [Livewire Volt](https://livewire.laravel.com/docs/volt) (Single File Components)
* **Design System** : [Flux UI](https://fluxui.dev) & [Tailwind CSS v4](https://tailwindcss.com)
* **Base de Données** : PostgreSQL avec l'extension vectorielle [pgvector](https://github.com/pgvector/pgvector)
* **IA & RAG** : 
  * OpenAI (`text-embedding-3-small`) pour la vectorisation du catalogue.
  * Anthropic (`claude-sonnet-4-6`) pour la génération de la justification technique personnalisée.
* **Intégrations** : API Strava OAuth.
* **Environnement Hybride** : [NativePHP](https://nativephp.com) pour le support de l'application de bureau/mobile.

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
