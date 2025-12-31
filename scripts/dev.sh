#!/bin/bash
# ParkClean Manager - Scripts de développement

set -e

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/alex/Dev/Projets Personnel/parkclean"
DB_FILE="$PROJECT_ROOT/database/parkclean.db"

# Fonction d'aide
show_help() {
    echo -e "${BLUE}ParkClean Manager - Scripts de développement${NC}"
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  start       Démarrer le serveur de développement"
    echo "  stop        Arrêter le serveur de développement"
    echo "  restart     Redémarrer le serveur de développement"
    echo "  db-reset    Réinitialiser la base de données"
    echo "  db-seed     Peupler la base avec des données de test"
    echo "  db-backup   Créer une sauvegarde de la base"
    echo "  db-restore  Restaurer la base depuis une sauvegarde"
    echo "  test-api    Tester les endpoints API"
    echo "  clean       Nettoyer les fichiers temporaires"
    echo "  logs        Afficher les logs du serveur"
    echo "  help        Afficher cette aide"
    echo ""
}

# Fonction pour démarrer le serveur
start_server() {
    echo -e "${GREEN}Démarrage du serveur ParkClean...${NC}"
    cd "$PROJECT_ROOT"
    
    # Vérifier si un serveur est déjà en cours
    if lsof -ti:8000 >/dev/null 2>&1; then
        echo -e "${YELLOW}Un serveur est déjà en cours sur le port 8000${NC}"
        read -p "Voulez-vous l'arrêter et redémarrer ? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            stop_server
        else
            return
        fi
    fi
    
    echo -e "${BLUE}Serveur disponible sur: http://localhost:8000${NC}"
    echo -e "${BLUE}Appuyez sur Ctrl+C pour arrêter${NC}"
    
    php -S localhost:8000 -t public
}

# Fonction pour arrêter le serveur
stop_server() {
    echo -e "${YELLOW}Arrêt du serveur...${NC}"
    pkill -f "php -S localhost:8000" || echo -e "${RED}Aucun serveur trouvé${NC}"
}

# Fonction pour réinitialiser la base
db_reset() {
    echo -e "${YELLOW}Réinitialisation de la base de données...${NC}"
    
    if [ ! -f "$PROJECT_ROOT/database/parkclean.sql" ]; then
        echo -e "${RED}Fichier SQL non trouvé: $PROJECT_ROOT/database/parkclean.sql${NC}"
        exit 1
    fi
    
    # Sauvegarde avant reset
    if [ -f "$DB_FILE" ]; then
        backup_file="$DB_FILE.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$DB_FILE" "$backup_file"
        echo -e "${BLUE}Sauvegarde créée: $backup_file${NC}"
    fi
    
    # Suppression de l'ancienne base
    rm -f "$DB_FILE"
    
    # Création de la nouvelle base
    sqlite3 "$DB_FILE" < "$PROJECT_ROOT/database/parkclean.sql"
    
    echo -e "${GREEN}Base de données réinitialisée avec succès${NC}"
}

# Fonction pour peupler la base
db_seed() {
    echo -e "${GREEN}Population de la base avec des données de test...${NC}"
    
    if [ ! -f "$DB_FILE" ]; then
        echo -e "${RED}Base de données non trouvée. Exécutez d'abord: $0 db-reset${NC}"
        exit 1
    fi
    
    # Exécution du script de seed si disponible
    if [ -f "$PROJECT_ROOT/scripts/seed_test_vehicle.php" ]; then
        cd "$PROJECT_ROOT"
        php scripts/seed_test_vehicle.php
        echo -e "${GREEN}Données de test ajoutées avec succès${NC}"
    else
        echo -e "${YELLOW}Script de seed non trouvé, création de données basiques...${NC}"
        
        # Création de données basiques via SQLite
        sqlite3 "$DB_FILE" << EOF
-- Insertion de types de véhicules
INSERT OR IGNORE INTO vehicle_types (id, type, prix_lavage) VALUES 
(1, 'Voiture', 15.00),
(2, 'Moto', 10.00),
(3, 'Utilitaire', 20.00);

-- Insertion de clients de test
INSERT OR IGNORE INTO clients (id, nom, prenom, email, telephone) VALUES 
(1, 'Dupont', 'Jean', 'jean.dupont@email.com', '0123456789'),
(2, 'Martin', 'Marie', 'marie.martin@email.com', '0234567890'),
(3, 'Bernard', 'Pierre', 'pierre.bernard@email.com', '0345678901');

-- Insertion de véhicules de test
INSERT OR (id, client IGNORE INTO vehicles_id, marque, immatriculation, type_id) VALUES 
(1, 1, 'Renault Clio', 'AB-123-CD', 1),
(2, 2, 'Peugeot 208', 'EF-456-GH', 1),
(3, 1, 'Yamaha MT-07', 'IJ-789-KL', 2);
EOF
        echo -e "${GREEN}Données basiques ajoutées${NC}"
    fi
}

