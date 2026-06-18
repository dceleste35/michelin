# Architecture Technique de l'Application

Ce document présente l'architecture globale de **RideReady**, le modèle de données, les algorithmes clés et le pipeline d'intelligence artificielle (RAG + LLM).

---

## 🏗️ Vue d'Ensemble du Système

L'application suit une architecture monolithique modulaire sous **Laravel 13**, tirant parti de **Livewire Volt** (Single File Components) pour l'interface réactive et de **FrankenPHP** (ou Laravel Herd en local) pour le serveur d'application.

```mermaid
graph TD
    subgraph Client [Navigateur & NativePHP App]
        UI[Livewire Volt Components]
        Vite[Vite Assets HMR]
    end

    subgraph Backend [Laravel 13 - FrankenPHP]
        Route[Router & Middleware Auth]
        Controller[Controllers API / Web]
        ServiceWear[WearService - Usure SCORE]
        ServiceInfer[ProfileInferenceService - Profil & Terrain]
        ServiceRecom[RecommenderService - Recs & RAG]
        ServiceRag[RagService - Recherche pgvector]
    end

    subgraph External [APIs Externes]
        Strava[API Strava OAuth & Activités]
        OpenAI[API OpenAI Embeddings]
        Anthropic[API Anthropic Claude]
    end

    subgraph Database [PostgreSQL 16]
        DB[(Postgres Tables)]
        PGVector[(Extension pgvector)]
    end

    UI <--> Route
    Route <--> Controller
    Controller <--> ServiceWear
    Controller <--> ServiceRecom

    ServiceInfer <--> Strava
    ServiceWear <--> DB
    ServiceWear --> ServiceInfer
    
    ServiceRecom --> ServiceRag
    ServiceRecom --> Anthropic
    ServiceRag --> OpenAI
    ServiceRag <--> PGVector
    DB <--> PGVector
```

---

## 📊 Modèle de Données et Relations

