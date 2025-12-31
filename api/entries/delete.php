<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = $data['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

try {
    $stmt = $conn->prepare("DELETE FROM entries WHERE id = :id");
    $stmt->execute([':id' => $id]);
    jsonResponse(["success" => true, "message" => "Entrée supprimée"]);
} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}