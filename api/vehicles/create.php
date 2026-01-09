<?php
declare(strict_types=1);
require_once "../config/database.php";
require_once "../config/input.php";

header("Content-Type: application/json; charset=utf-8");

// 1. Récupération des données (JSON ou POST)
$data = getInput();

// 2. Gestion de l'upload d'image (si un fichier est envoyé)
$image_path = $data['image'] ?? "";

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "../../public/uploads/vehicles/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = "v_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = "uploads/vehicles/" . $file_name;
    }
}

// 3. Validation des champs obligatoires
if (empty($data['marque']) || empty($data['immatriculation'])) {
    echo json_encode(["success" => false, "message" => "Marque et immatriculation requises"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO vehicles (client_id, marque, immatriculation, type_id, image)
        VALUES (:client_id, :marque, :immatriculation, :type_id, :image)
    ");

    $stmt->execute([
        ":client_id"       => !empty($data['client_id']) ? $data['client_id'] : null,
        ":marque"          => trim($data['marque']),
        ":immatriculation" => strtoupper(trim($data['immatriculation'])),
        ":type_id"         => !empty($data['type_id']) ? $data['type_id'] : null,
        ":image"           => $image_path
    ]);

    echo json_encode([
        "success" => true, 
        "message" => "Véhicule créé avec succès",
        "id"      => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur SQL : " . $e->getMessage()]);
}
