<?php
/**
 * ParkClean Manager - Client Model
 * Modèle pour la gestion des clients
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

class Client extends BaseModel
{
    protected string $table = 'clients';
    protected array $fillable = [
        'nom', 'prenom', 'email', 'telephone',
        'nbr_vehicules', 'matricules_historique', 'image'
    ];
    protected array $casts = [
        'id' => 'integer',
        'nbr_vehicules' => 'integer'
    ];

    /**
     * Récupère un client avec le nombre de véhicules
     */
    public static function findWithVehicleCount(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT c.*, COALESCE(v.count, 0) as vehicles_count
            FROM clients c
            LEFT JOIN (
                SELECT client_id, COUNT(*) as count
                FROM vehicles
                GROUP BY client_id
            ) v ON v.client_id = c.id
            WHERE c.id = :id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère tous les clients avec le nombre de véhicules
     */
    public static function allWithVehicleCount(int $limit = 100, int $offset = 0, string $sort = 'recent'): array
    {
        $pdo = Database::getConnection();
        
        $orderBy = match ($sort) {
            'name' => 'c.nom COLLATE NOCASE ASC, c.prenom COLLATE NOCASE ASC',
            'recent' => 'c.id DESC',
            default => 'c.id DESC'
        };

        $sql = "
            SELECT c.*, COALESCE(v.count, c.nbr_vehicules, 0) as vehicles_count
            FROM clients c
            LEFT JOIN (
                SELECT client_id, COUNT(*) as count
                FROM vehicles
                GROUP BY client_id
            ) v ON v.client_id = c.id
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche des clients par nom, prénom, email ou téléphone
     */
    public static function search(string $query, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT c.*, COALESCE(v.count, 0) as vehicles_count
            FROM clients c
            LEFT JOIN (
                SELECT client_id, COUNT(*) as count
                FROM vehicles
                GROUP BY client_id
            ) v ON v.client_id = c.id
            WHERE c.nom LIKE :query1
               OR c.prenom LIKE :query2
               OR c.email LIKE :query3
               OR c.telephone LIKE :query4
            ORDER BY c.id DESC
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
     * Crée un nouveau client
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['nom']) || empty($data['prenom'])) {
            throw new InvalidArgumentException('Le nom et le prénom sont requis');
        }

        // Validation email si fourni
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('L\'email est invalide');
            }

            // Vérifier l'unicité de l'email
            if (self::existsBy('email', $data['email'])) {
                throw new InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        // Valeur par défaut pour le nombre de véhicules
        if (!isset($data['nbr_vehicules'])) {
            $data['nbr_vehicules'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Met à jour un client
     */
    public static function update(int $id, array $data): bool
    {
        // Validation email si modifié
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('L\'email est invalide');
            }

            if (self::existsBy('email', $data['email'], $id)) {
                throw new InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Supprime un client (avec vérification des véhicules)
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();

        // Vérifier si le client a des véhicules
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE client_id = ?');
        $stmt->execute([$id]);
        $vehicleCount = $stmt->fetchColumn();

        if ($vehicleCount > 0) {
            throw new InvalidArgumentException(
                "Impossible de supprimer ce client ({$vehicleCount} véhicules associés)"
            );
        }

        return parent::delete($id);
    }

    /**
     * Ajoute une immatriculation à l'historique
     */
    public static function addToHistory(int $id, string $immatriculation): bool
    {
        $client = self::find($id);
        if (!$client) {
            return false;
        }

        $history = [];
        if (!empty($client['matricules_historique'])) {
            $history = json_decode($client['matricules_historique'], true) ?? [];
        }

        // Éviter les doublons
        if (!in_array($immatriculation, $history)) {
            $history[] = $immatriculation;
        }

        return parent::update($id, [
            'matricules_historique' => json_encode($history)
        ]);
    }

    /**
     * Met à jour le nombre de véhicules du client
     */
    public static function updateVehicleCount(int $id): bool
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE client_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();

        return parent::update($id, ['nbr_vehicules' => $count]);
    }

    /**
     * Statistiques des clients
     */
    public static function getStats(): array
    {
        $pdo = Database::getConnection();
        
        $total = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        $withVehicles = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE nbr_vehicules > 0")->fetchColumn();
        $withoutVehicles = $total - $withVehicles;
        $totalVehicles = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();

        // Clients avec le plus de véhicules
        $topClients = $pdo->query("
            SELECT c.id, c.nom, c.prenom, COUNT(v.id) as count
            FROM clients c
            LEFT JOIN vehicles v ON v.client_id = c.id
            GROUP BY c.id
            ORDER BY count DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'with_vehicles' => $withVehicles,
            'without_vehicles' => $withoutVehicles,
            'total_vehicles' => $totalVehicles,
            'avg_vehicles_per_client' => $total > 0 ? round($totalVehicles / $total, 2) : 0,
            'top_clients' => $topClients
        ];
    }
}

