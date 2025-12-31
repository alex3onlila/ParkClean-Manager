<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/input.php";
require_once __DIR__ . "/../utils/response.php";

// Récupère les données JSON envoyées par fetch()
$data = getInput();

// Champs requis
$required = ["vehicle_id", "montant_recu"];

foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === "") {
        jsonResponse(["success" => false, "message" => "Champ manquant: $f"], 400);
    }
}

try {
    // 1. CONDITION DE SÉCURITÉ : Vérifier si le véhicule est déjà présent aujourd'hui
    // On vérifie s'il existe une entrée pour ce véhicule aujourd'hui qui n'est PAS encore sortie
    $stmtCheck = $conn->prepare("
        SELECT id FROM entries 
        WHERE vehicle_id = :vid 
        AND DATE(date_enregistrement) = CURRENT_DATE
        AND est_sorti = 0
    ");
    $stmtCheck->execute([':vid' => $data["vehicle_id"]]);

    if ($stmtCheck->fetch()) {
        jsonResponse([
            "success" => false, 
            "message" => "Impossible : Ce véhicule est déjà enregistré au parking et n'est pas encore sorti."
        ], 400);
        exit;
    }

    // 2. Récupérer le prix automatique basé sur le type du véhicule
    $stmtV = $conn->prepare("
        SELECT v.id, vt.prix_lavage 
        FROM vehicles v
        JOIN vehicle_types vt ON v.type_id = vt.id
        WHERE v.id = :id
    ");
    $stmtV->execute([":id" => $data["vehicle_id"]]);
    $vehicle = $stmtV->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        jsonResponse(["success" => false, "message" => "Véhicule introuvable"], 404);
    }

    // 3. Calculs financiers automatisés
    $montant_total = (float)$vehicle['prix_lavage'];
    $montant_recu = (float)$data["montant_recu"];
    $montant_restant = $montant_total - $montant_recu;

    // 4. Insertion dans la table entries
    $sql = "INSERT INTO entries 
            (vehicle_id, date_enregistrement, montant_total, montant_recu, montant_restant, est_entree, est_sorti, obs)
            VALUES 
            (:vid, :date, :total, :recu, :restant, :entree, :sorti, :obs)";
    
    $stmt = $conn->prepare($sql);

    // Formatage de la date (HTML datetime-local vers SQL)
    $date_enregistrement = !empty($data["date_enregistrement"]) 
        ? str_replace('T', ' ', $data["date_enregistrement"]) 
        : date('Y-m-d H:i:s');

    // Gestion des statuts (Booléens convertis en Int)
    $est_entree = (isset($data["est_entree"]) && $data["est_entree"] == 1) ? 1 : 1; // Par défaut 1 (OK)
    $est_sorti  = (isset($data["est_sorti"]) && $data["est_sorti"] == 1) ? 1 : 0;   // Par défaut 0 (Parking)

    $stmt->execute([
        ":vid"     => $data["vehicle_id"],
        ":date"    => $date_enregistrement,
        ":total"   => $montant_total,
        ":recu"    => $montant_recu,
        ":restant" => $montant_restant,
        ":entree"  => $est_entree,
        ":sorti"   => $est_sorti,
        ":obs"     => trim($data["obs"] ?? "")
    ]);

    jsonResponse([
        "success" => true, 
        "message" => "Opération enregistrée : Véhicule au parking.",
        "id" => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Erreur Base de données: " . $e->getMessage()], 500);
}