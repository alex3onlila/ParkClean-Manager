<?php
// api/clients/list.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception('Fichier de configuration introuvable: ../config/database.php');
    }
    require_once $configPath;

    if (!isset($conn) && !isset($db)) {
        throw new Exception('Connexion base de données introuvable. Vérifier config/database.php (variable $conn ou $db).');
    }
    $pdo = $conn ?? $db;

    // Paramètres GET
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $sort = $_GET['sort'] ?? 'recent';

    $limit = max(1, min(1000, $limit));
    $offset = max(0, $offset);

    switch ($sort) {
        case 'name':
            $orderBy = 'c.nom COLLATE NOCASE ASC, c.prenom COLLATE NOCASE ASC';
            break;
        case 'recent':
        default:
            // pas de created_at dans le schéma : fallback sur id DESC
            $orderBy = 'c.id DESC';
            break;
    }

    // Requête optimisée : LEFT JOIN agrégé pour compter véhicules.
    // Si clients.nbr_vehicules est renseigné, on l'utilise en fallback.
    $sql = "
      SELECT
        c.id,
        c.nom,
        c.prenom,
        c.email,
        c.telephone,
        c.image,
        NULL AS created_at, -- champ absent dans ton schéma ; renvoyé null pour compatibilité UI
        COALESCE(v.count, c.nbr_vehicules, 0) AS vehicles_count,
        c.nbr_vehicules,
        c.matricules_historique
      FROM clients c
      LEFT JOIN (
        SELECT client_id, COUNT(*) AS count
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
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB Error in api/clients/list.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données', 'details' => $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error in api/clients/list.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'details' => $e->getMessage()]);
    exit;
}
