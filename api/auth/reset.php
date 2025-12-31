<?php
require_once "../utils/response.php";
require_once "../config/input.php";

header('Content-Type: application/json; charset=utf-8');

$data = getInput();
$user = $data['username'] ?? '';
$token = $data['token'] ?? '';
$new   = $data['new_password'] ?? '';

if (!$user || !$token || !$new) {
    jsonResponse(["success" => false, "message" => "username, token et new_password requis"], 400);
}

$resetsFile = __DIR__ . '/../../data/resets.json';
if (!file_exists($resetsFile)) {
    jsonResponse(["success" => false, "message" => "aucun token disponible"], 400);
}

$resets = json_decode(file_get_contents($resetsFile), true) ?: [];
if (!isset($resets[$token])) {
    jsonResponse(["success" => false, "message" => "token invalide"], 400);
}

$entry = $resets[$token];
if ($entry['username'] !== $user) {
    jsonResponse(["success" => false, "message" => "le token ne correspond pas à l’utilisateur"], 400);
}

if (time() > ($entry['expires'] ?? 0)) {
    unset($resets[$token]);
    file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));
    jsonResponse(["success" => false, "message" => "token expiré"], 400);
}

// --- Mise à jour du mot de passe ---
$updated = false;
$hash = password_hash($new, PASSWORD_DEFAULT);

try {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $conn->prepare("UPDATE users SET password = :p WHERE username = :u");
    $stmt->execute([":p" => $hash, ":u" => $user]);
    if ($stmt->rowCount() > 0) {
        $updated = true;
    }
} catch (Throwable $e) {
    // Optionnel: log interne
    // error_log("Erreur reset password: " . $e->getMessage());
}

// --- Fallback fichier config ---
if (!$updated) {
    $cfgPath = __DIR__ . '/../config/auth.php';
    if (!file_exists($cfgPath)) {
        jsonResponse(["success" => false, "message" => "auth config manquant et mise à jour DB échouée"], 500);
    }

    $cfg = require $cfgPath;
    if (!isset($cfg['users'][$user])) {
        jsonResponse(["success" => false, "message" => "utilisateur introuvable"], 404);
    }

    $cfg['users'][$user] = $hash;
    $php = "<?php\nreturn " . var_export($cfg, true) . ";\n";
    if (file_put_contents($cfgPath, $php) === false) {
        jsonResponse(["success" => false, "message" => "échec d’écriture du fichier config"], 500);
    }
}

// --- Nettoyage du token ---
unset($resets[$token]);
file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));

// --- Réponse finale ---
jsonResponse(["success" => true, "message" => "Mot de passe mis à jour avec succès"]);
jsonResponse(["success" => false, "message" => "Identifiants invalides"], 401);
		