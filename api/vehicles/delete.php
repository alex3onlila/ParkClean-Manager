<?php
declare(strict_types=1);
require_once "../config/database.php";

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true) ?? [];

if (empty($data["id"])) {
    echo json_encode(["success" => false, "message" => "ID manquant"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id=:id");
    $stmt->execute([":id" => $data["id"]]);

    echo json_encode(["success" => true, "message" => "Véhicule supprimé"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
