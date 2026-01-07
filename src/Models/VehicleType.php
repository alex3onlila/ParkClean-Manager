<?php
/**
 * ParkClean Manager - VehicleType Model
 * Modèle pour la gestion des types de véhicules
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Models;

use ParkClean\Core\BaseModel;
use ParkClean\Core\Database;
use PDO;
use InvalidArgumentException;

class VehicleType extends BaseModel
{
    protected string $table = 'vehicle_types';
    protected array $fillable = [
        'type', 'prix_lavage'
    ];
    protected array $casts = [
        'id' => 'integer',
        'prix_lavage' => 'float'
    ];

    /**
     * Récupère un type de véhicule avec le nombre de véhicules
     */
    public static function findWithCount(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT vt.*, COUNT(v.id) as vehicle_count
            FROM vehicle_types vt
            LEFT JOIN vehicles v ON v.type_id = vt.id
            WHERE vt.id = :id
            GROUP BY vt.id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère tous les types de véhicules avec le nombre de véhicules
     */
    public static function allWithCounts(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT vt.*, COUNT(v.id) as vehicle_count
            FROM vehicle_types vt
            LEFT JOIN vehicles v ON v.type_id = vt.id
            GROUP BY vt.id
            ORDER BY vt.type ASC
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau type de véhicule
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['type'])) {
            throw new InvalidArgumentException('Le type de véhicule est requis');
        }

        // Vérifier l'unicité du type
        if (self::existsBy('type', $data['type'])) {
            throw new InvalidArgumentException('Ce type de véhicule existe déjà');
        }

        // Valeur par défaut pour le prix
        if (!isset($data['prix_lavage']) || $data['prix_lavage'] < 0) {
            $data['prix_lavage'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Met à jour un type de véhicule
     */
    public static function update(int $id, array $data): bool
    {
        // Vérifier l'unicité du type si modifié
        if (isset($data['type'])) {
            if (self::existsBy('type', $data['type'], $id)) {
                throw new InvalidArgumentException('Ce type de véhicule existe déjà');
            }
        }

        // Prix positif
        if (isset($data['prix_lavage']) && $data['prix_lavage'] < 0) {
            throw new InvalidArgumentException('Le prix doit être positif');
        }

        return parent::update($id, $data);
    }

    /**
     * Supprime un type de véhicule (uniquement si aucun véhicule associé)
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        
        // Vérifier s'il y a des véhicules associés
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE type_id = ?');
        $stmt->execute([$id]);
        $vehicleCount = $stmt->fetchColumn();

        if ($vehicleCount > 0) {
            throw new InvalidArgumentException(
                "Impossible de supprimer ce type de véhicule ({$vehicleCount} véhicules associés)"
            );
        }

        return parent::delete($id);
    }

    /**
     * Recherche des types de véhicules
     */
    public static function search(string $query): array
    {
        return self::paginate(
            page: 1,
            perPage: 50,
            search: $query,
            searchFields: ['type']
        );
    }

    /**
     * Statistiques des types de véhicules
     */
    public static function getStats(): array
    {
        $pdo = Database::getConnection();
        
        $totalTypes = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_types")->fetchColumn();
        $totalVehicles = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        
        $avgPrice = (float)$pdo->query("SELECT AVG(prix_lavage) FROM vehicle_types")->fetchColumn();
        
        $byType = self::allWithCounts();

        return [
            'total_types' => $totalTypes,
            'total_vehicles' => $totalVehicles,
            'average_price' => $avgPrice,
            'by_type' => $byType
        ];
    }

    /**
     * Récupère les types de véhicules pour un select
     */
    public static function forSelect(): array
    {
        $types = self::allRaw();
        $result = [];
        
        foreach ($types as $type) {
            $result[$type['id']] = $type['type'] . ' (' . number_format($type['prix_lavage'], 0, ',', ' ') . ' CDF)';
        }
        
        return $result;
    }
}

