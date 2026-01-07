<?php
/**
 * ParkClean Manager - Payment Model
 * Modèle pour la gestion des paiements
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

class Payment extends BaseModel
{
    protected string $table = 'payments';
    protected array $fillable = [
        'entry_id', 'montant', 'mode_paiement', 'date_paiement'
    ];
    protected array $casts = [
        'id' => 'integer',
        'entry_id' => 'integer',
        'montant' => 'float'
    ];

    /**
     * Modes de paiement valides
     */
    public const MODE_CASH = 'cash';
    public const MODE_CARD = 'carte';
    public const MODE_TRANSFER = 'virement';
    public const MODE_OTHER = 'autre';

    /**
     * Récupère un paiement avec les informations de l'entrée
     */
    public static function findWithEntry(int $id): ?array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT p.*, 
                   e.montant_total, e.montant_restant as entry_restant,
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM payments p
            LEFT JOIN entries e ON p.entry_id = e.id
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE p.id = :id
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère tous les paiements avec les informations de l'entrée
     */
    public static function allWithEntries(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT p.*, 
                   e.montant_total,
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM payments p
            LEFT JOIN entries e ON p.entry_id = e.id
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les paiements d'une entrée
     */
    public static function findByEntry(int $entryId): array
    {
        return self::findAllBy('entry_id', $entryId);
    }

    /**
     * Récupère les paiements d'une date
     */
    public static function byDate(string $date): array
    {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT p.*, 
                   e.montant_total,
                   v.marque, v.immatriculation,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM payments p
            LEFT JOIN entries e ON p.entry_id = e.id
            LEFT JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE DATE(p.date_paiement) = :date
            ORDER BY p.id DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les paiements d'aujourd'hui
     */
    public static function today(): array
    {
        return self::byDate(date('Y-m-d'));
    }

    /**
     * Crée un nouveau paiement
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['entry_id'])) {
            throw new InvalidArgumentException('L\'entrée est requise');
        }
        if (!isset($data['montant']) || $data['montant'] <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif');
        }

        // Vérifier que l'entrée existe
        $entry = Entry::find((int)$data['entry_id']);
        if (!$entry) {
            throw new InvalidArgumentException('L\'entrée spécifiée n\'existe pas');
        }

        // Vérifier le montant restant
        $remaining = $entry['montant_restant'] ?? 0;
        if ($data['montant'] > $remaining) {
            throw new InvalidArgumentException(
                "Le montant ({$data['montant']}) dépasse le montant restant ({$remaining})"
            );
        }

        // Valider le mode de paiement
        $validModes = [self::MODE_CASH, self::MODE_CARD, self::MODE_TRANSFER, self::MODE_OTHER];
        if (!isset($data['mode_paiement']) || !in_array($data['mode_paiement'], $validModes)) {
            $data['mode_paiement'] = self::MODE_CASH;
        }

        // Créer le paiement
        $payment = parent::create($data);

        // Mettre à jour l'entrée
        Entry::addPayment((int)$data['entry_id'], (float)$data['montant']);

        return $payment;
    }

    /**
     * Supprime un paiement
     */
    public static function delete(int $id): bool
    {
        // Récupérer le paiement
        $payment = self::find($id);
        if (!$payment) {
            return false;
        }

        // Supprimer le paiement
        $result = parent::delete($id);

        // Rembourser l'entrée (annuler le paiement)
        if ($result) {
            Entry::addPayment((int)$payment['entry_id'], -1 * (float)$payment['montant']);
        }

        return $result;
    }

    /**
     * Statistiques des paiements
     */
    public static function getStats(?string $date = null): array
    {
        $pdo = Database::getConnection();
        $date = $date ?? date('Y-m-d');
        
        $totalPayments = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM payments WHERE DATE(date_paiement) = ?"
        )->execute([$date])->fetchColumn();

        $totalAmount = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant), 0) FROM payments WHERE DATE(date_paiement) = ?"
        )->execute([$date])->fetchColumn();

        // Par mode de paiement
        $byMode = [];
        $modes = [self::MODE_CASH, self::MODE_CARD, self::MODE_TRANSFER, self::MODE_OTHER];
        foreach ($modes as $mode) {
            $amount = (float)$pdo->prepare(
                "SELECT COALESCE(SUM(montant), 0) FROM payments WHERE DATE(date_paiement) = ? AND mode_paiement = ?"
            )->execute([$date, $mode])->fetchColumn();
            $byMode[$mode] = $amount;
        }

        return [
            'date' => $date,
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'by_mode' => $byMode
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
        
        $totalPayments = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM payments WHERE DATE(date_paiement) BETWEEN ? AND ?"
        )->execute([$startDate, $endDate])->fetchColumn();

        $totalAmount = (float)$pdo->prepare(
            "SELECT COALESCE(SUM(montant), 0) FROM payments WHERE DATE(date_paiement) BETWEEN ? AND ?"
        )->execute([$startDate, $endDate])->fetchColumn();

        return [
            'month' => $month,
            'year' => $year,
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount
        ];
    }
}

