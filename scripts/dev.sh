#!/bin/bash
# ParkClean Manager - Scripts de développement
# Professionnalisé et corrigé

set -e

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="/home/alex/Dev/Projets Personnel/parkclean"
DB_FILE="$PROJECT_ROOT/database/parkclean.db"
SQL_FILE="$PROJECT_ROOT/database/parkclean.sql"
BACKUP_DIR="$PROJECT_ROOT/backups"
LOGS_DIR="$PROJECT_ROOT/logs"

# Fonction d'aide
show_help() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           ParkClean Manager - Scripts de Développement    ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${GREEN}Usage:${NC} $0 [command]"
    echo ""
    echo -e "${GREEN}Commands:${NC}"
    echo "  start       Démarrer le serveur de développement"
    echo "  stop        Arrêter le serveur de développement"
    echo "  restart     Redémarrer le serveur de développement"
    echo "  db-reset    Réinitialiser la base de données"
    echo "  db-seed     Peupler la base avec des données de test"
    echo "  db-backup   Créer une sauvegarde de la base"
    echo "  db-restore  Restaurer la base depuis une sauvegarde"
    echo "  db-info     Afficher les informations de la base"
    echo "  test-api    Tester les endpoints API"
    echo "  test        Exécuter les tests unitaires"
    echo "  clean       Nettoyer les fichiers temporaires"
    echo "  logs        Afficher les logs du serveur"
    echo "  help        Afficher cette aide"
    echo ""
    echo -e "${GREEN}Options:${NC}"
    echo "  -h, --help  Afficher cette aide"
}

# Fonction pour afficher un message
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    log_info "Vérification des prérequis..."
    
    local missing=()
    
    if ! command -v php &> /dev/null; then
        missing+=("PHP")
    fi
    
    if ! command -v sqlite3 &> /dev/null; then
        missing+=("SQLite3")
    fi
    
    if ! command -v curl &> /dev/null; then
        missing+=("cURL")
    fi
    
    if [ ${#missing[@]} -ne 0 ]; then
        log_error "Prérequis manquants: ${missing[*]}"
        exit 1
    fi
    
    log_success "Tous les prérequis sont installés"
}

# Fonction pour vérifier que le serveur répond
wait_for_server() {
    local max_attempts=10
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -s http://localhost:8000 > /dev/null 2>&1; then
            return 0
        fi
        sleep 1
        ((attempt++))
    done
    
    return 1
}

# Fonction pour démarrer le serveur
start_server() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Démarrage du serveur ParkClean                  ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    check_prerequisites
    
    # Vérifier si un serveur est déjà en cours
    if lsof -ti:8000 >/dev/null 2>&1; then
        log_warning "Un serveur est déjà en cours sur le port 8000"
        read -p "Voulez-vous l'arrêter et redémarrer ? (o/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Oo]$ ]]; then
            stop_server
            sleep 2
        else
            return
        fi
    fi
    
    cd "$PROJECT_ROOT"
    
    echo ""
    log_info "Serveur disponible sur: ${GREEN}http://localhost:8000${NC}"
    log_info "Appuyez sur ${YELLOW}Ctrl+C${NC} pour arrêter"
    echo ""
    
    php -S localhost:8000 -t public
}

# Fonction pour arrêter le serveur
stop_server() {
    log_info "Arrêt du serveur..."
    
    # Trouver et tuer le processus PHP
    local pids=$(lsof -ti:8000 2>/dev/null || true)
    
    if [ -n "$pids" ]; then
        echo "$pids" | xargs kill -9 2>/dev/null || true
        log_success "Serveur arrêté"
    else
        log_warning "Aucun serveur trouvé sur le port 8000"
    fi
}

# Fonction pour réinitialiser la base
db_reset() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Réinitialisation de la base de données          ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    if [ ! -f "$SQL_FILE" ]; then
        log_error "Fichier SQL non trouvé: $SQL_FILE"
        exit 1
    fi
    
    # Sauvegarde avant reset
    if [ -f "$DB_FILE" ]; then
        local backup_file="$DB_FILE.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$DB_FILE" "$backup_file"
        log_info "Sauvegarde créée: $backup_file"
    fi
    
    # Suppression de l'ancienne base
    rm -f "$DB_FILE"
    
    log_info "Création de la nouvelle base de données..."
    
    # Créer le répertoire database si nécessaire
    mkdir -p "$(dirname "$DB_FILE")"
    
    # Exécution du script SQL avec sqlite3
    if command -v sqlite3 &> /dev/null; then
        sqlite3 "$DB_FILE" < "$SQL_FILE"
    else
        # Fallback: utiliser PHP
        php -r "
            \$pdo = new PDO('sqlite:$DB_FILE');
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$sql = file_get_contents('$SQL_FILE');
            \$pdo->exec(\$sql);
            echo 'Base de données créée avec succès';
        "
    fi
    
    # Définir les permissions
    chmod 666 "$DB_FILE" 2>/dev/null || true
    
    log_success "Base de données réinitialisée avec succès"
}

