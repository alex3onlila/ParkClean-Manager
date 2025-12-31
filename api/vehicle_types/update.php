<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$required = ["id","type","prix_lavage"];
foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        jsonResponse(["success" => false, "message" => "Champ manquant: $f"], 400);
    }
}

$id = (int)$data["id"];

// Vérification existence
$stmtCheck = $conn->prepare("SELECT id FROM vehicle_types WHERE id = :id");
$stmtCheck->execute([":id" => $id]);
if (!$stmtCheck->fetch()) {
    jsonResponse(["success" => false, "message" => "Type de véhicule introuvable"], 404);
}

try {
    $stmt = $conn->prepare("
        UPDATE vehicle_types SET
            type = :type,
            prix_lavage = :prix_lavage
        WHERE id = :id
    ");
    $stmt->execute([
        ":id"         => $id,
        ":type"       => trim($data["type"]),
        ":prix_lavage"=> (float)$data["prix_lavage"]
    ]);

    jsonResponse([
        "success" => true,
        "message" => "Type de véhicule mis à jour avec succès",
        "id"      => $id
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
