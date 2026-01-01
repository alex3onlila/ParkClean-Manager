<?php
declare(strict_types=1);
require_once "../config/database.php"; 

header("Content-Type: application/json; charset=utf-8");

// 1. Récupération des données via $_POST (compatible FormData JS)
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID manquant"]);
    exit;
}

try {
    // 2. Récupérer l'image actuelle en base de données pour ne pas l'écraser par du vide
    $stmtImg = $conn->prepare("SELECT image FROM vehicles WHERE id = ?");
    $stmtImg->execute([$id]);
    $currentVehicle = $stmtImg->fetch();
    $imagePath = $currentVehicle['image'] ?? ""; 

    // 3. Gestion de l'upload d'une nouvelle image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Chemin physique sur le serveur pour déplacer le fichier
        $uploadDir = __DIR__ . '/../../public/uploads/vehicles/'; 
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Chemin relatif pour le navigateur (URL)
            // Note: On retire "public/" si votre serveur pointe déjà sur ce dossier
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
        ":client_id"       => $_POST["client_id"] ?? null,
        ":marque"          => $_POST["marque"] ?? "",
        ":immatriculation" => $_POST["immatriculation"] ?? "",
        ":type_id"         => $_POST["type_id"] ?? null,
        ":image"           => $imagePath
    ]);

    echo json_encode(["success" => true, "message" => "Véhicule mis à jour avec succès"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur serveur : " . $e->getMessage()]);
}