# Fonction pour peupler la base
db_seed() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Population de la base avec données de test     ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    if [ ! -f "$DB_FILE" ]; then
        log_error "Base de données non trouvée. Exécutez d'abord: $0 db-reset"
        exit 1
    fi
    
    log_info "Ajout des données de test..."
    
    # Exécution du script de seed si disponible
    if [ -f "$PROJECT_ROOT/scripts/seed_test_vehicle.php" ]; then
        cd "$PROJECT_ROOT"
        php scripts/seed_test_vehicle.php
        log_success "Données de test ajoutées via seed_test_vehicle.php"
    else
        # Création de données basiques via SQLite
        sqlite3 "$DB_FILE" << 'EOF'
-- Insertion de types de véhicules (si pas déjà présents)
INSERT OR IGNORE INTO vehicle_types (id, type, prix_lavage) VALUES 
(1, 'Moto/Kavaki', 500),
(2, 'Kavaki pleine', 1000),
(3, 'Taxi', 700),
(4, 'Voiture Personnelle', 1000),
(5, '4x4', 1500),
(6, 'Bus', 1000),
(7, 'Coaster', 2000),
(8, 'Camion Léger', 2000),
(9, 'Camion Lourd', 5000);

-- Insertion de clients de test
INSERT OR IGNORE INTO clients (id, nom, prenom, email, telephone, nbr_vehicules) VALUES 
(1, 'Dupont', 'Jean', 'jean.dupont@email.com', '0123456789', 2),
(2, 'Martin', 'Marie', 'marie.martin@email.com', '0234567890', 1),
(3, 'Bernard', 'Pierre', 'pierre.bernard@email.com', '0345678901', 3);

-- Insertion de véhicules de test
INSERT OR IGNORE INTO vehicles (id, client_id, marque, type_id, immatriculation) VALUES 
(1, 1, 'Renault Clio', 4, 'AB-123-CD'),
(2, 1, 'Yamaha MT-07', 1, 'IJ-789-KL'),
(3, 2, 'Peugeot 208', 4, 'EF-456-GH'),
(4, 3, 'Toyota Hilux', 5, 'GH-789-IJ'),
(5, 3, 'Ford Transit', 8, 'KL-012-MN');
EOF
        
        log_success "Données basiques ajoutées"
    fi
    
    # Afficher les statistiques
    db_info
}

# Fonction de sauvegarde
db_backup() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Sauvegarde de la base de données                ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    if [ ! -f "$DB_FILE" ]; then
        log_error "Base de données non trouvée"
        exit 1
    fi
    
    mkdir -p "$BACKUP_DIR"
    
    local backup_file="$BACKUP_DIR/parkclean_backup_$(date +%Y%m%d_%H%M%S).db"
    
    cp "$DB_FILE" "$backup_file"
    
    log_success "Sauvegarde créée: $backup_file"
    
    # Afficher la taille
    local size=$(du -h "$backup_file" | cut -f1)
    log_info "Taille de la sauvegarde: $size"
}

# Fonction de restauration
db_restore() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Restauration de la base de données              ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    echo -e "${YELLOW}Fichiers de sauvegarde disponibles:${NC}"
    ls -la "$BACKUP_DIR"/ 2>/dev/null || echo "Aucune sauvegarde trouvée dans $BACKUP_DIR"
    
    echo ""
    read -p "Entrez le nom du fichier à restaurer: " backup_name
    
    if [ -z "$backup_name" ]; then
        log_error "Nom de fichier requis"
        exit 1
    fi
    
    local backup_file="$BACKUP_DIR/$backup_name"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Fichier de sauvegarde non trouvé: $backup_file"
        exit 1
    fi
    
    # Sauvegarde de la base actuelle
    if [ -f "$DB_FILE" ]; then
        local current_backup="$DB_FILE.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$DB_FILE" "$current_backup"
        log_info "Base actuelle sauvegardée: $current_backup"
    fi
    
    # Restauration
    cp "$backup_file" "$DB_FILE"
    
    log_success "Base de données restaurée avec succès"
}

