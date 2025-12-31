<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();
$entry_id = isset($data["entry_id"]) ? (int)$data["entry_id"] : null;

try {
    $sql = "SELECT p.*, e.marque AS entry_marque, e.immatriculation AS entry_immatriculation
            FROM payments p
            JOIN entries e ON p.entry_id = e.id";

    $params = [];
    if ($entry_id) {
        $sql .= " WHERE p.entry_id = :entry_id";
        $params[":entry_id"] = $entry_id;
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    jsonResponse([
        "success" => true,
        "message" => "Liste des paiements récupérée avec succès",
        "count"   => count($payments),
        "items"   => $payments
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur SQL", "details" => $e->getMessage()], 500);
}
