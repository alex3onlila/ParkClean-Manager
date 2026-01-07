<?php
/**
 * ParkClean Manager - Vehicle Model
 * Modèle pour la gestion des véhicules
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

class Vehicle extends BaseModel
{
    protected string $table = 'vehicles';
    protected array $fillable = [
        'client_id', 'marque', 'type_id', 'immatriculation', 'image'
    ];
    protected array $casts = [
        'id' => 'integer',
        'client_id' => 'integer',
        'type_id' => 'integer'
    ];

    /**
     * Récupère un véhicule avec les informations du client
     */
    public static function findWithClient(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT v.*, 
                   c.nom as client_nom, c.prenom as client_prenom,
                   c.email as client_email, c.telephone as client_telephone,
                   vt.type as type_nom, vt.prix_lavage
            FROM vehicles v
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            WHERE v.id = :id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère tous les véhicules avec les informations du client
     */
    public static function allWithClients(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT v.*, 
                   c.nom as client_nom, c.prenom as client_prenom,
                   vt.type as type_nom
            FROM vehicles v
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            ORDER BY v.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les véhicules d'un client
     */
    public static function findByClient(int $clientId): array
    {
        return self::findAllBy('client_id', $clientId);
    }

    /**
     * Recherche des véhicules par marque, modèle ou immatriculation
     */
    public static function search(string $query, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT v.*, 
                   c.nom as client_nom, c.prenom as client_prenom,
                   vt.type as type_nom
            FROM vehicles v
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            WHERE v.marque LIKE :query1
               OR v.immatriculation LIKE :query2
               OR c.nom LIKE :query3
               OR c.prenom LIKE :query4
            ORDER BY v.id DESC
            LIMIT :limit
        ";
        
        $stmt = $pdo->prepare($sql);
        $searchTerm = "%{$query}%";
        $stmt->bindValue(':query1', $searchTerm);
        $stmt->bindValue(':query2', $searchTerm);
        $stmt->bindValue(':query3', $searchTerm);
        $stmt->bindValue(':query4', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un véhicule
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['client_id'])) {
            throw new InvalidArgumentException('Le client est requis');
        }
        if (empty($data['marque'])) {
            throw new InvalidArgumentException('La marque est requise');
        }
        if (empty($data['immatriculation'])) {
            throw new InvalidArgumentException('L\'immatriculation est requise');
        }

        // Vérifier l'unicité de l'immatriculation
        if (self::existsBy('immatriculation', $data['immatriculation'])) {
            throw new InvalidArgumentException('Un véhicule avec cette immatriculation existe déjà');
        }

        return parent::create($data);
    }

    /**
     * Met à jour un véhicule
     */
    public static function update(int $id, array $data): bool
    {
        // Vérifier l'unicité de l'immatriculation (si modifié)
        if (isset($data['immatriculation'])) {
            if (self::existsBy('immatriculation', $data['immatriculation'], $id)) {
                throw new InvalidArgumentException('Un véhicule avec cette immatriculation existe déjà');
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Supprime un véhicule (avec vérification des dépendances)
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();

        // Vérifier s'il y a des entrées associées
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM entries WHERE vehicle_id = ?');
        $stmt->execute([$id]);
        $entryCount = $stmt->fetchColumn();

        // Vérifier s'il y a des abonnements actifs
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM abonnements WHERE vehicle_id = ? AND est_actif = 1');
        $stmt->execute([$id]);
        $subscriptionCount = $stmt->fetchColumn();

        if ($entryCount > 0 || $subscriptionCount > 0) {
            // Soft delete plutôt que suppression définitive
            return parent::update($id, ['updated_at' => date('Y-m-d H:i:s')]);
        }

        return parent::delete($id);
    }

    /**
     * Compte les véhicules par type
     */
    public static function countByType(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT vt.type, COUNT(v.id) as count
            FROM vehicle_types vt
            LEFT JOIN vehicles v ON v.type_id = vt.id
            GROUP BY vt.id
            ORDER BY count DESC
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques des véhicules
     */
    public static function getStats(): array
    {
        $pdo = Database::getConnection();
        
        $total = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        $withClient = (int)$pdo->query("SELECT COUNT(*) FROM vehicles WHERE client_id IS NOT NULL")->fetchColumn();
        $withoutClient = $total - $withClient;
        
        return [
            'total' => $total,
            'with_client' => $withClient,
            'without_client' => $withoutClient,
            'by_type' => self::countByType()
        ];
    }
}

