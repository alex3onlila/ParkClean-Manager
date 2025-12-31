<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$id = isset($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

// Vérifier si le paiement existe
$stmtCheck = $conn->prepare("SELECT id FROM payments WHERE id = :id");
$stmtCheck->execute([":id" => $id]);
if (!$stmtCheck->fetch()) {
    jsonResponse(["success" => false, "message" => "Paiement introuvable"], 404);
}

try {
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        jsonResponse([
            "success" => true,
            "message" => "Paiement supprimé avec succès",
            "id"      => $id
        ]);
    } else {
        jsonResponse([
            "success" => false,
            "message" => "Aucun paiement supprimé"
        ], 400);
    }

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
