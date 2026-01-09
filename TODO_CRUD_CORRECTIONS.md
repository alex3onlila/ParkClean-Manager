# TODO - Corrections CRUD

## Objectif
Corriger les API vehicles pour qu'elles fonctionnent correctement avec le JavaScript.

## Problème identifié
Les API vehicles utilisaient `$_POST` directement au lieu de `getInput()`, ce qui causait des problèmes de lecture des données JSON envoyées par le JavaScript.

## Corrections effectuées

### ✅ api/vehicles/create.php
- Ajout de `require_once "../config/input.php";`
- Utilisation de `getInput()` pour récupérer les données
- Suppression de la logique UPDATE intégrée (maintenant uniquement CREATE)
- Validation des champs obligatoires
- Formatage correct des données (trim, uppercase)

### ✅ api/vehicles/update.php
- Ajout de `require_once "../config/input.php";`
- Utilisation de `getInput()` pour récupérer les données
- Conservation de la gestion des uploads d'images
- Formatage correct des données

## État des fichiers JavaScript

| Fichier | CRUD | Notifications | Statut |
|---------|------|---------------|--------|
| clients.js | ✅ Complet | ✅ ParkCleanAPI | OK |
| vehicles.js | ✅ Complet | ✅ ParkCleanAPI | OK (corrigé) |
| daily.js | ✅ Complet | ✅ ParkCleanAPI | OK |
| subscription.js | ✅ Complet | ✅ ParkCleanAPI | OK |
| dashboard.js | ✅ Complet | ✅ ParkCleanAPI | OK |

## À vérifier
- Tester l'ajout de véhicules
- Tester la modification de véhicules
- Tester la suppression de véhicules
- Tester l'upload d'images

## Notes
Le JavaScript utilise `FormData` pour l'envoi des données (avec support des images). Les API corrigées utilisent `getInput()` qui supporte à la fois JSON et FormData.

