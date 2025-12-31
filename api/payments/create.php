<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();

// Champs obligatoires
$required = ["entry_id","montant","mode_paiement"];
foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        jsonResponse(["success" => false, "message" => "Champ manquant: $f"], 400);
    }
}

// Vérification entry_id
$stmtEntry = $conn->prepare("SELECT id FROM entries WHERE id = :id");
$stmtEntry->execute([":id" => $data["entry_id"]]);
if (!$stmtEntry->fetch()) {
    jsonResponse(["success" => false, "message" => "Entrée introuvable"], 400);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO payments (entry_id, montant, mode_paiement)
        VALUES (:entry_id, :montant, :mode_paiement)
    ");

    $stmt->execute([
        ":entry_id"     => (int)$data["entry_id"],
        ":montant"      => (float)$data["montant"],
        ":mode_paiement"=> trim($data["mode_paiement"])
    ]);

    jsonResponse([
        "success" => true,
        "message" => "Paiement enregistré avec succès",
        "id"      => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
