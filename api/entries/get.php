<?php
require_once "../config/database.php";
require_once "../utils/response.php";

// Récupération de l'ID depuis la query string (?id=...) ou le corps JSON
$id = $_GET['id'] ?? null;

if (!$id) {
    // Si pas en GET, on regarde dans le flux d'entrée JSON
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
}

if (!$id) {
    jsonResponse(["success" => false, "message" => "ID de l'entrée manquant"], 400);
}

try {
    // Requête détaillée avec jointures
    $sql = "SELECT 
                e.*, 
                v.immatriculation, 
                v.marque, 
                (c.nom || ' ' || c.prenom) AS client_nom,
                (c.telephone) AS client_telephone,
                vt.type AS categorie_nom,
                vt.prix_lavage AS tarif_standard
            FROM entries e
            JOIN vehicles v ON e.vehicle_id = v.id
            JOIN clients c ON v.client_id = c.id
            JOIN vehicle_types vt ON v.type_id = vt.id
            WHERE e.id = :id";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $entry = $stmt->fetch();

    if (!$entry) {
        jsonResponse(["success" => false, "message" => "Entrée introuvable"], 404);
    }

    // Renvoie l'objet complet
    jsonResponse($entry);

} catch (PDOException $e) {
    jsonResponse([
        "success" => false, 
        "message" => "Erreur lors de la récupération",
        "details" => $e->getMessage()
    ], 500);
}