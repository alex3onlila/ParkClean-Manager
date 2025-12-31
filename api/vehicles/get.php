<?php
declare(strict_types=1);
require_once "../config/database.php";
require_once "../utils/response.php";

// Validation de l'ID
$id = (int)($_GET["id"] ?? 0);
if (!$id) {
    echo notFoundResponse("ID de véhicule manquant");
    exit;
}

try {
    $sql = "
      SELECT v.id, v.client_id, v.marque, v.immatriculation, v.type_id, v.image,
             c.prenom, c.nom as client_nom,
             t.type AS type_nom
      FROM vehicles v
      JOIN clients c ON v.client_id = c.id
      JOIN vehicle_types t ON v.type_id = t.id
      WHERE v.id = :id
      LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([":id" => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Ajouter client_prenom pour la compatibilité avec le JavaScript
        $data['client_prenom'] = $data['prenom'];
        echo formatItemResponse($data, "Véhicule trouvé");
    } else {
        echo notFoundResponse("Véhicule");
    }
} catch (Exception $e) {
    echo handleDatabaseError($e, "récupération du véhicule");
}
