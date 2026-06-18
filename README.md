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

## 🧠 Architecture IA &mdash; SCORE Déterministe + RAG + LLM

### La règle d'or du projet

> **RideReady ne laisse jamais une IA inventer un chiffre.**

La recommandation repose sur **trois briques complémentaires**, chacune utilisée là et seulement là où elle excelle :

| Brique | Rôle | Pourquoi ce choix |
|---|---|---|
| `SCORE` (PHP pur) | **Calcule** l'usure, le profil, la compatibilité produit | Déterministe, auditable, zéro hallucination possible |
| `RAG` (pgvector) | **Récupère** les faits exacts du catalogue Michelin | Le LLM seul invente les specs &mdash; le RAG les ancre dans la source |
| `LLM` (Claude Haiku) | **Reformule** en prose motivante des faits DÉJÀ calculés | Jamais sur un chiffre ; uniquement sur du texte |

**Le piège évité :** mettre du LLM sur un calcul d'usure. L'usure est une formule physique, pas une opinion. On ne la confie pas à une IA.

---

### Couche 1 &mdash; Le Score d'Usure (100 % déterministe)

Chaque activité Strava est convertie en **kilomètres équivalents** selon une formule physique pondérée :

```
km_eq = distance_réelle × coef_terrain × coef_poids × coef_style
```

**Coefficients du modèle** (stockés en base dans `wear_coefficients`, modifiables sans redéploiement) :

| Variable | Plage | Valeurs |
|---|---|---|
| `coef_terrain` | 0.80 → 1.40 | ASPHALT 0.8 · HARDPACKED 1.1 · MIXED 1.3 · SOFT 1.2 · MUD 1.4 |
| `coef_poids` | 0.85 → 1.30 | `1 + (poids_kg − 80) / 100` |
| `coef_style` | 1.00 → 1.15 | ENDURANCE 1.00 · MIXED 1.08 · AGRESSIF 1.15 |

**Exemple concret &mdash; Marc, 78 kg, style MIXED, sortie gravel 45 km :**

```
coef_terrain = 1.20   (gravel mixte)
coef_poids   = 0.98   (1 + (78 − 80) / 100)
coef_style   = 1.08   (MIXED)

km_eq = 45 × 1.20 × 0.98 × 1.08 = 57.2 km d'usure équivalents
```

L'accumulation sur toutes les sorties depuis le montage donne :

```
wear_pct     = min(100,  Σ(km_eq) / km_eol_baseline × 100)
remaining_km =          (km_eol_baseline − Σ(km_eq)) / coef_moyen_récent
```

`km_eol_baseline` = durée de vie de référence du modèle (ex : 4 000 km pour un Power Gravel en usage mixte, issu du catalogue).

**Flux de calcul :**

```
[Strava OAuth]
      │  import activités depuis le montage du pneu
      ▼
╔═══════════════════════════════════════════════╗
║  WearService :: getTireHealth(userTireId)      ║
║                                               ║
║  Pour chaque activité :                       ║
║    km_eq += dist × coef_terrain               ║
║                  × coef_poids                 ║
║                  × coef_style                 ║
║                                               ║
║  wear_pct  = Σ(km_eq) / km_eol × 100         ║
║  remaining = (km_eol − Σ) / coef_moyen       ║
╚═══════════════════════════════════════════════╝
      │
      ├─── wear_pct ≥ seuil (80 %) ──▶  Alerte UC-3 déclenchée
      │
      └─── wear_pct < seuil         ──▶  Mise à jour tableau de bord
```

> **Pourquoi déterministe ?** Pour la même entrée, on obtient toujours le même résultat. Le cycliste peut auditer son score. Michelin peut recalibrer les coefficients en base sans toucher au code.

---

### Couche 2 &mdash; Le Pipeline RAG (Retrieval-Augmented Generation)

#### Ce qu'est un RAG en une phrase

> Avant de laisser le LLM répondre, on **cherche les bons faits** dans notre base de connaissances, on les **colle dans le contexte**, et le LLM **rédige en s'appuyant uniquement sur ces faits fournis**.

L'IA ne répond plus « de mémoire ». Elle répond comme un expert avec **la fiche technique sous les yeux** &mdash; elle lit et reformule, elle n'invente pas.

| | Sans RAG (LLM seul) | Avec RAG |
|---|---|---|
| Réponse | *« Ce pneu dure dans les 4 000 km environ. »* | *« D'après la fiche Michelin : Power Gravel RS, durée estimée 5 000 km, pression 2,0–4,0 bar. »* |
| Statut | ❌ Chiffre inventé | ✅ Sourcé et vérifiable |

#### Architecture en 2 temps

**TEMPS 1 &mdash; Ingestion du catalogue** (une seule fois, hors-ligne)

```
[Catalogue Michelin 2026]  Excel / JSON
      │
      │  Découpage en chunks sémantiques
      │  1 chunk = 1 fait vérifiable
      │  ex : "Power Gravel RS — 700×42C, TPI 3×120,
      │         pression 2,0–4,0 bar, compound GUM-X"
      ▼
[EmbeddingService :: embed(chunk)]
      │  Appel API embeddings (OpenAI text-embedding-3-small)
      │  → vecteur de 1 536 nombres (adresse sémantique)
      ▼
[PostgreSQL + pgvector]
      │  table : knowledge_chunks
      │  colonne : embedding_1536  vector(1536)
      │  index HNSW cosinus (recherche rapide)
      ▼
  Base vectorielle prête
```

