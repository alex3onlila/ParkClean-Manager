<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();

// Champs requis
$required = ["type","prix_lavage"];
foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        jsonResponse(["success" => false, "message" => "Champ manquant: $f"], 400);
    }
}

try {
    $stmt = $conn->prepare("
        INSERT INTO vehicle_types (type, prix_lavage)
        VALUES (:type, :prix_lavage)
    ");
    $stmt->execute([
        ":type"        => trim($data["type"]),
        ":prix_lavage" => (float)$data["prix_lavage"]
    ]);

    jsonResponse([
        "success" => true,
        "message" => "Type de véhicule créé avec succès",
        "id"      => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
