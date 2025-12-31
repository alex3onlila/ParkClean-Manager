<?php
/**
 * ParkClean Manager - Utilitaires de Réponse API
 * Standardise les formats de réponse pour tous les endpoints
 */

declare(strict_types=1);

// ============================================================================
// CONFIGURATION
// ============================================================================

// En-têtes de sécurité et JSON
function setSecureJsonHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// ============================================================================
// FORMATS DE RÉPONSE STANDARDISÉS
// ============================================================================

/**
 * Réponse de succès standard
 */
function successResponse($data = null, $message = 'Opération réussie', $code = 200) {
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
    http_response_code(422);
    return json_encode([
        'success' => false,
        'error' => 'Données invalides',
        'validation_errors' => $errors,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Réponse de ressource non trouvée
 */
function notFoundResponse($resource = 'Ressource') {
    http_response_code(404);
    return json_encode([
        'success' => false,
        'error' => $resource . ' non trouvé(e)',
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Réponse d'autorisation échouée
 */
function unauthorizedResponse($message = 'Accès non autorisé') {
    http_response_code(401);
    return json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Réponse de serveur interne
 */
function serverErrorResponse($message = 'Erreur interne du serveur', $details = null) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ];
    
    if ($details !== null && $_ENV['APP_ENV'] === 'development') {
        $response['debug'] = $details;
    }
    
    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

/**
 * Validation des données d'entrée
 */
function validateRequired($data, $requiredFields) {
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = "Le champ '$field' est requis";
        }
    }
    
    return $errors;
}

/**
 * Validation d'ID numérique
 */
function validateId($id) {
    if (!is_numeric($id) || intval($id) <= 0) {
        return "ID invalide";
    }
    return null;
}

/**
 * Sanitisation basique des données
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Pagination standardisée
 */
function paginateResponse($items, $page = 1, $limit = 20, $total = null) {
    $total = $total ?? count($items);
    $totalPages = ceil($total / $limit);
    
    return [
        'items' => $items,
        'pagination' => [
            'current_page' => intval($page),
            'per_page' => intval($limit),
            'total_items' => intval($total),
            'total_pages' => intval($totalPages),
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ];
}

// ============================================================================
// FONCTIONS SPÉCIFIQUES AUX ENTITÉS
// ============================================================================

/**
 * Format standard pour les listes
 */
function formatListResponse($items, $message = 'Liste récupérée avec succès') {
    return successResponse($items, $message);
}

/**
 * Format standard pour un élément unique
 */
function formatItemResponse($item, $message = 'Élément récupéré avec succès') {
    return successResponse($item, $message);
}

/**
 * Format standard pour les opérations CRUD
 */
function formatCreateResponse($id, $message = 'Élément créé avec succès') {
    return successResponse(['id' => $id], $message, 201);
}

function formatUpdateResponse($message = 'Élément mis à jour avec succès') {
    return successResponse(null, $message);
}

function formatDeleteResponse($message = 'Élément supprimé avec succès') {
    return successResponse(null, $message);
}

// ============================================================================
// GESTION DES ERREURS AVANCÉE
// ============================================================================

/**
 * Gestionnaire d'erreurs PDO
 */
function handleDatabaseError($e, $operation = 'opération') {
    error_log("Database Error in $operation: " . $e->getMessage());
    
    // En développement, on peut renvoyer plus de détails
    if ($_ENV['APP_ENV'] ?? 'production' === 'development') {
        return serverErrorResponse("Erreur de base de données", $e->getMessage());
    }
    
    return serverErrorResponse("Erreur de base de données lors de $operation");
}

/**
 * Gestionnaire d'erreurs de validation
 */
function handleValidationErrors($errors) {
    return validationErrorResponse($errors);
}

/**
 * Validation des paramètres de requête
 */
function validateQueryParams($allowedParams, $actualParams) {
    $invalidParams = array_diff(array_keys($actualParams), $allowedParams);
    
    if (!empty($invalidParams)) {
        return "Paramètres non autorisés: " . implode(', ', $invalidParams);
    }
    
    return null;
}

// ============================================================================
// FONCTIONS DE CONVENIENCE
// ============================================================================

/**
 * Envoie une réponse et termine l'exécution
 */
function sendResponse($json) {
    echo $json;
    exit;
}

/**
 * Envoie une réponse de succès
 */
function sendSuccess($data = null, $message = 'Opération réussie') {
    sendResponse(successResponse($data, $message));
}

/**
 * Envoie une réponse d'erreur
 */
function sendError($message = 'Une erreur est survenue', $code = 400) {
    sendResponse(errorResponse($message, $code));
}

// ============================================================================
// MIDDLEWARE SIMPLE
// ============================================================================

/**
 * Vérifie que la requête est en JSON
 */
function requireJsonRequest() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_contains($contentType, 'application/json')) {
        sendError('Content-Type doit être application/json', 415);
    }
}

/**
 * Extrait et valide les données JSON du body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('JSON invalide', 400);
    }
    
    return $data ?: [];
}
?>
