<?php
/**
 * ParkClean Manager - Unit Tests Bootstrap
 */

declare(strict_types=1);

// Charger l'autoloader du projet
require_once dirname(__DIR__) . '/src/autoload.php';

// Définir le chemin de la base de données de test
$testDbPath = dirname(__DIR__) . '/database/test_parkclean.db';
\ParkClean\Core\Database::setDbPath($testDbPath);

// Créer la base de données de test si nécessaire
if (!file_exists($testDbPath)) {
    $sqlFile = dirname(__DIR__) . '/database/parkclean.sql';
    if (file_exists($sqlFile)) {
        $pdo = new PDO("sqlite:{$testDbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(file_get_contents($sqlFile));
    }
}

