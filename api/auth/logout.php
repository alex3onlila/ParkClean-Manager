<?php
require_once "../utils/response.php";

session_start();

// Vider la session
$_SESSION = [];

// Supprimer le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Réponse JSON uniforme
jsonResponse([
    "success" => true,
    "message" => "Déconnexion réussie"
]);
