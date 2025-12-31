# ParkClean Manager

Petite application PHP + SQLite pour gérer clients, véhicules, entrées et abonnements d'un parking.

## 🚀 Démarrage Rapide

### Installation
```bash
# Cloner le dépôt et naviguer dans le dossier
cd parkclean

# Configuration initiale (vérifie les dépendances, crée la base, peuple les données)
make setup

# OU manuellement:
# 1. Vérifier les dépendances
make deps-check

# 2. Créer la base de données
make db-reset

# 3. Ajouter des données de test
make db-seed

# 4. Démarrer le serveur
make start
```

Le serveur sera accessible sur **http://localhost:8000**

---

## 📁 Structure du Projet

```
parkclean/
├── api/                    # Endpoints API REST
│   ├── auth/              # Authentification
│   ├── clients/           # Gestion des clients
│   ├── vehicles/          # Gestion des véhicules
│   ├── vehicle_types/     # Types de véhicules
│   ├── entries/           # Journal des entrées
│   ├── abonnements/       # Abonnements
│   ├── payments/          # Paiements
│   ├── export/            # Export (PDF, Excel)
│   ├── reports/           # Rapports
│   ├── config/            # Configuration
│   └── utils/             # Utilitaires (réponses API standardisées)
│
├── public/                # Interface utilisateur
│   ├── pages/             # Pages de l'application
│   ├── partials/          # Composants partiels (header, footer)
│   ├── assets/
│   │   ├── css/          # Styles CSS (unifiés et optimisés)
│   │   ├── js/           # Scripts JavaScript (production ready)
│   │   └── images/       # Images statiques
│   └── uploads/          # Fichiers uploadés (véhicules)
│
├── database/             # Base de données SQLite
│   ├── parkclean.db     # Base de données (auto-créée)
│   └── parkclean.sql    # Schéma SQL
│
├── scripts/              # Scripts utilitaires
│   ├── dev.sh           # Scripts Bash de développement
│   └── seed_test_vehicle.php  # Script de seeding
│
├── others/              # Documentation et logs
│   ├── architecture.txt # Architecture du projet
│   └── php_error.log    # Logs PHP
│
├── Makefile             # Commandes Make automatisées
└── README.md            # Ce fichier
```

---

## 🎯 Fonctionnalités

### Module Clients
- Liste paginée avec recherche
- Ajout, modification, suppression
- Photos de profil
- Nombre de véhicules par client

### Module Véhicules
- Gestion CRUD complète
- Association client-véhicule
- Types de véhicules configurables
- Upload de photos
- Recherche par marque, plaque, propriétaire

### Module Journal (Entrées)
- Enregistrement des entrées/sorties
- Calcul automatique des montants
- Historique complet
- Statistiques financières

### Module Abonnements
- Gestion des abonnements
- Suivi des paiements
- Rapports de revenus

---

## ⚙️ Commandes Makefile

### Développement
```bash
make help              # Afficher l'aide
make start             # Démarrer le serveur
make stop              # Arrêter le serveur
make restart           # Redémarrer le serveur
make dev               # Mode développement complet
```

### Base de données
```bash
make db-reset          # Réinitialiser la base
make db-seed           # Ajouter des données de test
make db-backup         # Créer une sauvegarde
make db-restore        # Restaurer une sauvegarde
make db-info           # Informations sur la base
make test-db           # Vérifier l'intégrité
```

### Tests et Qualité
```bash
make test-api          # Tester les endpoints API
make security-check    # Vérifications de sécurité
make stats             # Statistiques du projet
```

### Maintenance
```bash
make clean             # Nettoyer les fichiers temporaires
make clean-all         # Nettoyage complet
make logs              # Afficher les logs
make maintenance       # Maintenance complète
```

### Production
```bash
make prod-prep         # Préparation pour la production
make deps-check        # Vérifier les dépendances
```

---

## 🔧 Scripts de Développement

Le script `scripts/dev.sh` offre les mêmes fonctionnalités que Makefile :

```bash
# Rendre le script exécutable
chmod +x scripts/dev.sh

# Utilisation
./scripts/dev.sh start      # Démarrer le serveur
./scripts/dev.sh db-reset   # Réinitialiser la base
./scripts/dev.sh test-api   # Tester l'API
./scripts/dev.sh help       # Afficher l'aide
```

---

## 📡 API REST

### Endpoints Disponibles

| Module | GET | POST | PUT | DELETE |
|--------|-----|------|-----|--------|
| `/api/clients/` | list, get | create | update | delete |
| `/api/vehicles/` | list, get | create | update | delete |
| `/api/vehicle_types/` | list, get | create | update | delete |
| `/api/entries/` | list, get | create | update | delete |
| `/api/abonnements/` | list, get | create | update | delete |
| `/api/payments/` | list, get | create | update | delete |
| `/api/auth/` | - | login, logout | - | - |

### Format de Réponse Standardisé

```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { ... },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### Réponse d'Erreur
```json
{
  "success": false,
  "error": "Message d'erreur",
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

---

## 🎨 Design et UX

### Design Unifié
- **CSS optimisé** : Variables CSS centralisées, supprimés les doublons
- **Glassmorphism** : Design moderne avec effets de transparence
- **Responsive** : Adaptation mobile complète
- **Icônes** : Bootstrap Icons

### JavaScript Optimisé
- **Pas de logs en production** : Système de logging conditionnel
- **Fonctions réutilisables** : Renderers génériques pour les tableaux
- **Meilleure gestion des erreurs** : Notifications et feedback utilisateur

---

## 🛡️ Sécurité

- **Validation des entrées** : Sanitisation et validation côté serveur
- **Headers de sécurité** : Protection XSS, CSRF, clickjacking
- **Requêtes préparées** : Protection contre les injections SQL
- **Authentification** : Gestion de session sécurisée

### Vérifications de Sécurité
```bash
make security-check
```

---

## 📊 Optimisations Effectuées

### CSS
- Variables CSS centralisées pour cohérence
- Suppression des doublons massifs
- Classes unifiées pour tableaux, modals, formulaires
- Responsive design optimisé

### JavaScript
- Suppression des console.log en production
- Logger conditionnel (`DEV_MODE`)
- Amélioration de la gestion des erreurs API
- Standardisation des renderers de tableaux

### API
- Format de réponse standardisé
- Gestion d'erreurs centralisée
- Meilleure documentation des erreurs

### DX (Developer Experience)
- Makefile complet pour l'automatisation
- Scripts de développement
- Commandes de maintenance
- Backup/restore de la base

---

## 🔧 Dépannage

### Le serveur ne démarre pas
```bash
# Vérifier que le port 8000 est libre
lsof -i :8000

# Sioccupied, arrêter le processus
kill <PID>
```

### Base de données corrompue
```bash
make db-reset
make db-seed
```

### Erreur de permissions
```bash
# Rendre les scripts exécutables
chmod +x scripts/dev.sh

# Vérifier les permissions de la base
chmod 666 database/parkclean.db
```

### Logs d'erreur
```bash
make logs
# Ou directement
tail -f others/php_error.log
```

---

## 📝 Licence

Ce projet est open source et disponible sous licence MIT.

---

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche feature
3. Soumettre une pull request

Pour toute question ou suggestion, ouvrez une issue sur le dépôt.

