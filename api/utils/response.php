<?php
/**
 * ParkClean Manager - Utilitaires de Réponse API
 * Standardise les formats de réponse pour tous les endpoints
 */

declare(strict_types=1);

// ============================================================================
// CONFIGURATION
// ============================================================================

/**
 * En-têtes de sécurité et JSON
 */
function setSecureJsonHeaders() {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}

// ============================================================================
// FORMATS DE RÉPONSE STANDARDISÉS
// ============================================================================

/**
 * Réponse de succès standard
 */
function successResponse($data = null, $message = 'Opération réussie', $code = 200) {
    setSecureJsonHeaders();
    http_response_code($code);
    return json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Réponse d'erreur standard
 */
function errorResponse($message = 'Une erreur est survenue', $code = 400, $details = null) {
    setSecureJsonHeaders();
    http_response_code($code);
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ];
    
    if ($details !== null) {
        $response['details'] = $details;
    }
    
    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * Réponse de validation échouée
 */
function validationErrorResponse($errors) {
    return errorResponse('Données invalides', 422, ['validation_errors' => $errors]);
}

/**
 * Réponse de ressource non trouvée
 */
function notFoundResponse($resource = 'Ressource') {
    return errorResponse($resource . ' non trouvé(e)', 404);
}

// ============================================================================
// FONCTIONS DE CONVENIENCE (Celles que vous utilisez dans create.php)
// ============================================================================

/**
 * FONCTION MANQUANTE : jsonResponse
 * C'est cette fonction que votre fichier create.php appelle à la ligne 84.
 */
function jsonResponse(array $data, int $code = 200) {
    setSecureJsonHeaders();
    http_response_code($code);
    
    // Si la clé success n'est pas définie, on tente de la deviner via le code HTTP
    if (!isset($data['success'])) {
        $data['success'] = ($code >= 200 && $code < 300);
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envoie une réponse et termine l'exécution
 */
function sendResponse($json) {
    setSecureJsonHeaders();
    echo $json;
    exit;
}

/**
 * Envoie une réponse de succès rapide
 */
function sendSuccess($data = null, $message = 'Opération réussie', $code = 200) {
    sendResponse(successResponse($data, $message, $code));
}

/**
 * Envoie une réponse d'erreur rapide
 */
function sendError($message = 'Une erreur est survenue', $code = 400) {
    sendResponse(errorResponse($message, $code));
}

// ============================================================================
// UTILITAIRES DE DONNÉES
// ============================================================================

/**
 * Extrait et valide les données JSON du body (Utile si getInput() échoue)
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('JSON invalide', 400);
    }
    
    return $data ?: [];
}

/**
 * Sanitisation basique des données
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}