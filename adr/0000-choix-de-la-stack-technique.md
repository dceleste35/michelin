# ADR 0000 : Choix de la stack technique et de l'infrastructure

* **Statut** : Accepté
* **Date** : 2026-06-15
* **Auteur** : Guillaume

---

## Contexte et Problématique

Pour le développement d'une application fonctionnelle en 5 jours lors du Hackathon, nous devons maximiser notre vélocité de livraison tout en garantissant un résultat de qualité de niveau industriel et présentable. Une adaptation mobile nécessite l'usage ponctuel de NativePHP.

L'équipe possède une expertise confirmée en PHP/Laravel.

---

## Options Envisagées

### Option A : Java (Spring Boot) + Angular 
* **Pourquoi cette réflexion** : Il s'agit d'une stack standard en entreprise. De plus, le design system de Michelin propose une bibliothèque de composants sous Angular.
* **Pourquoi le rejet** :
  - **Compétence technique déséquilibrée** : Seul un membre de l'équipe maîtrise à la fois Java et Angular. Le reste de l'équipe ne maîtrise aucun des deux, ce qui aurait créé un goulot d'étranglement majeur.
  - **Inaccessibilité des assets** : La bibliothèque de composants Michelin n'est pas publique, rendant son utilisation impossible dans le cadre du hackathon.
  - **Courbe d'apprentissage** : La courbe d'apprentissage sur 5 jours aurait lourdement compromis la livraison finale.

### Option B : Laravel (PHP) + Livewire / Tailwind CSS + NativePHP
* **Pourquoi ce choix** :
  - **Expertise collective** : Toute l'équipe maîtrise PHP et Laravel, garantissant une productivité maximale dès le premier jour.
  - **NativePHP** : Permet d'interagir rapidement avec les couches bas niveau pour le mobile sans la lourdeur d'un framework Java.
  - **Laravel Cloud** : Zéro gestion d'infrastructure (pas de serveurs à configurer), idéal pour le format court d'un hackathon.

---

## Décision

Nous utiliserons **Laravel 13** (Backend/Frontend) couplé à **NativePHP** (pour l'adaptation mobile), déployé sur **Laravel Cloud**. La base de données retenue est **PostgreSQL**.

---

## Architecture Technique Retenue

| Composant | Technologie | Justification |
| --- | --- | --- |
| **Backend** | Laravel 13 | Expertise collective de l'équipe. |
| **Frontend** | Blade / Livewire | Intégration rapide avec Laravel sans API complexe. |
| **Mobile** | NativePHP | Adaptation légère et rapide. |
| **Base de données** | PostgreSQL | Maîtrise totale par l'équipe, standard entreprise. |
| **Déploiement** | Laravel Cloud | Rapidité de déploiement, zéro DevOps. |

---

## Conséquences

### Positives
* **Vitesse** : Aucun temps d'apprentissage nécessaire sur la stack principale.
* **Agilité** : PostgreSQL permet une transition fluide vers des environnements d'entreprise réels après le hackathon.
* **Focus** : 100% du temps dédié à la valeur métier.

### Risques et Atténuations
* **Non-alignement avec le design system Michelin** : Puisque la librairie Angular est inaccessible, nous devrons créer des composants "maison" rapides en Tailwind pour maintenir une identité visuelle cohérente et soignée.