L'application utilise PostgreSQL. L'extension `pgvector` est activée pour gérer le stockage et la recherche des vecteurs à 1536 dimensions.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email
        string password
        string strava_athlete_id
        int weight_kg
        string segment
        string riding_style
        boolean segment_overridden
    }
    strava_activities {
        bigint id PK
        bigint user_id FK
        string external_id
        string gear_id
        string sport_type
        double distance_m
        int moving_time_s
        double average_speed_ms
        double total_elevation_gain_m
        double average_watts
        double average_cadence
        string surface_derived
        timestamp start_date
        jsonb raw_json
    }
    products {
        bigint id PK
        string web_range_name
        string segment
        string tire_position
        boolean is_tubeless_ready
        double weight_g
        double width_mm
        jsonb metadata
    }
    user_tires {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        string position
        date mounted_at
        double mounted_odometer_km
        double wear_percent
        boolean is_active
    }
    knowledge_chunks {
        bigint id PK
        bigint product_id FK
        string segment
        text content
        string source
        vector embedding_1536
    }
    recommendations {
        bigint id PK
        bigint user_id FK
        bigint current_product_id FK
        bigint recommended_product_id FK
        json rationale_json
        timestamp created_at
    }

    users ||--o{ strava_activities : "a"
    users ||--o{ user_tires : "possede"
    users ||--o{ recommendations : "reçoit"
    products ||--o{ user_tires : "est_lie_a"
    products ||--o{ knowledge_chunks : "contient"
    user_tires ||--o{ recommendations : "concerne"
```

---

## ⚡ Algorithmes Clés

### 1. Détection Automatique de Surface (`ProfileInferenceService`)
L'API Strava ne fournit pas de type de surface (goudron, terre, etc.). Le service [ProfileInferenceService](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/ProfileInferenceService.php) analyse les signaux physiques de chaque sortie pour déduire le terrain dominant :

* **Ride & VirtualRide** : Directement classés en `Asphalt` (Route).
* **GravelRide** :
  * Dénivelé $\le 8\text{ m/km}$ et vitesse $> 24\text{ km/h}$ $\rightarrow$ `Asphalt` (Randonnée roulante type route).
  * Dénivelé $\le 14\text{ m/km}$ $\rightarrow$ `Hardpacked` (Chemin sec/damé).
  * Sinon $\rightarrow$ `Mixed` (Chemin caillouteux/technique).
* **MountainBikeRide & EMountainBikeRide** :
  * Dénivelé $\le 15\text{ m/km}$ $\rightarrow$ `Mixed`.
  * Vitesse moyenne $< 12\text{ km/h}$ $\rightarrow$ `Mud` (Single-track très technique/boueux).
  * Dénivelé $\le 30\text{ m/km}$ $\rightarrow$ `Soft` (Terre meuble).
  * Sinon $\rightarrow$ `Mud` (Terrain difficile).

---

### 2. Formule de Calcul de l'Usure (`WearService` &mdash; SCORE)
L'usure n'est pas calculée sur les simples kilomètres réels, mais convertie en **kilomètres équivalents** accumulés selon la sévérité de l'usage. La formule SCORE est appliquée par activité depuis la date de montage (`mounted_at`) :

$$\text{Usure Activité (km équivalents)} = \text{Distance (km)} \times \text{Coeff Terrain} \times \text{Coeff Poids} \times \text{Coeff Style}$$

* **Coeff Poids** (borné entre $0.85$ et $1.30$) :
  $$\text{Coeff Poids} = 1 + \frac{\text{Poids (kg)} - 80}{100}$$
* **Coeff Style** (basé sur l'agressivité détectée) :
  * `ENDURANCE` : $1.00$
  * `MIXED` : $1.08$
  * `AGRESSIF` : $1.15$
* **Coeff Terrain** (dépendant du segment et du sol) :
  * Configuré dynamiquement dans la table `wear_coefficients`. Exemple pour le segment **Gravel** :
    * Asphalt : $0.90$ | Hardpacked : $1.10$ | Mixed : $1.25$ | Soft : $1.40$ | Mud : $1.80$
* **Calcul d'End of Life (EOL)** :
  * Le pourcentage d'usure final est : 
    $$\text{Usure (\%)} = \min\left(100.0, \frac{\sum \text{Usure Activité}}{\text{Baseline EOL (ex. 4000 km)}}\right) \times 100$$

---

## 🤖 Pipeline de Recommandation (RAG + LLM)

Lorsqu'un pneu atteint un seuil critique d'usure ($\ge 85\%$), le système déclenche une proposition de remplacement gérée par [RecommenderService](file:///C:/Users/Guillaume/PhpstormProjects/michelin/app/Services/RecommenderService.php) :

```mermaid
sequenceDiagram
    autonumber
    participant UI as Livewire (Front)
    participant Recs as RecommenderService
    participant RAG as RagService
    participant OpenAI as API OpenAI Embeddings
    participant DB as PGVector (Knowledge Chunks)
    participant LLM as API Anthropic Claude 3.5

    UI->>Recs: Demande de Recommandation (Tire ID)
    Recs->>Recs: Logique Score (Détermination du produit cible)
    Recs->>RAG: retrieve("comparatif gravel...")
    RAG->>OpenAI: POST /v1/embeddings (Requête texte)
    OpenAI-->>RAG: Retourne le vecteur (1536d)
    RAG->>DB: Requête similarité cosinus (<=>) + Filtre Segment
    DB-->>RAG: Top 3 Chunks (Faits techniques réels)
    RAG-->>Recs: Liste des Faits Produits
    Recs->>LLM: Génère Justification (Prompt + Profil + Faits RAG)
    LLM-->>Recs: Texte argumenté comparatif
    Recs->>DB: Sauvegarde la recommandation en cache
    Recs-->>UI: Retourne le pneu recommandé & la justification
```

### Protection anti-hallucination :
1. **Filtre strict de segment** : La recherche vectorielle filtre uniquement sur les fiches produits correspondant au segment de l'utilisateur (Gravel, VTT, Route, etc.).
2. **Seuil de similarité cosinus** : Seuls les chunks avec une distance cosinus $< 0.25$ sont acceptés pour éviter d'injecter des données hors-sujet.
3. **Prompt directif** : Le LLM reçoit une consigne lui interdisant d'inventer des données techniques non présentes dans les chunks du catalogue officiel fournis en contexte.
