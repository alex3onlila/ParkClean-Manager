<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Chemin vers votre base de données (ajustez selon votre structure)
$dbFile = __DIR__ . '/../../database/parkclean.db';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => "Connexion échouée"]);
    exit;
}

// Récupération de l'ID depuis l'URL (ex: get.php?id=3)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => "ID invalide"]);
    exit;
}

try {
    // On récupère les colonnes exactes nécessaires pour vehicles.js
    $stmt = $pdo->prepare("SELECT id, client_id, marque, immatriculation, type_id, image FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        echo json_encode(['success' => false, 'error' => "Véhicule introuvable"]);
        exit;
    }

    // On renvoie directement l'objet sans passer par formatItemResponse()
    echo json_encode($vehicle);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}