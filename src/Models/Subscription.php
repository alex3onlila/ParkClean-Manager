<?php
/**
 * ParkClean Manager - Subscription Model
 * Modèle pour la gestion des abonnements
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

class Subscription extends BaseModel
{
    protected string $table = 'abonnements';
    protected array $fillable = [
        'vehicle_id', 'date_enregistrement', 'date_debut', 'date_fin',
        'montant_total', 'montant_recu', 'montant_restant', 'est_actif', 'obs'
    ];
    protected array $casts = [
        'id' => 'integer',
        'vehicle_id' => 'integer',
        'montant_total' => 'float',
        'montant_recu' => 'float',
        'montant_restant' => 'float',
        'est_actif' => 'integer'
    ];

    /**
     * Récupère un abonnement avec les informations du véhicule
     */
    public static function findWithVehicle(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT a.*, 
                   v.marque, v.immatriculation, v.image as vehicle_image,
                   c.nom as client_nom, c.prenom as client_prenom,
                   c.email as client_email, c.telephone as client_telephone
            FROM abonnements a
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE a.id = :id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère tous les abonnements avec les informations du véhicule
     */
    public static function allWithVehicles(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT a.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM abonnements a
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            ORDER BY a.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les abonnements actifs
     */
    public static function active(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT a.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM abonnements a
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE a.est_actif = 1
            ORDER BY a.date_fin ASC
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les abonnements expirant bientôt
     */
    public static function expiringSoon(int $days = 7): array
    {
        $pdo = Database::getConnection();
        $limitDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "
            SELECT a.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom,
                   c.email as client_email, c.telephone as client_telephone
            FROM abonnements a
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE a.est_actif = 1 
              AND a.date_fin <= :limitDate
              AND a.date_fin >= :today
            ORDER BY a.date_fin ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limitDate', $limitDate);
        $stmt->bindValue(':today', date('Y-m-d'));
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les abonnements expirés
     */
    public static function expired(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT a.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM abonnements a
            LEFT JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE a.est_actif = 1 AND a.date_fin < :today
            ORDER BY a.date_fin DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':today', date('Y-m-d'));
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les abonnements d'un véhicule
     */
    public static function findByVehicle(int $vehicleId): array
    {
        return self::findAllBy('vehicle_id', $vehicleId);
    }

    /**
     * Crée un nouvel abonnement
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException('Le véhicule est requis');
        }
        if (empty($data['date_debut'])) {
            throw new InvalidArgumentException('La date de début est requise');
        }
        if (empty($data['date_fin'])) {
            throw new InvalidArgumentException('La date de fin est requise');
        }
        if (strtotime($data['date_fin']) <= strtotime($data['date_debut'])) {
            throw new InvalidArgumentException('La date de fin doit être après la date de début');
        }
        if (!isset($data['montant_total']) || $data['montant_total'] < 0) {
            throw new InvalidArgumentException('Le montant total doit être positif');
        }

        // Calculer le montant restant
        $montant_recu = $data['montant_recu'] ?? 0;
        $data['montant_restant'] = max(0, $data['montant_total'] - $montant_recu);
        $data['est_actif'] = 1;

        return parent::create($data);
    }

    /**
     * Renouvelle un abonnement
     */
    public static function renew(int $id, array $data): bool
    {
        $subscription = self::find($id);
        if (!$subscription) {
            return false;
        }

        $newStart = $subscription['date_fin'];
        $newEnd = date('Y-m-d', strtotime($newStart . ' + ' . ($data['duration'] ?? 30) . ' days'));

        // Désactiver l'ancien abonnement
        parent::update($id, ['est_actif' => 0]);

        // Créer un nouvel abonnement
        self::create([
            'vehicle_id' => $subscription['vehicle_id'],
            'date_debut' => $newStart,
            'date_fin' => $newEnd,
            'montant_total' => $data['montant_total'] ?? $subscription['montant_total'],
            'montant_recu' => $data['montant_recu'] ?? 0,
            'obs' => 'Renouvellement de l\'abonnement #' . $id
        ]);

        return true;
    }

    /**
     * Ajoute un paiement à l'abonnement
     */
    public static function addPayment(int $id, float $amount): bool
    {
        $subscription = self::find($id);
        if (!$subscription) {
            return false;
        }

        $newReceived = ($subscription['montant_recu'] ?? 0) + $amount;
        $newRemaining = max(0, ($subscription['montant_total'] ?? 0) - $newReceived);

        return parent::update($id, [
            'montant_recu' => $newReceived,
            'montant_restant' => $newRemaining
        ]);
    }

    /**
     * Désactive un abonnement
     */
    public static function deactivate(int $id): bool
    {
        return parent::update($id, [
            'est_actif' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Statistiques des abonnements
     */
    public static function getStats(): array
    {
        $pdo = Database::getConnection();
        
        $total = (int)$pdo->query("SELECT COUNT(*) FROM abonnements")->fetchColumn();
        $active = (int)$pdo->query("SELECT COUNT(*) FROM abonnements WHERE est_actif = 1")->fetchColumn();
        $expired = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM abonnements WHERE est_actif = 1 AND date_fin < ?"
        )->execute([date('Y-m-d')])->fetchColumn();
        
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM abonnements")->fetchColumn();
        $receivedRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant_recu), 0) FROM abonnements")->fetchColumn();
        $remainingRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant_restant), 0) FROM abonnements")->fetchColumn();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'total_revenue' => $totalRevenue,
            'received_revenue' => $receivedRevenue,
            'remaining_revenue' => $remainingRevenue
        ];
    }
}

