<?php
require_once "../config/database.php";

// ⚠️ Assure-toi d'avoir installé PhpSpreadsheet via Composer :
// composer require phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$date = $_GET["date"] ?? date("Y-m-d");

try {
    $sql = "
        SELECT v.immatriculation, s.name AS service, e.prix, e.date_entree
        FROM entries e
        JOIN vehicles v ON v.id = e.vehicle_id
        JOIN services s ON s.id = e.service_id
        WHERE DATE(e.date_entree) = :date
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":date" => $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Création du fichier Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // En-têtes
    $headers = ["Immatriculation","Service","Prix","Date"];
    $sheet->fromArray($headers, null, "A1");

    // Données
    $sheet->fromArray($rows, null, "A2");

    // Style simple
    $sheet->getStyle("A1:D1")->getFont()->setBold(true);

    // Nom du fichier
    $filename = "rapport_$date.xlsx";

    // Headers HTTP
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: max-age=0");

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

} catch (PDOException $e) {
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
