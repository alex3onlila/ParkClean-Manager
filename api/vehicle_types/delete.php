<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = isset($data["id"]) ? (int)$data["id"] : null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

// Vérification existence
$stmtCheck = $conn->prepare("SELECT id FROM vehicle_types WHERE id = :id");
$stmtCheck->execute([":id" => $id]);
if (!$stmtCheck->fetch()) {
    jsonResponse(["success" => false, "message" => "Type de véhicule introuvable"], 404);
}

try {
    $stmt = $conn->prepare("DELETE FROM vehicle_types WHERE id = :id");
    $stmt->execute([":id" => $id]);

    jsonResponse([
        "success" => true,
        "message" => "Type de véhicule supprimé avec succès",
        "id"      => $id
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
