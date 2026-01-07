<?php
/**
 * ParkClean Manager - Autoloader PSR-4
 * Système d'autoloading des classes
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

// Enregistrer l'autoloader
spl_autoload_register(function (string $className): void {
    // Namespace de base du projet
    $prefix = 'ParkClean\\';
    $baseDir = dirname(__DIR__) . '/src/';
    
    // Vérifier si le namespace correspond
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    // Récupérer le nom de la classe relative au namespace
    $relativeClass = substr($className, $len);
    
    // Remplacer les séparateurs de namespace par des slashs de répertoire
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Si le fichier existe, l'inclure
    if (file_exists($file)) {
        require $file;
    }
});

// Fonction utilitaire pour charger les dépendances Composer si disponibles
function requireComposerAutoloader(): void
{
    $composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require $composerAutoload;
    }
}

// Charger l'autoloader Composer si disponible
requireComposerAutoloader();

