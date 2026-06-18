# Guide de l'Utilisateur &mdash; RideReady

Bienvenue dans le guide d'utilisation de **RideReady**. Ce document vous explique comment l'application vous aide à suivre l'état de vos pneus vélo Michelin et à choisir le bon pneu de rechange au bon moment.

---

## 🌟 Introduction : Pourquoi utiliser RideReady ?

Les pneus sont le seul point de contact entre votre vélo et le sol. Connaître leur usure exacte est difficile : une simple usure visuelle ne suffit pas, et les kilomètres réels ne reflètent pas la fatigue subie sur des chemins caillouteux ou dans la boue.

**RideReady** résout ce problème en connectant votre compte **Strava** :
1. Nous analysons l'historique et le dénivelé de vos sorties pour déterminer la surface exacte sur laquelle vous roulez.
2. Nous calculons une usure physique réaliste prenant en compte votre poids et votre style de pilotage.
3. Nous vous suggérons le pneu Michelin de remplacement idéal, adapté à votre pratique réelle, avec une justification technique personnalisée rédigée par notre intelligence artificielle.

---

## 🚀 Étape 1 : Connecter votre compte Strava

Lors de votre première connexion :
1. Sur la page d'accueil, cliquez sur le bouton orange **« Se connecter avec Strava »**.
2. *(Pour le prototype de démonstration, cette connexion est simulée et vous connecte automatiquement au profil de **Marc**, un cycliste gravel actif).*
3. Une fois connecté, l'application importe automatiquement vos dernières sorties en arrière-plan.

---

## 📊 Étape 2 : Comprendre le Tableau de Bord (Dashboard)

Votre tableau de bord ([Mes Équipements](file:///C:/Users/Guillaume/PhpstormProjects/michelin/resources/views/dashboard.blade.php)) affiche vos pneus actuellement montés :

```
+--------------------------------------------------+
|                  PNEU ARRIÈRE                    |
|       MICHELIN Power Gravel (700x40C)            |
|                                                  |
|  [||||||||||||||||||||||||||||||||||||   ] 86%   |
|                                                  |
|  Statut : Critique  |  Distance restante : 340 km|
+--------------------------------------------------+
```

### Indicateurs de santé du pneu :
* **Jauge d'usure** : Affiche le pourcentage d'usure calculé.
  * **Vert ($< 60\%$)** : Pneu en excellent état.
  * **Orange ($60\% - 84\%$)** : Pneu usé. Planifiez son remplacement prochain.
  * **Rouge ($\ge 85\%$)** : Usure critique. Sécurité compromise, le pneu doit être changé immédiatement.
* **Kilométrage Restant** : Une estimation dynamique des kilomètres réels restants avant que le pneu ne soit hors-service, ajustée selon l'agressivité de vos dernières sorties.

---

## 💡 Étape 3 : La Recommandation IA Intelligente

Dès qu'un pneu passe sous le seuil critique d'usure ($\ge 85\%$), un encart de recommandation apparaît sur votre tableau de bord.

1. **Le Pneu Suggéré** : Notre algorithme sélectionne le pneu Michelin le plus adapté à votre profil actuel (ex: montée en gamme vers le *MICHELIN Power Gravel RS* pour plus de rendement).
2. **La Justification Technique** : Notre IA compare votre pneu actuel avec le pneu suggéré à partir du catalogue technique officiel de Michelin, en expliquant de façon factuelle les gains (ex. gains de watts, résistance aux crevaisons sur sol mixte).
3. **Le Comparatif Direct** : Un tableau synthétique oppose les caractéristiques clés (poids, montage Tubeless, largeur) des deux pneus pour vous aider à décider.

---

## ⚙️ Étape 4 : Personnaliser votre Profil

L'exactitude des calculs dépend des informations de votre profil. Rendez-vous sur votre page [Mon Profil](file:///C:/Users/Guillaume/PhpstormProjects/michelin/resources/views/pages/%E2%9A%A1profile.blade.php) pour ajuster :

* **Votre poids total** (cycliste + vélo) : Un cycliste plus lourd augmente l'usure physique du pneu arrière.
* **Votre style de pilotage** : Choisissez entre `Endurance` (conduite souple), `Mixte` ou `Agressif` (freinages tardifs, relances fortes) pour affiner les coefficients d'usure.
* **Votre segment dominant** : Si l'IA détecte automatiquement un profil *Gravel* mais que vous préférez forcer un profil *Route*, vous pouvez modifier et verrouiller ce paramètre.
