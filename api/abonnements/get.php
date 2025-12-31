<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";

$id = $_GET['id'] ?? null;

if (!$id) jsonResponse(["success" => false, "message" => "ID manquant"], 400);

try {
    $stmt = $conn->prepare("SELECT * FROM abonnements WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($abonnement) {
        jsonResponse($abonnement);
    } else {
        jsonResponse(["success" => false, "message" => "Introuvable"], 404);
    }
} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => $e->getMessage()], 500);
}