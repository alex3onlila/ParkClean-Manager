<?php
declare(strict_types=1);
require_once "../config/database.php";

header("Content-Type: application/json; charset=utf-8");

// 1. Support mixte : JSON ou FormData (pour les uploads d'images)
$id = $_POST['id'] ?? null;
$client_id = $_POST['client_id'] ?? null;
$marque = $_POST['marque'] ?? "";
$immatriculation = $_POST['immatriculation'] ?? "";
$type_id = $_POST['type_id'] ?? null;
$image_path = $_POST['image_old'] ?? ""; // Garder l'ancienne image par défaut

// 2. Gestion de l'upload d'image (si un fichier est envoyé)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "../../public/uploads/vehicles/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = "v_" . time() . "_" . uniqid() . "." . $file_ext;
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = "uploads/vehicles/" . $file_name;
    }
}

try {
    if ($id) {
        // --- MODE UPDATE ---
        $sql = "UPDATE vehicles SET 
                client_id = :client_id, 
                marque = :marque, 
                immatriculation = :immatriculation, 
                type_id = :type_id, 
                image = :image 
                WHERE id = :id";
        $params = [
            ":id" => $id,
            ":client_id" => $client_id,
            ":marque" => $marque,
            ":immatriculation" => $immatriculation,
            ":type_id" => $type_id,
            ":image" => $image_path
        ];
        $message = "Véhicule mis à jour";
    } else {
        // --- MODE INSERT ---
        $sql = "INSERT INTO vehicles (client_id, marque, immatriculation, type_id, image)
                VALUES (:client_id, :marque, :immatriculation, :type_id, :image)";
        $params = [
            ":client_id" => $client_id,
            ":marque" => $marque,
            ":immatriculation" => $immatriculation,
            ":type_id" => $type_id,
            ":image" => $image_path
        ];
        $message = "Véhicule créé";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["success" => true, "message" => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur SQL : " . $e->getMessage()]);
}