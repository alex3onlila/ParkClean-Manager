<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = isset($data["id"]) ? (int)$data["id"] : null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

try {
    $stmt = $conn->prepare("SELECT * FROM vehicle_types WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $type = $stmt->fetch();

    if (!$type) {
        jsonResponse(["success" => false, "message" => "Type de véhicule non trouvé"], 404);
    }

    jsonResponse([
        "success" => true,
        "message" => "Type de véhicule récupéré avec succès",
        "data"    => $type
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
