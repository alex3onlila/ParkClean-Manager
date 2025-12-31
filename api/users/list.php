<?php
// api/users/list.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
  $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  $limit = max(1, min(1000, $limit));
  $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users ORDER BY id DESC LIMIT :limit");
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE);
}