**TEMPS 2 &mdash; Retrieval + Génération** (à chaque recommandation)

```
[Profil cycliste + Score d'usure calculé]
      │
      ▼
[RagService :: retrieve("gravel mixte longue distance")]
      │
      │  ① Embed la requête → vecteur de la question
      │
      │  ② Recherche SQL sémantique :
      │     SELECT content, source, product_id
      │     FROM   knowledge_chunks
      │     WHERE  segment = 'GRAVEL'             -- filtre dur anti-faux-positifs
      │     ORDER  BY embedding_1536 <=> $vec     -- distance cosinus pgvector
      │     HAVING distance < 0.25               -- seuil anti-hallucination
      │     LIMIT  5
      │
      │  ③ Aucun chunk sous le seuil ?
      │     → réponse "information non disponible"
      │       (pas d'hallucination de secours)
      ▼
[Top-5 chunks factuels — catalogue Michelin]
      │
      ▼
[LlmService :: writeJustification(score_facts + rag_chunks)]
      │  température 0.3 (fidélité maximale)
      │  system prompt : "INTERDICTION d'inventer un chiffre.
      │                   Base-toi STRICTEMENT sur les faits fournis."
      ▼
  Justification d'achat &mdash; 3 phrases, 0 chiffre inventé
```

**Choix techniques justifiés :**

| Choix | Raison |
|---|---|
| pgvector dans PostgreSQL | Pas de base vectorielle externe &mdash; une seule infra, zéro désynchronisation |
| Filtre `segment` SQL | Impossible de proposer un pneu VTT à un gravel rider |
| Seuil distance cosinus `< 0.25` | Rejette les chunks sémantiquement trop éloignés de la requête |
| Température LLM `= 0.3` | Variabilité minimale, réponse ancrée dans les faits |
| System prompt anti-hallucination | Hallucinations structurellement impossibles |
| Score calculé **avant** l'appel LLM | Le LLM reçoit des faits &mdash; il ne calcule pas |

---

### Vue d'ensemble &mdash; Les 3 briques en interaction

```
  [Strava]                [Catalogue Michelin]
  activités                  Excel / JSON
      │                           │
      ▼                           ▼
  ┌──────────┐             ┌──────────────────┐
  │  SCORE   │             │  RAG — Ingestion │
  │  PHP pur │             │  EmbedChunksJob  │
  │          │             │  pgvector        │
  │ usure    │             └────────┬─────────┘
  │ profil   │                      │
  │ matching │                      │ top-5 chunks
  └────┬─────┘                      │
       │ faits chiffrés             │
       └──────────┬─────────────────┘
                  ▼
           ┌────────────┐
           │ LlmService │  température 0.3
           │ Claude     │  prompt anti-hallucination
           │ Haiku      │
           └─────┬──────┘
                 ▼
    ┌────────────────────────────┐
    │   Recommandation finale    │
    │                            │
    │  SCORE : "Usure 86%,       │
    │           ~240 km restants"│
    │                            │
    │  RAG   : "Power Gravel RS, │
    │           TPI 120, GUM-X"  │
    │                            │
    │  LLM   : "Voici pourquoi   │
    │           ce pneu est fait │
    │           pour votre usage"│
    └────────────────────────────┘
```

**L'IA ne remplace pas le calcul physique &mdash; elle le met en mots.**

Cette séparation évite le risque classique des systèmes LLM purs : un LLM seul interrogé sur l'usure invente un chiffre plausible mais faux. Ici, les chiffres viennent exclusivement du modèle mathématique ou du catalogue réel ; le LLM ne fait que les reformuler en prose motivante.

---

### Matrice use cases &times; briques d'intelligence

| Use Case | SCORE | RAG | LLM |
|---|:---:|:---:|:---:|
| UC-1 &middot; Inférence profil rider | ● | | |
| UC-2 &middot; Tire Health &mdash; usure prédictive | ● | | |
| UC-3 &middot; Alerte fin de vie ~3 semaines | ● | | ● |
| UC-4 &middot; Recommandation pneu personnalisée | ● | ● | ● |
| UC-5 &middot; Justification comparative chiffrée | ● | ● | ● |
| UC-6 &middot; Panier pré-rempli + stock | ● | | |

* **SCORE** est présent partout (cœur physique défendable).
* Le **RAG** ancre les faits produit.
* Le **LLM** n'apparaît **jamais seul** sur un calcul ou un chiffre. La frontière est nette et assumée devant le jury.

---

### Ce que le jury peut vérifier dans le code

| Composant | Fichier |
|---|---|
| Formule d'usure | [app/Services/WearService.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/WearService.php) |
| Coefficients terrain / poids / style | Table SQL `wear_coefficients` (seeder [WearCoefficientsSeeder.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/database/seeders/WearCoefficientsSeeder.php)) |
| Inférence du profil Strava | [app/Services/ProfileInferenceService.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/ProfileInferenceService.php) |
| Retrieval sémantique pgvector | [app/Services/RagService.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/RagService.php) |
| Orchestration Score + RAG + LLM | [app/Services/RecommenderService.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/RecommenderService.php) |
| System prompt anti-hallucination | [app/Services/LlmService.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/LlmService.php) |
| Ingestion des embeddings | [app/Jobs/EmbedChunksJob.php](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Jobs/EmbedChunksJob.php) |
| Vecteurs catalogue Michelin | Table `knowledge_chunks`, colonne `embedding_1536` |

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
