<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = isset($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

try {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $client = $stmt->fetch();

    if (!$client) {
        jsonResponse(["success" => false, "message" => "Client non trouvé"], 404);
    }

    jsonResponse([
        "success" => true,
        "message" => "Client récupéré avec succès",
        "data"    => $client
    ]);

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
