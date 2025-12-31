<?php
// api/clients/update.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/input.php';
require_once __DIR__ . '/../utils/response.php';

$data = getInput();

// Vérifier ID
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID invalide'], 400);
}

// Validation minimale (si fournis)
if (array_key_exists('nom', $data) && trim((string)$data['nom']) === '') {
    jsonResponse(['success' => false, 'message' => 'Le champ nom ne peut pas être vide'], 400);
}
if (array_key_exists('prenom', $data) && trim((string)$data['prenom']) === '') {
    jsonResponse(['success' => false, 'message' => 'Le champ prenom ne peut pas être vide'], 400);
}

try {
    // Vérifier existence du client
    $check = $conn->prepare("SELECT id FROM clients WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(['success' => false, 'message' => 'Client introuvable'], 404);
    }

    // Champs autorisés à la mise à jour et leur type
    $allowed = [
        'nom' => 'str',
        'prenom' => 'str',
        'email' => 'str',
        'telephone' => 'str',
        'image' => 'str',
        'nbr_vehicules' => 'int',
        'matricules_historique' => 'str'
    ];

    $sets = [];
    $params = [':id' => $id];

    foreach ($allowed as $field => $type) {
        if (array_key_exists($field, $data)) {
            $val = $data[$field];
            if ($type === 'str') {
                $val = $val === null ? null : trim((string)$val);
                if ($val === '') $val = null;
            } else { // int
                $val = ($val === null || $val === '') ? 0 : (int)$val;
            }
            $sets[] = "$field = :$field";
            $params[":$field"] = $val;
        }
    }

    if (empty($sets)) {
        jsonResponse(['success' => false, 'message' => 'Aucun champ à mettre à jour'], 400);
    }

    // Exécuter la mise à jour
    $sql = "UPDATE clients SET " . implode(', ', $sets) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        if ($k === ':nbr_vehicules') {
            $stmt->bindValue($k, (int)$v, PDO::PARAM_INT);
        } elseif ($v === null) {
            $stmt->bindValue($k, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
    }
    $stmt->execute();

    // Récupérer l'enregistrement mis à jour
    $sel = $conn->prepare("SELECT id, nom, prenom, email, telephone, image, nbr_vehicules, matricules_historique FROM clients WHERE id = :id LIMIT 1");
    $sel->execute([':id' => $id]);
    $updated = $sel->fetch(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'message' => 'Client mis à jour', 'data' => $updated]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur base de données', 'details' => $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur serveur', 'details' => $e->getMessage()], 500);
}
