<?php
/**
 * ParkClean Manager - Base Model
 * Classe mère pour tous les modèles de données
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Core;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Classe abstraite de base pour tous les modèles
 * Fournit les opérations CRUD standardisées
 */
abstract class BaseModel
{
    protected static ?PDO $pdo = null;
    protected static string $dbPath = '';
    
    protected string $table = '';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    
    // Colonnes de timestamps
    protected bool $hasTimestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Initialise la connexion à la base de données
     */
    public static function init(?string $dbPath = null): void
    {
        if ($dbPath !== null) {
            self::$dbPath = $dbPath;
        }
        
        if (self::$pdo === null) {
            self::$pdo = Database::getConnection();
        }
    }

    /**
     * Récupère l'instance PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = Database::getConnection();
        }
        return self::$pdo;
    }

    /**
     * Récupère le chemin de la base de données
     */
    public static function getDbPath(): string
    {
        if (empty(self::$dbPath)) {
            self::$dbPath = dirname(__DIR__, 2) . '/database/parkclean.db';
        }
        return self::$dbPath;
    }

    /**
     * Définit le chemin de la base de données
     */
    public static function setDbPath(string $path): void
    {
        self::$dbPath = $path;
        // Réinitialiser la connexion si elle existe
        self::$pdo = null;
    }

    /**
     * Récupère tous les enregistrements
     */
    public static function all(int $limit = 100, int $offset = 0, string $orderBy = 'id', string $orderDir = 'DESC'): array
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "SELECT * FROM {$table} ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les enregistrements sans pagination
     */
    public static function allRaw(): array
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "SELECT * FROM {$table} ORDER BY id DESC";
        $stmt = self::getConnection()->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un enregistrement par ID
     */
    public static function find(int $id): ?array
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère un enregistrement par un champ spécifique
     */
    public static function findBy(string $field, mixed $value): ?array
    {
        $instance = new static();
        $table = $instance->table;
        
        // Protection contre l'injection SQL sur les noms de colonnes
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new InvalidArgumentException("Nom de colonne invalide: {$field}");
        }
        
        $sql = "SELECT * FROM {$table} WHERE {$field} = :value LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère plusieurs enregistrements par un champ spécifique
     */
    public static function findAllBy(string $field, mixed $value, int $limit = 100): array
    {
        $instance = new static();
        $table = $instance->table;
        
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new InvalidArgumentException("Nom de colonne invalide: {$field}");
        }
        
        $sql = "SELECT * FROM {$table} WHERE {$field} = :value ORDER BY id DESC LIMIT :limit";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouvel enregistrement
     */
    public static function create(array $data): array
    {
        $instance = new static();
        $table = $instance->table;
        
        // Filtrer les données avec fillable
        $data = $instance->filterFillable($data);
        
        // Ajouter les timestamps
        if ($instance->hasTimestamps) {
            $data[$instance->createdAtColumn] = date('Y-m-d H:i:s');
            $data[$instance->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ":{$col}", array_keys($data)));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($data);
            
            $id = (int)self::getConnection()->lastInsertId();
            return array_merge(['id' => $id], $data);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la création: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Met à jour un enregistrement
     */
    public static function update(int $id, array $data): bool
    {
        $instance = new static();
        $table = $instance->table;
        
        // Filtrer les données avec fillable
        $data = $instance->filterFillable($data);
        
        // Mettre à jour le timestamp
        if ($instance->hasTimestamps) {
            $data[$instance->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        
        // Construire la requête SET
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE id = :id";
        $data['id'] = $id;
        
        try {
            $stmt = self::getConnection()->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Supprime un enregistrement
     */
    public static function delete(int $id): bool
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Supprime plusieurs enregistrements
     */
    public static function deleteMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $instance = new static();
        $table = $instance->table;
        
        $placeholders = implode(', ', array_map(fn($i) => ":id{$i}", array_keys($ids)));
        $sql = "DELETE FROM {$table} WHERE id IN ({$placeholders})";
        
        $params = [];
        foreach ($ids as $index => $id) {
            $params[":id{$index}"] = $id;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Compte le nombre d'enregistrements
     */
    public static function count(?string $where = null, array $params = []): int
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "SELECT COUNT(*) FROM {$table}";
        if ($where !== null) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Vérifie si un enregistrement existe
     */
    public static function exists(int $id): bool
    {
        $instance = new static();
        $table = $instance->table;
        
        $sql = "SELECT 1 FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return (bool)$stmt->fetch();
    }

    /**
     * Vérifie si une valeur existe dans un champ
     */
    public static function existsBy(string $field, mixed $value, ?int $excludeId = null): bool
    {
        $instance = new static();
        $table = $instance->table;
        
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new InvalidArgumentException("Nom de colonne invalide: {$field}");
        }
        
        $sql = "SELECT 1 FROM {$table} WHERE {$field} = :value";
        $params = [':value' => $value];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return (bool)$stmt->fetch();
    }

    /**
     * Exécute une requête SQL personnalisée (SELECT)
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exécute une requête SQL personnalisée (INSERT, UPDATE, DELETE)
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Effectue une recherche avec pagination
     */
    public static function paginate(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        array $searchFields = [],
        ?string $orderBy = null,
        string $orderDir = 'DESC'
    ): array {
        $instance = new static();
        $table = $instance->table;
        
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $whereClause = '';
        $params = [];
        
        if ($search !== null && !empty($searchFields)) {
            $conditions = [];
            foreach ($searchFields as $field) {
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
                    continue;
                }
                $conditions[] = "{$field} LIKE :search";
            }
            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' OR ', $conditions);
                $params[':search'] = "%{$search}%";
            }
        }
        
        $orderBy = $orderBy ?? 'id';
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $orderBy)) {
            $orderBy = 'id';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        
        // Compter le total
        $countSql = "SELECT COUNT(*) FROM {$table} {$whereClause}";
        $stmt = self::getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        // Récupérer les données
        $sql = "SELECT * FROM {$table} {$whereClause} ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
        $stmt = self::getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int)ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Filtre les données avec fillable
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Applique les casts sur les données
     */
    protected function applyCasts(array $data): array
    {
        foreach ($this->casts as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $data[$field] = match ($type) {
                'int', 'integer' => (int)$data[$field],
                'float', 'double', 'real' => (float)$data[$field],
                'bool', 'boolean' => (bool)$data[$field],
                'array', 'json' => is_string($data[$field]) ? json_decode($data[$field], true) : $data[$field],
                'string' => (string)$data[$field],
                default => $data[$field]
            };
        }
        
        return $data;
    }

    /**
     * Cache certains champs de la réponse
     */
    protected function hideFields(array $data): array
    {
        if (empty($this->hidden)) {
            return $data;
        }
        
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }

    /**
     * Hydrate le modèle avec des données
     */
    public function hydrate(array $data): self
    {
        $data = $this->applyCasts($data);
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }

    /**
     * Convertit le modèle en tableau
     */
    public function toArray(): array
    {
        $data = get_object_vars($this);
        unset($data['table'], $data['fillable'], $data['hidden'], $data['casts']);
        unset($data['hasTimestamps'], $data['createdAtColumn'], $data['updatedAtColumn']);
        
        return $this->hideFields($data);
    }
}

