<?php
require_once "../config/database.php";
require_once "../config/input.php";

$data = getInput();
$date = $data["date"] ?? ($_GET["date"] ?? date("Y-m-d"));

try {
    // Sélectionner uniquement les colonnes existantes
    $sql = "
        SELECT v.immatriculation,
               v.marque,
               v.type_id,
               e.montant_recu,
        FROM entries e
        JOIN vehicles v ON v.id = e.vehicle_id
        ORDER BY e.date_entree ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":date" => $date]);

    require_once "../../vendor/fpdf/fpdf.php";

    $pdf = new FPDF();
    $pdf->AddPage();

    // Titre
    $pdf->SetFont("Arial","B",14);
    $pdf->Cell(0,10,"Rapport Journalier - $date",0,1,"C");

    // En-têtes du tableau
    $pdf->SetFont("Arial","B",10);
    $pdf->Cell(50,8,"Immatriculation",1);
    $pdf->Cell(50,8,"Marque",1);
    $pdf->Cell(40,8,"Montant",1);
    $pdf->Ln();

    // Données
    $pdf->SetFont("Arial","",10);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(50,8,$row["immatriculation"],1);
        $pdf->Cell(50,8,$row["marque"],1);
        $pdf->Cell(40,8,number_format($row["montant_recu"],0,","," "),1,0,"R");
        $pdf->Ln();
    }

    $pdf->Output("D","rapport_$date.pdf"); // téléchargement direct

} catch (PDOException $e) {
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
