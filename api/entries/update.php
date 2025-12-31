<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();

if (!isset($data['id'])) {
    jsonResponse(["success" => false, "message" => "ID manquant"], 400);
}

try {
    // Recalcul du montant restant si le montant reçu change
    $stmtCheck = $conn->prepare("SELECT montant_total FROM entries WHERE id = :id");
    $stmtCheck->execute([':id' => $data['id']]);
    $entry = $stmtCheck->fetch();

    if (!$entry) jsonResponse(["success" => false, "message" => "Entrée introuvable"], 404);

    $montant_recu = (float)($data['montant_recu'] ?? 0);
    $montant_restant = (float)$entry['montant_total'] - $montant_recu;

    $stmt = $conn->prepare("
        UPDATE entries SET 
            date_enregistrement = :date,
            montant_recu = :recu,
            montant_restant = :restant,
            est_entree = :entree,
            est_sorti = :sorti,
            obs = :obs
        WHERE id = :id
    ");

    $stmt->execute([
        ":id"      => $data["id"],
        ":date"    => $data["date_enregistrement"],
        ":recu"    => $montant_recu,
        ":restant" => $montant_restant,
        ":entree"  => (int)$data["est_entree"],
        ":sorti"   => (int)$data["est_sorti"],
        ":obs"     => $data["obs"]
    ]);

    jsonResponse(["success" => true, "message" => "Entrée mise à jour"]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}