<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/input.php";
require_once __DIR__ . "/../../utils/response.php";

$data = getInput();

// Validation minimale
if (empty($data['vehicle_id']) || empty($data['date_debut']) || empty($data['date_fin'])) {
    jsonResponse(["success" => false, "message" => "Données incomplètes"], 400);
}

try {
    // Calculs serveur pour sécurité (Reste = Total - Reçu)
    $total = (float)$data['montant_total'];
    $recu = (float)$data['montant_recu'];
// Calcule le solde sans jamais descendre en dessous de zéro
    $restant = max(0, $total - $recu);
    if (!empty($data['id'])) {
        // UPDATE
        $sql = "UPDATE abonnements SET 
                vehicle_id = :vid, date_debut = :dd, date_fin = :df, 
                montant_total = :mt, montant_recu = :mr, montant_restant = :mrest, obs = :obs
                WHERE id = :id";
        $params = [
            ':id' => $data['id'], ':vid' => $data['vehicle_id'], ':dd' => $data['date_debut'], ':df' => $data['date_fin'],
            ':mt' => $total, ':mr' => $recu, ':mrest' => $restant, ':obs' => $data['obs'] ?? ''
        ];
    } else {
        // INSERT
        $sql = "INSERT INTO abonnements (vehicle_id, date_debut, date_fin, montant_total, montant_recu, montant_restant, obs) 
                VALUES (:vid, :dd, :df, :mt, :mr, :mrest, :obs)";
        $params = [
            ':vid' => $data['vehicle_id'], ':dd' => $data['date_debut'], ':df' => $data['date_fin'],
            ':mt' => $total, ':mr' => $recu, ':mrest' => $restant, ':obs' => $data['obs'] ?? ''
        ];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    jsonResponse(["success" => true, "message" => "Abonnement enregistré avec succès"]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur DB: " . $e->getMessage()], 500);
}