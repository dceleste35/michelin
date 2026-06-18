---
marp: true
theme: default
class: lead
paginate: true
backgroundColor: #1e1e2e
textColor: #cdd6f4
---

# **RideReady**
### Suivi d'usure intelligent & Recommandations de pneus Michelin par IA

*Hackathon Michelin 2026*  
Présenté par l'équipe projet

---

## 🛑 Le Problème : L'angle mort du cycliste

- **Difficile d'évaluer l'usure** : L'usure d'un pneu n'est pas linéaire et dépend des terrains (route, boue, cailloux).
- **Sécurité en jeu** : Un pneu arrière usé à l'excès glisse, et un pneu avant usé provoque la chute.
- **Surconsommation ou gâchis** : Remplacement trop précoce (perte d'argent) ou trop tardif (danger).
- **Manque de conseils personnalisés** : Comment choisir le bon pneu de rechange parmi les dizaines de références du catalogue Michelin ?

---

## 💡 La Solution : RideReady

Une application intelligente connectée à **Strava** qui :

1. **Mesure l'usure réelle (SCORE)** : Traduit vos kilomètres Strava en usure physique pondérée par le terrain, votre poids et votre style.
2. **Prévient en temps opportun** : Alertes visuelles claires dès que vos pneus entrent en zone de fatigue critique.
3. **Recommande avec précision (RAG + LLM)** : Compare votre monte actuelle au catalogue officiel Michelin pour proposer le pneu idéal avec une justification argumentée par IA.

---

## ⚙️ Comment ça marche ? (Architecture)

```
[ Strava API ] ──> Importation des activités (distance, vitesse, dénivelé)
                        │
                        ▼
[ Inférence ]   ──> Déduction de la surface (Asphalte, Mixed, Mud...)
                        │
                        ▼
[ Algorithme ]  ──> Usure cumulée corrigée par poids et style de pilotage
                        │
                        ▼ (Si usure >= 85%)
[ RAG + LLM ]   ──> Recherche vectorielle pgvector sur le catalogue Michelin
                ──> Génération d'un comparatif technique par Claude 3.5
```

---

## 🚴 Scénario de Démo : Le cas de Marc

- **Marc**, cycliste Gravel passionné (90 kg).
- **Usage sur 6 mois** : 80 activités importées automatiquement de Strava.
- **Verdict d'usure** :
  - Pneu avant : **72%** (Usure modérée)
  - Pneu arrière : **86%** (Usure critique &mdash; Alerte déclenchée !)
- **Recommandation Michelin** : Montée en gamme vers le **MICHELIN Power Gravel RS Racing Line** pour un gain de rendement optimal sur sol mixte, justifié techniquement par notre assistant IA.

---

## 🚀 Perspectives & Impact Business

- **Fidélisation Client** : Michelin devient un compagnon quotidien du cycliste, pas seulement une marque à l'achat.
- **Conversion E-Commerce** : Lien direct "Un clic pour acheter" vers les revendeurs agréés Michelin ou la boutique officielle au moment précis du besoin.
- **Collecte de Données (RGPD)** : Analyse anonymisée de la durée de vie réelle des pneus sur le terrain pour améliorer la R&D Michelin.
- **Extension B2B** : Intégration possible avec les flottes de vélos en libre-service ou les loueurs professionnels.

---

# **Merci pour votre attention !**
### Des questions ?

*Retrouvez la documentation sur : [RideReady README.md](file:///C:/Users/Guillaume/PhpstormProjects/michelin/README.md)*
