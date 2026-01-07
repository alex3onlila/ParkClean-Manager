<?php
/**
 * ParkClean Manager - Logger
 * Système de logging structuré
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Core;

use DateTime;
use RuntimeException;

/**
 * Classe de logging structuré
 */
class Logger
{
    private static ?self $instance = null;
    private string $logPath;
    private string $env;
    private int $maxFiles = 5;
    private int $maxSize = 10485760; // 10MB
    private bool $initialized = false;

    /**
     * Niveaux de log
     */
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Constructeur privé (singleton)
     */
    private function __construct()
    {
        $this->env = getenv('APP_ENV') ?: 'development';
        $this->logPath = $this->getLogPath();
        $this->ensureLogDirectory();
    }

    /**
     * Récupère l'instance singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Méthode statique shortcut pour log
     */
    public static function log(string $message, array $context = [], string $level = self::LEVEL_INFO): void
    {
        self::getInstance()->write($message, $context, $level);
    }

    /**
     * Log de niveau DEBUG
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->write($message, $context, self::LEVEL_DEBUG);
    }

    /**
     * Log de niveau INFO
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->write($message, $context, self::LEVEL_INFO);
    }

    /**
     * Log de niveau WARNING
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->write($message, $context, self::LEVEL_WARNING);
    }

    /**
     * Log de niveau ERROR
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->write($message, $context, self::LEVEL_ERROR);
    }

    /**
     * Log de niveau CRITICAL
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->write($message, $context, self::LEVEL_CRITICAL);
    }

    /**
     * Écrit un log
     */
    public function write(string $message, array $context = [], string $level = self::LEVEL_INFO): void
    {
        // En développement, afficher dans la console si configuré
        if ($this->env === 'development' && getenv('APP_DEBUG') === 'true') {
            $this->writeToConsole($message, $context, $level);
        }

        // Ne pas logger les DEBUG en production
        if ($level === self::LEVEL_DEBUG && $this->env !== 'development') {
            return;
        }

        $this->ensureLogDirectory();
        $this->checkRotation();

        $logEntry = $this->formatLogEntry($message, $context, $level);
        $filePath = $this->getLogFilePath();

        if (file_put_contents($filePath, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            // Ne pas provoquer d'erreur si le logging échoue
            error_log("Failed to write to log file: {$filePath}");
        }
    }

    /**
     * Formate une entrée de log
     */
    private function formatLogEntry(string $message, array $context, string $level): string
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
        $contextJson = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        return "[{$timestamp}] [{$level}] {$message}{$contextJson}\n";
    }

    /**
     * Écrit dans la console (pour développement)
     */
    private function writeToConsole(string $message, array $context, string $level): void
    {
        $colors = [
            self::LEVEL_DEBUG => "\033[36m",   // Cyan
            self::LEVEL_INFO => "\033[32m",    // Green
            self::LEVEL_WARNING => "\033[33m", // Yellow
            self::LEVEL_ERROR => "\033[31m",   // Red
            self::LEVEL_CRITICAL => "\033[35m",// Magenta
        ];
        
        $color = $colors[$level] ?? "\033[0m";
        $reset = "\033[0m";
        
        $timestamp = date('H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        echo "{$color}[{$timestamp}] [{$level}]{$reset} {$message}{$contextStr}\n";
    }

    /**
     * Récupère le chemin du fichier de log
     */
    private function getLogFilePath(): string
    {
        $date = date('Y-m-d');
        return $this->logPath . "/parkclean-{$date}.log";
    }

    /**
     * Récupère le chemin du répertoire de logs
     */
    private function getLogPath(): string
    {
        $defaultPath = dirname(__DIR__, 2) . '/logs';
        $customPath = getenv('LOG_PATH');
        
        return $customPath ?: $defaultPath;
    }

    /**
     * S'assure que le répertoire de logs existe
     */
    private function ensureLogDirectory(): void
    {
        if ($this->initialized) {
            return;
        }

        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true)) {
                throw new RuntimeException("Cannot create log directory: {$this->logPath}");
            }
        }

        // Créer un .gitkeep
        $gitkeep = $this->logPath . '/.gitkeep';
        if (!file_exists($gitkeep)) {
            file_put_contents($gitkeep, '');
        }

        $this->initialized = true;
    }

    /**
     * Vérifie la rotation des fichiers de log
     */
    private function checkRotation(): void
    {
        $currentFile = $this->getLogFilePath();
        
        if (!file_exists($currentFile)) {
            return;
        }

        if (filesize($currentFile) < $this->maxSize) {
            return;
        }

        // Renommer le fichier actuel
        $renameTo = $currentFile . '.' . time() . '.old';
        rename($currentFile, $renameTo);

        // Supprimer les vieux fichiers
        $this->cleanOldLogs();
    }

    /**
     * Supprime les vieux fichiers de log
     */
    private function cleanOldLogs(): void
    {
        $pattern = $this->logPath . '/parkclean-*.log.*';
        $files = glob($pattern);
        
        if (empty($files)) {
            return;
        }

        // Trier par date de modification
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        // Garder seulement les fichiers récents
        $filesToDelete = array_slice($files, $this->maxFiles);
        
        foreach ($filesToDelete as $file) {
            @unlink($file);
        }
    }

    /**
     * Récupère les logs récents
     */
    public static function getRecentLogs(int $lines = 100): array
    {
        $instance = self::getInstance();
        $filePath = $instance->getLogFilePath();
        
        if (!file_exists($filePath)) {
            return [];
        }

        $content = shell_exec("tail -n {$lines} " . escapeshellarg($filePath));
        $lines = explode("\n", trim($content));
        
        return array_filter($lines, fn($line) => !empty(trim($line)));
    }

    /**
     * Nettoie tous les fichiers de log
     */
    public static function clearLogs(): void
    {
        $instance = self::getInstance();
        $pattern = $instance->logPath . '/parkclean-*.log*';
        
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }

    /**
     * Récupère la taille totale des logs
     */
    public static function getLogsSize(): int
    {
        $instance = self::getInstance();
        $pattern = $instance->logPath . '/parkclean-*.log*';
        $totalSize = 0;
        
        foreach (glob($pattern) as $file) {
            $totalSize += filesize($file);
        }
        
        return $totalSize;
    }
}

