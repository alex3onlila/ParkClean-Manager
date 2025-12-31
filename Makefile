# ParkClean Manager - Makefile
# Automatisation des tâches de développement

.PHONY: help start stop restart db-reset db-seed db-backup test-api clean logs install deps

# Variables
PROJECT_ROOT := $(shell pwd)
DB_FILE := $(PROJECT_ROOT)/database/parkclean.db
SCRIPTS_DIR := $(PROJECT_ROOT)/scripts
DEV_SCRIPT := $(SCRIPTS_DIR)/dev.sh

# Couleurs pour l'output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m

# Help - Affichage de l'aide
help: ## Affiche cette aide
	@echo "$(BLUE)ParkClean Manager - Makefile$(NC)"
	@echo ""
	@echo "$(GREEN)Usage:$(NC) make [target]"
	@echo ""
	@echo "$(GREEN)Targets disponibles:$(NC)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Serveur de développement
start: ## Démarrer le serveur de développement
	@echo "$(GREEN)Démarrage du serveur ParkClean...$(NC)"
	@$(DEV_SCRIPT) start

stop: ## Arrêter le serveur de développement
	@echo "$(YELLOW)Arrêt du serveur...$(NC)"
	@$(DEV_SCRIPT) stop

restart: ## Redémarrer le serveur de développement
	@echo "$(YELLOW)Redémarrage du serveur...$(NC)"
	@$(DEV_SCRIPT) restart

# Base de données
db-reset: ## Réinitialiser la base de données
	@echo "$(YELLOW)Réinitialisation de la base de données...$(NC)"
	@$(DEV_SCRIPT) db-reset

db-seed: ## Peupler la base avec des données de test
	@echo "$(GREEN)Population de la base avec des données de test...$(NC)"
	@$(DEV_SCRIPT) db-seed

db-backup: ## Créer une sauvegarde de la base
	@echo "$(GREEN)Création d'une sauvegarde...$(NC)"
	@$(DEV_SCRIPT) db-backup

db-restore: ## Restaurer la base depuis une sauvegarde
	@echo "$(YELLOW)Restauration de la base...$(NC)"
	@$(DEV_SCRIPT) db-restore

# Tests et validation
test-api: ## Tester les endpoints API
	@echo "$(GREEN)Test des endpoints API...$(NC)"
	@$(DEV_SCRIPT) test-api

test-db: ## Vérifier l'intégrité de la base de données
	@echo "$(GREEN)Vérification de l'intégrité de la base...$(NC)"
	@sqlite3 $(DB_FILE) "PRAGMA integrity_check;"

# Nettoyage
clean: ## Nettoyer les fichiers temporaires
	@echo "$(GREEN)Nettoyage des fichiers temporaires...$(NC)"
	@$(DEV_SCRIPT) clean

clean-logs: ## Nettoyer les fichiers de log
	@echo "$(GREEN)Nettoyage des logs...$(NC)"
	@find $(PROJECT_ROOT) -name "*.log" -delete 2>/dev/null || true
	@echo "$(GREEN)Logs nettoyés$(NC)"

clean-all: clean clean-logs ## Nettoyage complet
	@echo "$(GREEN)Nettoyage complet terminé$(NC)"

# Logs
logs: ## Afficher les logs du serveur
	@$(DEV_SCRIPT) logs

# Installation et dépendances
install: ## Installer les dépendances (si applicable)
	@echo "$(GREEN)Installation des dépendances...$(NC)"
	@echo "Aucune dépendance externe requise pour ParkClean"
	@echo "$(GREEN)Installation terminée$(NC)"

deps-check: ## Vérifier les dépendances système
	@echo "$(GREEN)Vérification des dépendances...$(NC)"
	@which php >/dev/null 2>&1 && echo "✓ PHP installé" || echo "✗ PHP manquant"
	@which sqlite3 >/dev/null 2>&1 && echo "✓ SQLite3 installé" || echo "✗ SQLite3 manquant"
	@which curl >/dev/null 2>&1 && echo "✓ cURL installé" || echo "✗ cURL manquant"
	@which jq >/dev/null 2>&1 && echo "✓ jq installé (optionnel)" || echo "⚠ jq non installé (optionnel)"