# Fonction de sauvegarde
db_backup() {
    if [ ! -f "$DB_FILE" ]; then
        echo -e "${RED}Base de données non trouvée${NC}"
        exit 1
    fi
    
    backup_file="$PROJECT_ROOT/backups/parkclean_backup_$(date +%Y%m%d_%H%M%S).db"
    mkdir -p "$PROJECT_ROOT/backups"
    
    cp "$DB_FILE" "$backup_file"
    echo -e "${GREEN}Sauvegarde créée: $backup_file${NC}"
}

# Fonction de restauration
db_restore() {
    echo -e "${YELLOW}Fichiers de sauvegarde disponibles:${NC}"
    ls -la "$PROJECT_ROOT/backups/"*.db 2>/dev/null || echo "Aucune sauvegarde trouvée"
    
    read -p "Entrez le nom du fichier à restaurer: " backup_name
    backup_file="$PROJECT_ROOT/backups/$backup_name"
    
    if [ ! -f "$backup_file" ]; then
        echo -e "${RED}Fichier de sauvegarde non trouvé: $backup_file${NC}"
        exit 1
    fi
    
    # Sauvegarde de la base actuelle
    if [ -f "$DB_FILE" ]; then
        current_backup="$DB_FILE.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$DB_FILE" "$current_backup"
        echo -e "${BLUE}Base actuelle sauvegardée: $current_backup${NC}"
    fi
    
    # Restauration
    cp "$backup_file" "$DB_FILE"
    echo -e "${GREEN}Base de données restaurée avec succès${NC}"
}

# Fonction de test API
test_api() {
    echo -e "${GREEN}Test des endpoints API...${NC}"
    
    # Vérification du serveur
    if ! curl -s http://localhost:8000 >/dev/null 2>&1; then
        echo -e "${RED}Serveur non démarré. Exécutez: $0 start${NC}"
        exit 1
    fi
    
    echo -e "${BLUE}Test de l'endpoint des routes...${NC}"
    curl -s http://localhost:8000/api/routes.php | jq '.' 2>/dev/null || curl -s http://localhost:8000/api/routes.php
    
    echo -e "${BLUE}Test de l'endpoint des véhicules...${NC}"
    curl -s http://localhost:8000/api/vehicles/list.php | jq '.' 2>/dev/null || curl -s http://localhost:8000/api/vehicles/list.php
    
    echo -e "${BLUE}Test de l'endpoint des clients...${NC}"
    curl -s http://localhost:8000/api/clients/list.php | jq '.' 2>/dev/null || curl -s http://localhost:8000/api/clients/list.php
}

# Fonction de nettoyage
clean() {
    echo -e "${GREEN}Nettoyage des fichiers temporaires...${NC}"
    
    # Suppression des fichiers temporaires
    find "$PROJECT_ROOT" -name "*.tmp" -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name "*.log" -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name ".DS_Store" -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name "Thumbs.db" -delete 2>/dev/null || true
    
    # Suppression des sessions PHP expirées
    find /tmp -name "sess_*" -mtime +1 -delete 2>/dev/null || true
    
    echo -e "${GREEN}Nettoyage terminé${NC}"
}

# Fonction des logs
show_logs() {
    echo -e "${BLUE}Logs du serveur PHP (Ctrl+C pour quitter):${NC}"
    tail -f "$PROJECT_ROOT/others/php_error.log" 2>/dev/null || echo "Fichier de log non trouvé"
}

# Fonction principale
main() {
    case "${1:-help}" in
        "start")
            start_server
            ;;
        "stop")
            stop_server
            ;;
        "restart")
            stop_server
            sleep 2
            start_server
            ;;
        "db-reset")
            db_reset
            ;;
        "db-seed")
            db_seed
            ;;
        "db-backup")
            db_backup
            ;;
        "db-restore")
            db_restore
            ;;
        "test-api")
            test_api
            ;;
        "clean")
            clean
            ;;
        "logs")
            show_logs
            ;;
        "help"|"--help"|"-h")
            show_help
            ;;
        *)
            echo -e "${RED}Commande inconnue: $1${NC}"
            show_help
            exit 1
            ;;
    esac
}

# Exécution
main "$@"
