<?php
/**
 * ParkClean Manager - Entry Model
 * Modèle pour la gestion des entrées/sorties journalières
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

class Entry extends BaseModel
{
    protected string $table = 'entries';
    protected array $fillable = [
        'vehicle_id', 'date_enregistrement', 'montant_total', 
        'montant_recu', 'montant_restant', 'est_entree', 'est_sorti', 'obs'
    ];
    protected array $casts = [
        'id' => 'integer',
        'vehicle_id' => 'integer',
        'montant_total' => 'float',
        'montant_recu' => 'float',
        'montant_restant' => 'float',
        'est_entree' => 'integer',
        'est_sorti' => 'integer'
    ];

    /**
     * Récupère une entrée avec les informations du véhicule
     */
    public static function findWithVehicle(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT e.*, 
                   v.marque, v.immatriculation, v.image as vehicle_image,
                   c.nom as client_nom, c.prenom as client_prenom,
                   vt.type as vehicle_type
            FROM entries e
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            WHERE e.id = :id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère toutes les entrées avec les informations du véhicule
     */
    public static function allWithVehicles(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT e.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM entries e
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            ORDER BY e.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entrées du jour
     */
    public static function today(): array
    {
        $pdo = Database::getConnection();
        $today = date('Y-m-d');
        
        $sql = "
            SELECT e.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM entries e
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE DATE(e.date_enregistrement) = :today
            ORDER BY e.id DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':today', $today);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entrées d'une date spécifique
     */
    public static function byDate(string $date): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT e.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM entries e
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE DATE(e.date_enregistrement) = :date
            ORDER BY e.id DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les entrées non soldées
     */
    public static function unpaid(): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT e.*, 
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM entries e
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE e.montant_restant > 0
            ORDER BY e.id DESC
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle entrée
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException('Le véhicule est requis');
        }
        if (!isset($data['montant_total']) || $data['montant_total'] < 0) {
            throw new InvalidArgumentException('Le montant total doit être positif');
        }

        // Calculer le montant restant
        $montant_recu = $data['montant_recu'] ?? 0;
        $data['montant_restant'] = max(0, $data['montant_total'] - $montant_recu);
        $data['est_entree'] = 1;
        $data['est_sorti'] = 0;

        return parent::create($data);
    }

    /**
     * Enregistre une sortie
     */
    public static function markAsExited(int $id): bool
    {
        return parent::update($id, [
            'est_sorti' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Enregistre un paiement
     */
    public static function addPayment(int $id, float $amount): bool
    {
        $entry = self::find($id);
        if (!$entry) {
            return false;
        }

        $newReceived = ($entry['montant_recu'] ?? 0) + $amount;
        $newRemaining = max(0, ($entry['montant_total'] ?? 0) - $newReceived);

        return parent::update($id, [
            'montant_recu' => $newReceived,
            'montant_restant' => $newRemaining,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Statistiques journalières
     */
    public static function getDailyStats(?string $date = null): array
    {
        $pdo = Database::getConnection();
        $date = $date ?? date('Y-m-d');
        
        // Total des entrées
        $totalEntries = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM entries WHERE DATE(date_enregistrement) = ? AND est_entree = 1"
        )->execute([$date])->fetchColumn();

        // Total des sorties
        $totalExits = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM entries WHERE DATE(date_enregistrement) = ? AND est_sorti = 1"
        )->execute([$date])->fetchColumn();

        // Total des montants reçus
        $totalReceived = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant_recu), 0) FROM entries WHERE DATE(date_enregistrement) = ?"
        )->execute([$date])->fetchColumn();

        // Total des montants restants
        $totalRemaining = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant_restant), 0) FROM entries WHERE DATE(date_enregistrement) = ?"
        )->execute([$date])->fetchColumn();

        // Total attendu
        $totalExpected = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant_total), 0) FROM entries WHERE DATE(date_enregistrement) = ?"
        )->execute([$date])->fetchColumn();

        return [
            'date' => $date,
            'total_entries' => $totalEntries,
            'total_exits' => $totalExits,
            'total_received' => $totalReceived,
            'total_remaining' => $totalRemaining,
            'total_expected' => $totalExpected
        ];
    }

    /**
     * Statistiques mensuelles
     */
    public static function getMonthlyStats(int $month, int $year): array
    {
        $pdo = Database::getConnection();
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $totalEntries = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM entries WHERE DATE(date_enregistrement) BETWEEN ? AND ?"
        )->execute([$startDate, $endDate])->fetchColumn();

        $totalReceived = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant_recu), 0) FROM entries WHERE DATE(date_enregistrement) BETWEEN ? AND ?"
        )->execute([$startDate, $endDate])->fetchColumn();

        return [
            'month' => $month,
            'year' => $year,
            'total_entries' => $totalEntries,
            'total_received' => $totalReceived
        ];
    }
}

