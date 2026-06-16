# Michelin Docker Environment (FrankenPHP + PostgreSQL + pgAdmin)

Ce dossier `ops/` contient la configuration nécessaire pour lancer et exécuter le projet Michelin de manière conteneurisée. Les variables d'environnement pour l'environnement Docker sont gérées via le fichier `.env.docker` pour rester isolées de vos configurations locales.

---

## 🚀 Lancement rapide

### 1. Démarrer les conteneurs
Depuis la racine du projet, exécutez la commande suivante pour construire l'image de l'application et démarrer tous les services en arrière-plan :

```bash
docker compose -f ops/docker-compose.yml up -d --build
```

### 2. Générer la clé d'application (au premier lancement)
Si c'est le premier lancement, générez la clé de chiffrement Laravel dans le conteneur :

```bash
docker compose -f ops/docker-compose.yml exec app php artisan key:generate
```

### 3. Lancer les migrations
```bash
docker compose -f ops/docker-compose.yml exec app php artisan migrate
```

### 4. Accéder aux services
Une fois les conteneurs démarrés :
- **Application Web (Laravel + FrankenPHP)** : [http://localhost:8000](http://localhost:8000)
- **pgAdmin (Gestion de base de données)** : [http://localhost:5050](http://localhost:5050)

---

## 🔑 Identifiants par défaut

### pgAdmin (Interface Web)
- **Email** : `admin@michelin.local` (configurable dans `ops/.env.docker`)
- **Mot de passe** : `adminpassword` (configurable dans `ops/.env.docker`)

### Connexion PostgreSQL depuis pgAdmin
Pour connecter pgAdmin à la base de données :
1. Cliquez sur **Add New Server**.
2. Dans l'onglet **General**, nommez le serveur (ex: `Michelin DB`).
3. Dans l'onglet **Connection** :
   - **Host name/address** : `db`
   - **Port** : `5432`
   - **Maintenance database** : `postgres`
   - **Username** : `postgres`
   - **Password** : `mysecretpassword` (défini dans `ops/.env.docker`)
4. Cliquez sur **Save**.

---

## 🛠️ Commandes courantes

### Exécuter les migrations Laravel
```bash
docker compose -f ops/docker-compose.yml exec app php artisan migrate
```

### Exécuter les tests (Pest / PHPUnit)
```bash
docker compose -f ops/docker-compose.yml exec app php artisan test
```

### Installer de nouvelles dépendances Composer
```bash
docker compose -f ops/docker-compose.yml exec app composer install
```

### Accéder au terminal du conteneur App
```bash
docker compose -f ops/docker-compose.yml exec app bash
```

### Consulter les logs
```bash
docker compose -f ops/docker-compose.yml logs -f
```

---

## 🛑 Arrêter l'environnement

```bash
docker compose -f ops/docker-compose.yml down
```
