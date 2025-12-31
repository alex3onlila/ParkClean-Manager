<?php
// public/api/vehicles/list.php
header('Content-Type: application/json; charset=utf-8');

// Inclusion de la connexion avec gestion d'erreur silencieuse
try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur config DB: ' . $e->getMessage()]);
    exit;
}

try {
    // Requête SQL optimisée avec jointures
    $sql = "SELECT v.*, 
                   (c.nom || ' ' || c.prenom) AS client_nom, 
                   vt.type AS type_nom,
                   vt.prix_lavage
            FROM vehicles v
            LEFT JOIN clients c ON v.client_id = c.id
            LEFT JOIN vehicle_types vt ON v.type_id = vt.id
            ORDER BY v.id DESC";
            
    $stmt = $conn->query($sql);
    $vehicles = $stmt->fetchAll();

    // Toujours renvoyer un tableau, même vide
    echo json_encode($vehicles ?: []);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}
?>