# Fonction d'information sur la base
db_info() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Informations sur la base de données             ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    if [ ! -f "$DB_FILE" ]; then
        log_error "Base de données non trouvée: $DB_FILE"
        echo "Exécutez '$0 db-reset' pour la créer"
        exit 1
    fi
    
    echo ""
    log_info "Fichier: $DB_FILE"
    
    local size=$(du -h "$DB_FILE" | cut -f1)
    log_info "Taille: $size"
    
    echo ""
    echo -e "${YELLOW}Tables:${NC}"
    sqlite3 "$DB_FILE" ".tables" | tr ' ' '\n'
    
    echo ""
    echo -e "${YELLOW}Nombre d'enregistrements:${NC}"
    for table in $(sqlite3 "$DB_FILE" ".tables"); do
        local count=$(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM $table;")
        printf "  %-20s: %d\n" "$table" "$count"
    done
}

# Fonction de test API
test_api() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Test des endpoints API                          ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    log_info "Vérification du serveur..."
    
    # Vérification du serveur
    if ! curl -s http://localhost:8000 >/dev/null 2>&1; then
        log_error "Serveur non démarré. Exécutez: $0 start"
        exit 1
    fi
    
    log_success "Serveur en cours"
    
    echo ""
    
    # Test des endpoints
    local endpoints=(
        "/api/routes.php"
        "/api/clients/list.php"
        "/api/vehicles/list.php"
        "/api/vehicle_types/list.php"
        "/api/entries/list.php"
        "/api/abonnements/list.php"
    )
    
    for endpoint in "${endpoints[@]}"; do
        echo -e "\n${YELLOW}Test: $endpoint${NC}"
        local response=$(curl -s "http://localhost:8000$endpoint" | head -c 200)
        
        if [ -n "$response" ]; then
            echo "$response" | jq . 2>/dev/null || echo "$response"
            log_success "OK"
        else
            log_error "Erreur ou vide"
        fi
    done
}

# Fonction de test unitaire
test() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Tests unitaires                                 ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    if [ ! -d "$PROJECT_ROOT/tests" ]; then
        log_warning "Aucun dossier de tests trouvé"
        exit 0
    fi
    
    if ! command -v phpunit &> /dev/null; then
        log_error "PHPUnit non installé"
        exit 1
    fi
    
    cd "$PROJECT_ROOT"
    phpunit
}

# Fonction de nettoyage
clean() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Nettoyage des fichiers temporaires              ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    log_info "Nettoyage en cours..."
    
    # Suppression des fichiers temporaires PHP
    find "$PROJECT_ROOT" -name "*.tmp" -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name "*.log" -type f -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name ".DS_Store" -delete 2>/dev/null || true
    find "$PROJECT_ROOT" -name "Thumbs.db" -delete 2>/dev/null || true
    
    # Nettoyer les sessions PHP expirées
    find /tmp -name "sess_*" -mtime +1 -delete 2>/dev/null || true
    
    # Nettoyer les logs si demandés
    if [ "$1" == "--logs" ]; then
        rm -rf "$LOGS_DIR"/* 2>/dev/null || true
        log_info "Logs nettoyés"
    fi
    
    log_success "Nettoyage terminé"
}

# Fonction pour afficher les logs
show_logs() {
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           Logs du serveur                                 ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
    
    local log_file="$LOGS_DIR/parkclean-$(date +%Y-%m-%d).log"
    
    if [ ! -f "$log_file" ]; then
        log_warning "Fichier de log non trouvé: $log_file"
        echo "Les logs sont dans: $LOGS_DIR"
        ls -la "$LOGS_DIR"/ 2>/dev/null || true
        exit 0
    fi
    
    if command -v tail &> /dev/null; then
        tail -f "$log_file"
    else
        cat "$log_file"
    fi
}

# Fonction principale
main() {
    local command="${1:-help}"
    
    # Enlever le tiret si présent
    if [[ "$command" == -* ]]; then
        command="help"
    fi
    
    case "$command" in
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
        "db-info")
            db_info
            ;;
        "test-api")
            test_api
            ;;
        "test")
            test
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

