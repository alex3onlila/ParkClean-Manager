<?php
declare(strict_types=1);
require_once "../config/database.php";
require_once "../config/input.php";

header("Content-Type: application/json; charset=utf-8");

// 1. Récupération des données (JSON ou POST)
$data = getInput();

// 2. Validation de l'ID
$id = !empty($data['id']) ? $data['id'] : null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID manquant"]);
    exit;
}

// 3. Gestion de l'upload d'image (si un fichier est envoyé)
try {
    // Récupérer l'image actuelle en base de données pour ne pas l'écraser par du vide
    $stmtImg = $conn->prepare("SELECT image FROM vehicles WHERE id = ?");
    $stmtImg->execute([$id]);
    $currentVehicle = $stmtImg->fetch();
    $imagePath = $currentVehicle['image'] ?? ""; 

    // Gestion de l'upload d'une nouvelle image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/uploads/vehicles/'; 
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'public/uploads/vehicles/' . $fileName;
        }
    }

    // 4. Mise à jour de la base de données
    $sql = "UPDATE vehicles 
            SET client_id = :client_id, 
                marque = :marque, 
                immatriculation = :immatriculation, 
                type_id = :type_id, 
                image = :image 
            WHERE id = :id";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id"              => $id,
        ":client_id"       => !empty($data["client_id"]) ? $data["client_id"] : null,
        ":marque"          => trim($data["marque"]),
        ":immatriculation" => strtoupper(trim($data["immatriculation"])),
        ":type_id"         => !empty($data["type_id"]) ? $data["type_id"] : null,
        ":image"           => $imagePath
    ]);

    echo json_encode(["success" => true, "message" => "Véhicule mis à jour avec succès"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur serveur : " . $e->getMessage()]);
}
