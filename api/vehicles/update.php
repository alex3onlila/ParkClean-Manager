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
    $sql = "UPDATE vehicles
            SET client_id=:client_id, marque=:marque, immatriculation=:immatriculation,
                type_id=:type_id, image=:image
            WHERE id=:id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => $data["id"],
        ":client_id" => $data["client_id"] ?? null,
        ":marque" => $data["marque"] ?? "",
        ":immatriculation" => $data["immatriculation"] ?? "",
        ":type_id" => $data["type_id"] ?? null,
        ":image" => $data["image"] ?? ""
    ]);

    echo json_encode(["success" => true, "message" => "Véhicule mis à jour"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
