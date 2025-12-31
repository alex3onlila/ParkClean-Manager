<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/input.php";
require_once __DIR__ . "/../utils/response.php";

$data = getInput();

// Vérification des champs essentiels
if (empty($data['vehicle_id']) || empty($data['date_debut']) || empty($data['date_fin'])) {
    jsonResponse(["success" => false, "message" => "Données incomplètes"], 400);
}

try {
    // Calcul automatique du solde restant (ne descend jamais sous zéro)
    $montant_total = (float)$data['montant_total'];
    $montant_recu = (float)$data['montant_recu'];
    $montant_restant = ($montant_total - $montant_recu > 0) ? ($montant_total - $montant_recu) : 0;

    $sql = "INSERT INTO abonnements (vehicle_id, date_debut, date_fin, montant_total, montant_recu, montant_restant, obs, est_actif) 
            VALUES (:vid, :dd, :df, :mt, :mr, :mrest, :obs, 1)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':vid'   => $data['vehicle_id'],
        ':dd'    => $data['date_debut'],
        ':df'    => $data['date_fin'],
        ':mt'    => $montant_total,
        ':mr'    => $montant_recu,
        ':mrest' => $montant_restant,
        ':obs'   => $data['obs'] ?? ''
    ]);

    jsonResponse(["success" => true, "id" => $conn->lastInsertId(), "message" => "Abonnement créé"]);
} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}