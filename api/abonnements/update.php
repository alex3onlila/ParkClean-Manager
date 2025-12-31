<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/input.php";
require_once __DIR__ . "/../utils/response.php";

$data = getInput();

if (empty($data['id'])) jsonResponse(["success" => false, "message" => "ID manquant"], 400);

try {
    $montant_total = (float)$data['montant_total'];
    $montant_recu = (float)$data['montant_recu'];
    $montant_restant = ($montant_total - $montant_recu > 0) ? ($montant_total - $montant_recu) : 0;

    $sql = "UPDATE abonnements SET 
            vehicle_id = :vid, date_debut = :dd, date_fin = :df, 
            montant_total = :mt, montant_recu = :mr, montant_restant = :mrest, obs = :obs 
            WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id'    => $data['id'],
        ':vid'   => $data['vehicle_id'],
        ':dd'    => $data['date_debut'],
        ':df'    => $data['date_fin'],
        ':mt'    => $montant_total,
        ':mr'    => $montant_recu,
        ':mrest' => $montant_restant,
        ':obs'   => $data['obs'] ?? ''
    ]);

    jsonResponse(["success" => true, "message" => "Mise à jour réussie"]);
} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}