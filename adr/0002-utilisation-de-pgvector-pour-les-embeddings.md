# ADR 0002 : Utilisation de pgvector pour le stockage d'embeddings

* **Statut** : Accepté
* **Date** : 2026-06-16
* **Auteur** : Antigravity (AI assistant) & Guillaume

---

## Contexte et Problématique

Le projet Michelin intègre des fonctionnalités liées à l'Intelligence Artificielle (RAG - *Retrieval-Augmented Generation*, et recherche sémantique). Ces fonctionnalités nécessitent de stocker et d'effectuer des recherches de similarité sur des vecteurs de grande dimension (embeddings) générés par des modèles LLM (comme OpenAI ou Anthropic).

Nous devions choisir une solution de base de données vectorielle.

---

## Options Envisagées

### Option A : Base de données vectorielle dédiée externe (ex: Pinecone, Qdrant, Milvus)
* **Avantages** : Hautement optimisé pour des milliards de vecteurs, fonctionnalités avancées d'indexation vectorielle prêtes à l'emploi.
* **Inconvénients** : Ajoute une dépendance réseau externe, complexité opérationnelle accrue (gestion d'un nouveau service), synchronisation complexe des données relationnelles classiques (ex: profils utilisateurs, articles) avec la base vectorielle.

### Option B : PostgreSQL avec l'extension `pgvector`
* **Avantages** :
  - **Base de données unique** : Le stockage relationnel classique et le stockage vectoriel se trouvent dans la même base de données.
  - **Requêtes simplifiées** : Il est possible de faire des jointures SQL standard directes entre les tables relationnelles et les embeddings vectoriels.
  - **Opérations simples** : Sauvegardes, transactions ACID, et indexation habituelles de PostgreSQL.
  - **Gratuit et Open-Source** : Facile à faire tourner en local sous Docker avec l'image `pgvector/pgvector`.
* **Inconvénients** : Légèrement moins performant sur des jeux de données d'une taille extrême (plusieurs dizaines de millions de vecteurs) par rapport à des moteurs vectoriels dédiés.

---

## Décision

Nous avons choisi l'**Option B : PostgreSQL + pgvector** en utilisant l'image Docker `pgvector/pgvector:pg17`.

---

## Conséquences

- **Architecture simplifiée** : Pas de service externe supplémentaire à payer ou à manager.
- **Intégration Laravel fluide** : Utilisation aisée via des packages PHP de recherche de vecteurs ou des requêtes Eloquent personnalisées.
- **Cohérence des données** : Pas de désynchronisation possible entre les objets de la base relationnelle et leurs représentations vectorielles.
