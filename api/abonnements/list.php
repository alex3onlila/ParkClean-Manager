<?php
// Désactiver l'affichage des erreurs HTML pour ne pas corrompre le JSON
ini_set('display_errors', 0); 
header('Content-Type: application/json');

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";

try {
    $today = date('Y-m-d');
    
    // Jointure simplifiée
    $sql = "SELECT a.*, 
                   v.immatriculation, 
                   c.nom, c.prenom,
                   vt.type as type_vehicule
            FROM abonnements a
            JOIN vehicles v ON a.vehicle_id = v.id
            JOIN clients c ON v.client_id = c.id
            JOIN vehicle_types vt ON v.type_id = vt.id
            ORDER BY a.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajout manuel de la logique de validité pour éviter les erreurs SQL complexes
    foreach ($subs as &$sub) {
        $sub['est_valide'] = ($today >= $sub['date_debut'] && $today <= $sub['date_fin']);
    }

    echo json_encode($subs);

} catch (PDOException $e) {
    // Si ça plante, on renvoie l'erreur exacte en JSON
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Erreur SQL : " . $e->getMessage()
    ]);
}