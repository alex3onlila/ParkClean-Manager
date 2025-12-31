<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

header("Content-Type: application/json");

// Récupération de la date depuis GET ou JSON
$data = getInput();
$date = $data["date"] ?? ($_GET["date"] ?? date("Y-m-d"));

try {
    $sql = "
        SELECT 
            COUNT(e.id) AS total_vehicules,
            SUM(CASE WHEN s.name = 'LAVAGE' THEN 1 ELSE 0 END) AS total_lavage,
            SUM(CASE WHEN s.name = 'GARDIENNAGE' THEN 1 ELSE 0 END) AS total_gardiennage,
            SUM(e.prix) AS chiffre_affaires
        FROM entries e
        JOIN services s ON s.id = e.service_id
        WHERE DATE(e.date_entree) = :date
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":date" => $date]);
    $rapport = $stmt->fetch();

    jsonResponse([
        "success" => true,
        "message" => "Rapport journalier généré avec succès",
        "date"    => $date,
        "rapport" => $rapport
    ]);

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
