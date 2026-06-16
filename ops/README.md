# Michelin Docker Environment (FrankenPHP + PostgreSQL + pgAdmin)

Ce dossier `ops/` contient la configuration nécessaire pour lancer et exécuter le projet Michelin de manière conteneurisée. 

Les variables d'environnement de Docker sont stockées dans le fichier `ops/.env`, lequel est ignoré par Git pour des raisons de sécurité.

---

## 🚀 Lancement rapide

### 1. Préparer l'environnement
Copiez le fichier d'exemple fourni pour créer votre fichier `.env` de configuration Docker :

```bash
cp ops/.env.docker.example ops/.env
```
*(Sur Windows PowerShell, vous pouvez utiliser : `Copy-Item ops/.env.docker.example ops/.env`)*

### 2. Démarrer les conteneurs
Exécutez la commande suivante pour construire l'image de l'application et démarrer tous les services en arrière-plan :

```bash
docker compose -f ops/docker-compose.yml up -d --build
```

### 3. Configurer l'application (au premier lancement)
Générez la clé de chiffrement Laravel, installez les dépendances JavaScript et compilez les assets :

```bash
# Générer la clé d'application (s'écrira directement dans ops/.env)
docker compose -f ops/docker-compose.yml exec app php artisan key:generate

# Installer les dépendances JS (npm)
docker compose -f ops/docker-compose.yml exec app npm install

# Compiler les assets pour la production (Vite)
docker compose -f ops/docker-compose.yml exec app npm run build
```

### 4. Lancer les migrations
```bash
docker compose -f ops/docker-compose.yml exec app php artisan migrate
```

### 5. Accéder aux services
Une fois les conteneurs démarrés :
- **Application Web (Laravel + FrankenPHP)** : [http://localhost:8000](http://localhost:8000)
- **pgAdmin (Gestion de base de données)** : [http://localhost:5050](http://localhost:5050)

---

## 💻 Développement avec Vite (Hot Reload)

Si vous développez et souhaitez utiliser le rechargement à chaud (HMR) de Vite :

1. Assurez-vous que le port `5173` est bien exposé (configuré par défaut dans `ops/docker-compose.yml`).
2. Lancez le serveur de développement Vite à l'intérieur du conteneur `app` :
   ```bash
   docker compose -f ops/docker-compose.yml exec app npm run dev
   ```

---

## 🔑 Identifiants par défaut

### pgAdmin (Interface Web)
- **Email** : `admin@michelin.com` (configurable dans `ops/.env`)
- **Mot de passe** : `adminpassword` (configurable dans `ops/.env`)

### Connexion PostgreSQL depuis pgAdmin
Pour connecter pgAdmin à la base de données :
1. Cliquez sur **Add New Server**.
2. Dans l'onglet **General**, nommez le serveur (ex: `Michelin DB`).
3. Dans l'onglet **Connection** :
   - **Host name/address** : `db`
   - **Port** : `5432`
   - **Maintenance database** : `postgres`
   - **Username** : `postgres`
   - **Password** : `mysecretpassword` (défini dans `ops/.env`)
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
