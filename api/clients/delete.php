<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = isset($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

// Vérifier si le client existe
$stmtCheck = $conn->prepare("SELECT id FROM clients WHERE id = :id");
$stmtCheck->execute([":id" => $id]);
if (!$stmtCheck->fetch()) {
    jsonResponse(["success" => false, "message" => "Client introuvable"], 404);
}

try {
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        jsonResponse([
            "success" => true,
            "message" => "Client supprimé avec succès",
            "id"      => $id
        ]);
    } else {
        jsonResponse([
            "success" => false,
            "message" => "Aucun client supprimé"
        ], 400);
    }

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
