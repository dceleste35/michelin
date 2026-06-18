# Guide de Déploiement (Laravel Cloud)

Ce guide décrit la procédure de mise en production et de configuration de l'application **RideReady** sur **Laravel Cloud**, conformément aux décisions actées dans l'[`ADR 0000`](file:///C:/Users/Guillaume/PhpstormProjects/michelin/adr/0000-choix-de-la-stack-technique.md).

---

## ☁️ Pourquoi Laravel Cloud ?

Laravel Cloud est une plateforme PaaS (Platform as a Service) conçue spécifiquement pour héberger des applications Laravel avec une configuration minimale. Elle gère automatiquement :
* Le provisionnement des serveurs et de la base de données PostgreSQL.
* L'intégration continue (CI/CD) liée à votre dépôt GitHub (déploiement automatique lors d'un `git push`).
* La gestion du certificat SSL (HTTPS automatique).
* L'exécution des tâches en arrière-plan (Queues/Workers) et des tâches planifiées.

---

## 🛠️ Étapes de Configuration sur le Dashboard Laravel Cloud

### 1. Lier le Dépôt GitHub
1. Connectez-vous sur [Laravel Cloud](https://cloud.laravel.com/).
2. Associez votre compte GitHub et sélectionnez le dépôt `michelin`.
3. Créez un nouveau projet (ex: `RideReady`) et associez-le à la branche principale (ex: `main` ou `master`).

### 2. Provisionner la Base de Données
Dans l'onglet **Databases** de votre environnement sur Laravel Cloud :
1. Créez une nouvelle base de données **PostgreSQL**.
2. Laravel Cloud injectera automatiquement les variables d'environnement de connexion (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) de manière transparente dans l'environnement de l'application.

> [!NOTE]
> L'extension `pgvector` requise pour la recherche de recommandations est pré-installée sur les serveurs PostgreSQL managés par Laravel Cloud. Notre migration [`2026_06_16_070400_enable_vector_extension.php`](file:///C:/Users/Guillaume/PhpstormProjects/michelin/database/migrations/2026_06_16_070400_enable_vector_extension.php) l'active automatiquement lors de l'exécution des migrations.

### 3. Configurer les Variables d'Environnement
Dans l'onglet **Variables** de votre environnement, ajoutez les clés de production suivantes :

| Clé | Valeur suggérée | Rôle |
| :--- | :--- | :--- |
| `APP_ENV` | `production` | Configure le framework en mode production (sécurisé). |
| `APP_DEBUG` | `false` | Désactive l'affichage des erreurs détaillées en public. |
| `STRAVA_CLIENT_ID` | `[ID de votre application Strava]` | ID client de production pour la connexion Strava. |
| `STRAVA_CLIENT_SECRET` | `[Clé secrète de production]` | Secret client pour l'obtention des tokens Strava. |
| `STRAVA_REDIRECT_URI` | `https://votre-domaine.laravel.cloud/auth/strava/callback` | URL de retour après authentification Strava. |
| `OPENAI_API_KEY` | `[Votre clé API OpenAI]` | Requis pour vectoriser le texte de recherche RAG. |
| `ANTHROPIC_API_KEY` | `[Votre clé API Anthropic]` | Requis pour la rédaction technique comparative via Claude. |

---

## 🚀 Script de Construction et Déploiement

Laravel Cloud compile automatiquement les assets et exécute les commandes de déploiement configurées dans le fichier de configuration de déploiement (ou dans l'interface web sous la section **Deployment Steps**) :

### 1. Build Phase (Compilation)
Le build compile les feuilles de style Tailwind v4 et les scripts JS optimisés pour la production :
```bash
npm install && npm run build
```

### 2. Hook de Déploiement (Après Mise en Ligne)
À chaque nouveau déploiement réussi, la commande suivante est automatiquement exécutée pour appliquer les modifications de structure de base de données en toute sécurité :
```bash
php artisan migrate --force
```

---

## 🧪 Seeding Initial en Production

Lors du premier déploiement, il est nécessaire de remplir le catalogue de produits Michelin pour que l'algorithme de recommandation fonctionne, puis d'initialiser le persona de démo Marc (sous lequel le bouton « Se connecter avec Strava » authentifie en démo).  
Exécutez ces tâches uniques à l'aide de la console de commandes intégrée dans le tableau de bord Laravel Cloud, **dans cet ordre** (Marc dépend du catalogue) :

```bash
php artisan db:seed --class=ProductCatalogSeeder --force
php artisan db:seed --class=MarcSeeder --force
```

Les deux seeders sont idempotents (`updateOrCreate`) : on peut les relancer sans créer de doublons ni écraser de comptes réels. Sans `MarcSeeder`, le bouton « Se connecter avec Strava » redirige vers `/login` en production faute de profil héros.

> [!WARNING]
> La commande `php artisan demo:reset` ne doit **pas** être lancée en production : elle effectue un `migrate:fresh` qui supprime toutes les données existantes. De même, `php artisan db:seed` (sans `--class`) recrée un « Test User » et n'est pas idempotent. Utilisez uniquement les commandes ciblées `--class=…` ci-dessus pour initialiser sans impacter les comptes utilisateurs réels.
