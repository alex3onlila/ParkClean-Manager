<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/input.php";
require_once __DIR__ . "/../utils/response.php";

$data = getInput();

if (empty($data['id'])) jsonResponse(["success" => false, "message" => "ID manquant"], 400);

try {
    $stmt = $conn->prepare("DELETE FROM abonnements WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    jsonResponse(["success" => true, "message" => "Supprimé"]);
} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}