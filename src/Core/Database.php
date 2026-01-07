<?php
/**
 * ParkClean Manager - Database Connection
 * Gestion centralisée de la connexion SQLite
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Core;

use PDO;
use PDOException;

/**
 * Classe de gestion de la base de données
 */
class Database
{
    private static ?PDO $instance = null;
    private static string $dbPath = '';
    
    /**
     * Récupère l'instance singleton de la connexion PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Récupère la connexion PDO (alias pour getInstance)
     */
    public static function getConnection(): PDO
    {
        return self::getInstance();
    }

    /**
     * Établit la connexion à la base de données
     */
    private static function connect(): void
    {
        $dbPath = self::getDbPath();
        
        // Vérifier que le répertoire existe
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Vérifier que le fichier existe, sinon le créer
        if (!file_exists($dbPath)) {
            self::createDatabase($dbPath);
        }
        
        try {
            self::$instance = new PDO("sqlite:{$dbPath}");
            
            // Configuration PDO
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Activer les clés étrangères
            self::$instance->exec("PRAGMA foreign_keys = ON");
            
            // Optimisations SQLite
            self::$instance->exec("PRAGMA journal_mode = WAL");
            self::$instance->exec("PRAGMA synchronous = NORMAL");
            self::$instance->exec("PRAGMA cache_size = 10000");
            self::$instance->exec("PRAGMA temp_store = MEMORY");
            
        } catch (PDOException $e) {
            throw new PDOException(
                "Erreur de connexion à la base de données: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Crée la base de données avec le schéma par défaut
     */
    private static function createDatabase(string $dbPath): void
    {
        $sqlFile = dirname(__DIR__, 2) . '/database/parkclean.sql';
        
        if (!file_exists($sqlFile)) {
            throw new PDOException("Fichier SQL non trouvé: {$sqlFile}");
        }
        
        // Créer le fichier vide
        touch($dbPath);
        chmod($dbPath, 0666);
        
        // Exécuter le script SQL
        $sql = file_get_contents($sqlFile);
        
        // Créer une connexion temporaire pour exécuter le script
        $tempPdo = new PDO("sqlite:{$dbPath}");
        $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Diviser en instructions individuelles (en tenant compte des commentaires)
        $tempPdo->exec($sql);
        
        // Activer les clés étrangères après création
        $tempPdo->exec("PRAGMA foreign_keys = ON");
        
        unset($tempPdo);
    }

    /**
     * Définit le chemin de la base de données
     */
    public static function setDbPath(string $path): void
    {
        self::$dbPath = $path;
        // Réinitialiser l'instance
        self::$instance = null;
    }

    /**
     * Récupère le chemin de la base de données
     */
    public static function getDbPath(): string
    {
        if (empty(self::$dbPath)) {
            // Valeur par défaut
            self::$dbPath = dirname(__DIR__, 2) . '/database/parkclean.db';
        }
        return self::$dbPath;
    }

    /**
     * Réinitialise la connexion
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Ferme la connexion
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Vérifie l'intégrité de la base de données
     */
    public static function integrityCheck(): array
    {
        $pdo = self::getInstance();
        
        // Vérification SQLite standard
        $result = $pdo->query("PRAGMA integrity_check")->fetchColumn();
        
        if ($result === 'ok') {
            return [
                'status' => 'ok',
                'message' => 'Intégrité de la base de données vérifiée'
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Problème d\'intégrité détecté',
            'details' => $result
        ];
    }

    /**
     * Récupère les informations sur la base de données
     */
    public static function getInfo(): array
    {
        $pdo = self::getInstance();
        $dbPath = self::getDbPath();
        
        $tables = [];
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $result->fetch()) {
            $tableName = $row['name'];
            $count = $pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
            $tables[$tableName] = (int)$count;
        }
        
        return [
            'path' => $dbPath,
            'size' => file_exists($dbPath) ? filesize($dbPath) : 0,
            'tables' => $tables
        ];
    }

    /**
     * Exécute une sauvegarde de la base de données
     */
    public static function backup(string $backupPath): bool
    {
        $dbPath = self::getDbPath();
        
        if (!file_exists($dbPath)) {
            return false;
        }
        
        // Créer le répertoire de sauvegarde si nécessaire
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        try {
            // Utiliser SQLite backup API via shell
            $cmd = sprintf(
                'sqlite3 "%s" ".backup %s"',
                escapeshellarg($dbPath),
                escapeshellarg($backupPath)
            );
            exec($cmd, $output, $returnCode);
            
            return $returnCode === 0;
        } catch (\Exception $e) {
            // Fallback: copie simple
            return copy($dbPath, $backupPath);
        }
    }

    /**
     * Commence une transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Annule une transaction
     */
    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Vérifie si une transaction est active
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
}