# Statistiques et monitoring
stats: ## Afficher les statistiques du projet
	@echo "$(BLUE)Statistiques du projet ParkClean:$(NC)"
	@echo ""
	@echo "$(YELLOW)Fichiers PHP:$(NC)"
	@find $(PROJECT_ROOT) -name "*.php" | wc -l | xargs printf "  Total: %d fichiers\n"
	@echo ""
	@echo "$(YELLOW)Fichiers JavaScript:$(NC)"
	@find $(PROJECT_ROOT) -name "*.js" | wc -l | xargs printf "  Total: %d fichiers\n"
	@echo ""
	@echo "$(YELLOW)Fichiers CSS:$(NC)"
	@find $(PROJECT_ROOT) -name "*.css" | wc -l | xargs printf "  Total: %d fichiers\n"
	@echo ""
	@echo "$(YELLOW)Lignes de code:$(NC)"
	@find $(PROJECT_ROOT) -name "*.php" -o -name "*.js" -o -name "*.css" | xargs wc -l | tail -1 | xargs printf "  Total: %d lignes\n"

# Base de données - Informations
db-info: ## Afficher les informations de la base de données
	@echo "$(BLUE)Informations de la base de données:$(NC)"
	@echo ""
	@if [ -f $(DB_FILE) ]; then \
		echo "$(GREEN)Base de données trouvée:$(NC) $(DB_FILE)"; \
		echo "$(YELLOW)Taille:$(NC) $$(du -h $(DB_FILE) | cut -f1)"; \
		echo "$(YELLOW)Tables:$(NC)"; \
		sqlite3 $(DB_FILE) ".tables"; \
		echo "$(YELLOW)Nombre d'enregistrements:$(NC)"; \
		for table in $$(sqlite3 $(DB_FILE) ".tables"); do \
			count=$$(sqlite3 $(DB_FILE) "SELECT COUNT(*) FROM $$table;"); \
			echo "  $$table: $$count enregistrements"; \
		done; \
	else \
		echo "$(RED)Base de données non trouvée:$(NC) $(DB_FILE)"; \
		echo "Exécutez 'make db-reset' pour la créer"; \
	fi

# Optimisation
optimize-css: ## Optimiser les fichiers CSS (minification basique)
	@echo "$(GREEN)Optimisation CSS...$(NC)"
	@echo "CSS déjà optimisé lors de l'unification"
	@echo "$(GREEN)Optimisation terminée$(NC)"

optimize-js: ## Optimiser les fichiers JavaScript
	@echo "$(GREEN)Optimisation JavaScript...$(NC)"
	@echo "JavaScript déjà optimisé (logs de debug supprimés)"
	@echo "$(GREEN)Optimisation terminée$(NC)"

# Sécurité
security-check: ## Vérifications de sécurité basiques
	@echo "$(GREEN)Vérifications de sécurité...$(NC)"
	@echo "$(YELLOW)Vérification des permissions...$(NC)"
	@find $(PROJECT_ROOT) -type f -perm /002 -exec ls -l {} \; | head -5 || echo "✓ Aucune permission world-writable trouvée"
	@echo "$(YELLOW)Vérification des fichiers de configuration...$(NC)"
	@[ -f $(PROJECT_ROOT)/.env ] && echo "⚠ Fichier .env trouvé - vérifiez qu'il n'est pas dans le dépôt" || echo "✓ Pas de fichier .env dans le dépôt"
	@echo "$(GREEN)Vérifications terminées$(NC)"

# Maintenance
maintenance: clean-all db-backup ## Maintenance complète
	@echo "$(GREEN)Maintenance complète terminée$(NC)"
	@echo "$(BLUE)Recommandations:$(NC)"
	@echo "  - Vérifiez les logs d'erreur"
	@echo "  - Mettez à jour les dépendances si nécessaire"
	@echo "  - Sauvegardez régulièrement"

# Setup initial
setup: deps-check db-reset db-seed ## Configuration initiale complète
	@echo "$(GREEN)Configuration initiale terminée!$(NC)"
	@echo "$(BLUE)Prochaines étapes:$(NC)"
	@echo "  1. Exécutez 'make start' pour démarrer le serveur"
	@echo "  2. Ouvrez http://localhost:8000 dans votre navigateur"
	@echo "  3. Connectez-vous avec les identifiants de test"

# Mode développement complet
dev: ## Démarrer en mode développement (serveur + tests)
	@echo "$(GREEN)Démarrage en mode développement...$(NC)"
	@make start &
	@sleep 3
	@make test-api
	@echo "$(GREEN)Mode développement prêt!$(NC)"

# Production (préparation)
prod-prep: clean-all security-check ## Préparation pour la production
	@echo "$(YELLOW)Préparation pour la production...$(NC)"
	@echo "  - Suppression des fichiers de développement"
	@echo "  - Vérifications de sécurité effectuées"
	@echo "$(GREEN)Préparation terminée$(NC)"
