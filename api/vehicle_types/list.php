<?php
// api/vehicle_types/list.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once __DIR__ . '/../config/database.php';

    if (!isset($conn) && !isset($db)) {
        throw new Exception('Connexion base de données introuvable. Vérifier config/database.php (variable $conn ou $db).');
    }
    $pdo = $conn ?? $db;

    // Paramètres GET
    $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

    $limit  = max(1, min(1000, $limit));
    $offset = max(0, $offset);

    // Requête simplifiée : uniquement les champs utiles pour le combo
    $sql = "
      SELECT 
        id,
        type AS label
      FROM vehicle_types
      ORDER BY type ASC
      LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items'   => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
