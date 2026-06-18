# Architecture Decision Records (ADR) & Schémas

Ce dossier contient les comptes-rendus de décision d'architecture (ADR) pour le projet Michelin, ainsi que les schémas explicatifs des choix techniques.

---

## 📑 Liste des ADRs

0. **[ADR 0000 : Choix de la stack technique et de l'infrastructure](0000-choix-de-la-stack-technique.md)**  
   *Pourquoi Laravel 13, NativePHP, PostgreSQL et Laravel Cloud ont été retenus pour le hackathon.*

1. **[ADR 0001 : Utilisation de FrankenPHP comme serveur d'application](0001-utilisation-de-frankenphp-comme-serveur-d-application.md)**  
   *Pourquoi nous avons choisi FrankenPHP plutôt que la stack traditionnelle Nginx + PHP-FPM.*
   
2. **[ADR 0002 : Utilisation de pgvector pour le stockage d'embeddings](0002-utilisation-de-pgvector-pour-les-embeddings.md)**  
   *Justification du choix de pgvector pour gérer la recherche vectorielle (RAG).*
