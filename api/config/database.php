<?php
// Toujours JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

try {
    // Fichier SQLite
    $db_file = __DIR__ . '/../../database/parkclean.db';

    // Connexion PDO
    $conn = new PDO("sqlite:" . $db_file);

    // Options PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Activer les clés étrangères
    $conn->exec("PRAGMA foreign_keys = ON");

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur connexion DB',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
