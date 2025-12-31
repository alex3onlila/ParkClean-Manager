<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();

// Champs requis
$required = ["id","entry_id","montant","mode_paiement"];
foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        jsonResponse(["success" => false, "message" => "Champ manquant: $f"], 400);
    }
}

$id = (int)$data["id"];

// Vérification existence du paiement
$stmtCheck = $conn->prepare("SELECT id FROM payments WHERE id = :id");
$stmtCheck->execute([":id" => $id]);
if (!$stmtCheck->fetch()) {
    jsonResponse(["success" => false, "message" => "Paiement introuvable"], 404);
}

// Vérification entry_id
$stmtEntry = $conn->prepare("SELECT id FROM entries WHERE id = :id");
$stmtEntry->execute([":id" => $data["entry_id"]]);
if (!$stmtEntry->fetch()) {
    jsonResponse(["success" => false, "message" => "Entrée introuvable"], 400);
}

try {
    $stmt = $conn->prepare("
        UPDATE payments SET
            entry_id = :entry_id,
            montant = :montant,
            mode_paiement = :mode_paiement
        WHERE id = :id
    ");

    $stmt->execute([
        ":id"           => $id,
        ":entry_id"     => (int)$data["entry_id"],
        ":montant"      => (float)$data["montant"],
        ":mode_paiement"=> trim($data["mode_paiement"])
    ]);

    if ($stmt->rowCount() > 0) {
        jsonResponse([
            "success" => true,
            "message" => "Paiement mis à jour avec succès",
            "id"      => $id
        ]);
    } else {
        jsonResponse([
            "success" => false,
            "message" => "Aucune modification effectuée"
        ], 400);
    }

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
