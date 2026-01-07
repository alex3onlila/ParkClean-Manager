<?php
/**
 * ParkClean Manager - BaseModel Unit Tests
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ParkClean\Core\Database;
use ParkClean\Core\BaseModel;

class BaseModelTest extends TestCase
{
    private static string $testDbPath = '';
    private static bool $dbInitialized = false;

    public static function setUpBeforeClass(): void
    {
        self::$testDbPath = dirname(__DIR__, 2) . '/database/test_parkclean.db';
        
        // Copier la base de données originale si elle existe
        $originalDb = dirname(__DIR__, 2) . '/database/parkclean.db';
        if (file_exists($originalDb) && !file_exists(self::$testDbPath)) {
            copy($originalDb, self::$testDbPath);
        }
        
        // Initialiser la base de données
        Database::setDbPath(self::$testDbPath);
        self::$dbInitialized = true;
    }

    public function setUp(): void
    {
        if (!self::$dbInitialized) {
            Database::setDbPath(self::$testDbPath);
            self::$dbInitialized = true;
        }
    }

    public function tearDown(): void
    {
        // Nettoyer après les tests si nécessaire
    }

    public static function tearDownAfterClass(): void
    {
        // Supprimer la base de données de test
        if (file_exists(self::$testDbPath)) {
            unlink(self::$testDbPath);
        }
    }

    /**
     * Test modèle factice pour vérifier l'autoloading
     */
    public function testAutoloading(): void
    {
        $this->assertTrue(class_exists(\ParkClean\Core\BaseModel::class));
        $this->assertTrue(class_exists(\ParkClean\Core\Database::class));
        $this->assertTrue(class_exists(\ParkClean\Models\Client::class));
    }

    /**
     * Test que la connexion à la base de données fonctionne
     */
    public function testDatabaseConnection(): void
    {
        $pdo = Database::getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
        
        // Vérifier que les tables existent
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains('clients', $tables);
        $this->assertContains('vehicles', $tables);
    }

    /**
     * Test que la base de données est intègre
     */
    public function testDatabaseIntegrity(): void
    {
        $pdo = Database::getConnection();
        $result = $pdo->query("PRAGMA integrity_check")->fetchColumn();
        $this->assertEquals('ok', $result);
    }

    /**
     * Test des informations de la base de données
     */
    public function testDatabaseInfo(): void
    {
        $info = Database::getInfo();
        
        $this->assertArrayHasKey('path', $info);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('tables', $info);
        
        $this->assertIsArray($info['tables']);
        $this->assertArrayHasKey('clients', $info['tables']);
    }
}

