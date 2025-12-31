<?php
require_once "../utils/response.php";
require_once "../config/input.php";

$data = getInput();
if (empty($data['username']) || empty($data['password'])) {
    jsonResponse(["success" => false, "message" => "Username et mot de passe requis"], 400);
}

$username = trim($data['username']);
$password = $data['password'];

try {
    require_once "../config/database.php";

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $stored = $user['password'];

        // Vérification hashée
        if (is_string($stored) && strpos($stored, '$2y$') === 0) {
            if (!password_verify($password, $stored)) {
                jsonResponse(["success" => false, "message" => "Identifiants invalides"], 401);
            }
        } else {
            // Comparaison legacy (non hashée)
            if ($password !== $stored) {
                jsonResponse(["success" => false, "message" => "Identifiants invalides"], 401);
            }
        }

        // Succès
        jsonResponse([
            "success" => true,
            "message" => "Connexion réussie",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "role" => $user['role']
            ]
        ]);
    }
} catch (Throwable $e) {
    // Optionnel: log interne
    // error_log("Erreur login: " . $e->getMessage());
}

// Fallback config file
$cfgPath = __DIR__ . '/../config/auth.php';
if (file_exists($cfgPath)) {
    $cfg = require $cfgPath;
    if (isset($cfg['users'][$username])) {
        $stored = $cfg['users'][$username];
        if (is_string($stored) && strpos($stored, '$2y$') === 0) {
            if (password_verify($password, $stored)) {
                jsonResponse(["success" => true, "message" => "Connexion réussie", "user" => ["id" => 0, "role" => "user"]]);
            }
        } else {
            if ($password === $stored) {
                jsonResponse(["success" => true, "message" => "Connexion réussie", "user" => ["id" => 0, "role" => "user"]]);
            }
        }
    }
}

// Si rien n’a marché
jsonResponse(["success" => false, "message" => "Identifiants invalides"], 401);
