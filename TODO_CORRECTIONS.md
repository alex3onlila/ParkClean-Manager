# TODO - Corrections des revenus mensuels et profil header

## Tâches terminées:
- [x] Analyser le code existant
- [x] Créer le plan de correction
- [x] Corriger loadMonthlySummary() pour distribuer les abonnements par mois
- [x] Améliorer la gestion du profil dans header.php

## Modifications effectuées:

### 1. dashboard.js - loadMonthlySummary()
**Avant:** Les abonnements étaient ajoutés SEULEMENT au mois actuel (bug)
**Après:** Chaque abonnement est ajouté au mois correspondant à sa `date_debut`
- Extraction du mois à partir de `date_debut` (format YYYY-MM-DD ou YYYY-MM)
- Vérification que `date_debut` existe avant traitement

### 2. header.php - Gestion du profil
**Améliorations:**
- Ajout d'IDs (`userNameDisplay`, `userEmailDisplay`, `userAvatar`) pour manipulation JS
- Script JavaScript pour charger les données utilisateur via API `/api/users/list.php` si les données de session sont incomplètes
- Meilleure gestion des valeurs par défaut
- Formatage plus propre du code PHP

## Tests recommandés:
1. Vérifier que les revenus mensuels s'affichent correctement pour chaque mois
2. Vérifier que les abonnements apparaissent dans le bon mois (selon date_debut)
3. Vérifier que le profil utilisateur s'affiche correctement dans le header

