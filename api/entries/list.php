<?php
header("Content-Type: application/json; charset=UTF-8");

// Correction du chemin selon votre structure d'image (api/config/database.php)
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Détermination de la date cible (soit par recherche, soit aujourd'hui)
    $targetDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // 2. Nombre de jours à inclure (par défaut 7 jours pour le dashboard)
    $days = isset($_GET['days']) ? intval($_GET['days']) : 7;

    // 3. Définition de la plage horaire métier : 06h00 à 05h59
    // La plage commence $days jours avant la date cible
    $startDt = new DateTime($targetDate);
    $startDt->modify("-{$days} days");
    $startDt->setTime(6, 0, 0);

    $endDt = new DateTime($targetDate);
    $endDt->setTime(5, 59, 59); // Jour cible à 05h59

    // 3. Requête SQLite avec groupement logique par journée
    // Utilisation de || pour la concaténation SQLite
    $sql = "SELECT 
                e.*, 
                v.immatriculation, v.marque, 
                (c.nom || ' ' || c.prenom) AS client_nom,
                vt.type AS categorie
            FROM entries e
            JOIN vehicles v ON e.vehicle_id = v.id
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            WHERE e.date_enregistrement BETWEEN :start AND :end
            ORDER BY e.date_enregistrement ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':start' => $startDt->format('Y-m-d H:i:s'),
        ':end'   => $endDt->format('Y-m-d H:i:s')
    ]);

    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Structuration de la réponse pour le JS (Recherche & Impression)
    echo json_encode([
        "success" => true,
        "period" => [
            "label" => $startDt->format('d/m/Y'),
            "full" => "Du " . $startDt->format('d/m H:i') . " au " . $endDt->format('d/m H:i')
        ],
        "data" => $entries ?: []
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur SQL : " . $e->getMessage()
    ]);
}