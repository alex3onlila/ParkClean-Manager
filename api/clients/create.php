<?php
require_once "../config/database.php";
require_once "../config/input.php";
require_once "../utils/response.php";

$data = getInput();

// Validation minimale
if (empty($data['nom']) || empty($data['prenom'])) {
    jsonResponse([
        "success" => false,
        "message" => "Champs obligatoires manquants (nom, prenom)"
    ], 400);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO clients
        (nom, prenom, email, telephone, nbr_vehicules, matricules_historique, image)
        VALUES (:nom, :prenom, :email, :telephone, :nbr_vehicules, :matricules_historique, :image)
    ");

    $stmt->execute([
        ":nom"                  => trim($data["nom"]),
        ":prenom"               => trim($data["prenom"]),
        ":email"                => $data["email"] ?? null,
        ":telephone"            => $data["telephone"] ?? null,
        ":nbr_vehicules"        => isset($data["nbr_vehicules"]) ? (int)$data["nbr_vehicules"] : 0,
        ":matricules_historique"=> $data["matricules_historique"] ?? null,
        ":image"                => $data["image"] ?? null
    ]);

    jsonResponse([
        "success" => true,
        "message" => "Client créé avec succès",
        "id"      => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    jsonResponse([
        "success" => false,
        "message" => "Erreur SQL",
        "details" => $e->getMessage()
    ], 500);
}
