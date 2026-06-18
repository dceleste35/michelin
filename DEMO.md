# RideReady — Déroulé de la démo

Parcours complet piloté par commandes, du **premier arrivant** jusqu'à la **conversion**.
L'app est conçue pour le **mobile** : on la présente sur téléphone.

---

## 0. Préparer la démo

```bash
php artisan demo:reset
```

Met la base à l'état **premier arrivant** :
- Marc, **connecté à Strava**, **16 sorties** GravelRide déjà importées
- **aucun pneu**, profil **non confirmé**
- cloche (alertes) vide

> Idempotent et déterministe : rejouable autant de fois que voulu. Refusé en production sans `--force`.

---

## 1. Arrivée du client — « on me force le mobile »

- Donner / afficher l'URL **`/qr`** (ex. `https://michelin-main-0kkx4c.laravel.cloud/qr`).
- En **desktop** → page « Une expérience pensée pour le mobile » + **QR code** (+ bouton « Continuer sur cet appareil » en secours).
- Le client **scanne** → l'app s'ouvre sur son téléphone.

**À montrer / dire :** « L'expérience est pensée mobile : on scanne, on est dans l'app. »

---

## 2. Connexion Strava → profil inféré

- Écran d'accueil → **« Se connecter avec Strava »**.
- Onboarding : le **profil est déduit des 16 sorties** (Gravel · Endurance · ~60 % route / 40 % chemin) — **aucun questionnaire**.
- Tap **« Oui, c'est ça »** pour confirmer.

**À dire :** « On ne demande rien : le profil est calculé (SCORE) à partir des sorties Strava. »

---

## 3. État initial — rien n'est encore suivi

- **Activités** : les 16 sorties, toutes **« À vérifier »** (aucun pneu assigné).
- **Pneus (garage)** : vide → « Ajoutez vos pneus ».
- **Dashboard** : carte « Ajoutez vos pneus ».
- **Cloche** : vide.

**À dire :** « Pour suivre l'usure, on déclare les pneus montés. »

---

## 4. Marc équipe son vélo

```bash
php artisan demo:tires
```

Monte une paire **Power Gravel neuve** + la rattache à l'historique de sorties.

- **Dashboard** : pneus **sains** (jauge basse, vert).
- **Garage** : la paire avant/arrière.
- **Détail d'un pneu** : ses sorties + stats.
- Les 3 dernières sorties restent **« À vérifier »** → montrer la **vérification par sortie** (Activités → Vérifier → choisir les pneus).

> Variante directe : `php artisan demo:tires --wear=86` pour arriver tout de suite à l'état usé.

---

## 5. Le temps passe → fin de vie → alerte

```bash
php artisan demo:wear --rear=86 --front=62
```

- **Dashboard** : pneu arrière **rouge « Pneu en fin de vie »**.
- **Cloche** : s'allume, **badge 1**.

**À dire :** « On prévient *avant* la crevaison. »

---

## 6. Conversion (cloche → revendeur)

- Ouvrir la **cloche** → page **Alertes** → le pneu en fin de vie.
- **« Commander sur Decathlon »** → redirection revendeur (nouvel onglet).
- De retour : **« Marquer comme commandé »** → le pneu quitte la cloche (badge → 0) et passe en section **« Commandé »** (annulable).

**À dire :** « La conversion = une simple redirection vers nos revendeurs partenaires. »

---

## (Bonus) Gestion de la collection

- **Permuter** un pneu monté depuis le dashboard (sélecteur sur la carte).
- **Archiver** un pneu hors collection (garage) — réutilisable, restaurable.

---

## Aide-mémoire des commandes

| Commande | Effet |
|---|---|
| `php artisan demo:reset` | État premier arrivant (16 sorties, 0 pneu) |
| `php artisan demo:tires` | Monte une paire **neuve** + l'assigne aux sorties |
| `php artisan demo:tires --wear=86` | Monte une paire **usée** (alerte armée) |
| `php artisan demo:tires --wear=86 --front=70` | Usure avant/arrière personnalisée |
| `php artisan demo:wear --rear=86 --front=62` | Vieillit les pneus montés en direct |

---

## Sur le cloud (Laravel Cloud)

- Déployer, puis (via le runner de commandes Laravel Cloud) :
  - `php artisan migrate --force`
  - `php artisan db:seed --force` *(ou `demo:reset --force`)* → état de départ
- Piloter ensuite avec `demo:tires` / `demo:wear` selon le moment de la démo.
- ⚠️ `demo:reset` fait un `migrate:fresh` (bloqué en prod sans `--force`). Pour rejouer sans tout recréer, on peut relancer `db:seed --force`.

---

## Compte de démo

- **Email :** `marc@rideready.test` — **Mot de passe :** `password`
- La connexion Strava de démo connecte automatiquement Marc (mock, aucune vraie API Strava).
