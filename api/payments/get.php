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
    $stmt = $conn->prepare("
        SELECT p.*, e.marque AS entry_marque, e.immatriculation AS entry_immatriculation
        FROM payments p
        JOIN entries e ON p.entry_id = e.id
        WHERE p.id = :id
    ");
    $stmt->execute([":id" => $id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        jsonResponse(["success" => false, "message" => "Paiement non trouvé"], 404);
    }

    jsonResponse([
        "success" => true,
        "message" => "Paiement récupéré avec succès",
        "data"    => $payment
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
