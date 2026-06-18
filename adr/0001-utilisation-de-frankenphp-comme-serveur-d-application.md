# ADR 0001 : Utilisation de FrankenPHP comme serveur d'application

* **Statut** : Accepté
* **Date** : 2026-06-16
* **Auteur** : Antigravity (AI assistant) & Guillaume

---

## Contexte et Problématique

Pour le déploiement et l'environnement de développement Docker du projet Michelin (Laravel 13 + PHP 8.4), nous avions besoin d'un serveur d'application rapide, moderne et facile à configurer.

Traditionnellement, les applications PHP sont exécutées avec une stack multi-conteneurs comprenant :
- Un conteneur **Nginx** (pour servir les fichiers statiques et agir en reverse-proxy).
- Un conteneur **PHP-FPM** (pour exécuter le code PHP).

Nous voulons explorer des alternatives pour simplifier l'orchestration tout en garantissant d'excellentes performances.

---

## Options Envisagées

### Option A : Nginx + PHP-FPM (Traditionnel)
* **Avantages** : Très documenté, standard éprouvé dans l'industrie.
* **Inconvénients** : Nécessite deux conteneurs distincts, de la configuration supplémentaire pour la communication (socket unix ou TCP port 9000), et une synchronisation complexe des volumes pour les fichiers statiques partagés.

### Option B : FrankenPHP (Moderne)
* **Avantages** :
  - Serveur d'application unique écrit en Go (basé sur **Caddy Server**).
  - Pas besoin d'un conteneur Nginx séparé : FrankenPHP sert directement les fichiers statiques et exécute PHP dans le même processus.
  - Support natif de HTTP/3, compression automatique (Gzip/Brotli) et génération de certificats SSL avec Let's Encrypt.
  - Intégration optimale avec **Laravel Octane** pour des gains de performance massifs (mode Worker).
  - Installation simple des dépendances PHP grâce à `install-php-extensions`.
* **Inconvénients** : Technologie plus récente que Nginx.

---

## Décision

Nous avons choisi l'**Option B : FrankenPHP** (`dunglas/frankenphp:1-php8.4-alpine`).

---

## Conséquences

- **Simplification de l'environnement** : Le fichier `docker-compose.yml` ne contient plus que le service applicatif (`app`), la base de données (`db`), et l'interface d'administration (`pgadmin`). La maintenance de la configuration Nginx n'est plus nécessaire.
- **Performance** : Temps de réponse optimisés.
- **Facilité d'évolution** : Transition transparente vers Laravel Octane si le projet le nécessite à l'avenir.
- **Portabilité** : Une image Docker unique prête pour la production (build multi-stage simplifié).
