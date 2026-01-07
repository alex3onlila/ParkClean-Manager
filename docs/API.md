# API Documentation - ParkClean Manager

## Overview

L'API REST de ParkClean Manager permet de gérer les clients, véhicules, entrées, abonnements et paiements.

**Base URL:** `http://localhost:8000/api`

---

## Format des Réponses

### Succès
```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { ... },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### Erreur
```json
{
  "success": false,
  "error": "Message d'erreur",
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### Erreur de Validation (422)
```json
{
  "success": false,
  "error": "Données invalides",
  "validation_errors": {
    "field": ["Le champ est requis"]
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

---

## Authentication

Les endpoints d'authentification:

### Login
```http
POST /api/auth/login.php
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

### Logout
```http
POST /api/auth/logout.php
```

---

## Clients

### Lister les clients
```http
GET /api/clients/list.php
GET /api/clients/list.php?limit=20&offset=0&sort=name
```

**Réponse:**
```json
{
  "success": true,
  "message": "Opération réussie",
  "data": [
    {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean@email.com",
      "telephone": "0123456789",
      "nbr_vehicules": 2,
      "vehicles_count": 2
    }
  ]
}
```

### Récupérer un client
```http
POST /api/clients/get.php
Content-Type: application/json

{
  "id": 1
}
```

### Créer un client
```http
POST /api/clients/create.php
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean@email.com",
  "telephone": "0123456789"
}
```

### Mettre à jour un client
```http
POST /api/clients/update.php
Content-Type: application/json

{
  "id": 1,
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean.nouveau@email.com"
}
```

### Supprimer un client
```http
POST /api/clients/delete.php
Content-Type: application/json

{
  "id": 1
}
```

---

## Véhicules

### Lister les véhicules
```http
GET /api/vehicles/list.php
```

### Récupérer un véhicule
```http
POST /api/vehicles/get.php
Content-Type: application/json

{
  "id": 1
}
```

### Créer un véhicule
```http
POST /api/vehicles/create.php
Content-Type: application/json

{
  "client_id": 1,
  "marque": "Renault Clio",
  "type_id": 4,
  "immatriculation": "AB-123-CD"
}
```

### Mettre à jour un véhicule
```http
POST /api/vehicles/update.php
Content-Type: application/json

{
  "id": 1,
  "marque": "Renault Mégane"
}
```

### Supprimer un véhicule
```http
POST /api/vehicles/delete.php
Content-Type: application/json

{
  "id": 1
}
```

---

## Types de Véhicules

### Lister les types
```http
GET /api/vehicle_types/list.php
```

### Créer un type
```http
POST /api/vehicle_types/create.php
Content-Type: application/json

{
  "type": "Camionnette",
  "prix_lavage": 1500
}
```

---

## Entrées (Journal)

### Lister les entrées
```http
GET /api/entries/list.php
GET /api/entries/list.php?date=2024-01-15
```

### Créer une entrée
```http
POST /api/entries/create.php
Content-Type: application/json

{
  "vehicle_id": 1,
  "montant_total": 1500,
  "montant_recu": 1500,
  "obs": "Lavage complet"
}
```

### Marquer comme sorti
```http
POST /api/entries/exit.php
Content-Type: application/json

{
  "id": 1
}
```

---

## Abonnements

### Lister les abonnements
```http
GET /api/abonnements/list.php
GET /api/abonnements/list.php?actif=1
```

### Créer un abonnement
```http
POST /api/abonnements/create.php
Content-Type: application/json

{
  "vehicle_id": 1,
  "date_debut": "2024-01-15",
  "date_fin": "2024-02-15",
  "montant_total": 10000,
  "montant_recu": 5000
}
```

### Renouveler un abonnement
```http
POST /api/abonnements/renew.php
Content-Type: application/json

{
  "id": 1,
  "duration": 30,
  "montant_total": 10000
}
```

---

## Paiements

### Lister les paiements
```http
GET /api/payments/list.php
```

### Créer un paiement
```http
POST /api/payments/create.php
Content-Type: application/json

{
  "entry_id": 1,
  "montant": 500,
  "mode_paiement": "cash"
}
```

---

## Codes d'Erreur

| Code | Signification |
|------|---------------|
| 200  | Succès |
| 400  | Mauvaise requête |
| 401  | Non autorisé |
| 404  | Ressource non trouvée |
| 422  | Erreur de validation |
| 500  | Erreur serveur |

---

## Rate Limiting

En production, les endpoints sensibles sont limités:
- **Login:** 5 tentatives/minute
- **API générale:** 100 requêtes/minute

---

## Bonnes Pratiques

1. **Toujours vérifier le code `success`** dans la réponse
2. **Gérer les erreurs** avec le champ `error`
3. **Utiliser HTTPS** en production
4. **Valider les entrées** côté client et serveur
5. **Logger les erreurs** pour le débogage

