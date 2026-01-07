<?php
/**
 * ParkClean Manager - Base Controller
 * Classe mère pour tous les contrôleurs API
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Core;

use PDOException;

/**
 * Classe abstraite de base pour les contrôleurs API
 */
abstract class BaseController
{
    protected array $request = [];
    protected array $headers = [];
    protected int $statusCode = 200;
    protected bool $debugMode = false;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->headers = $this->getHeaders();
        $this->request = $this->getInput();
        $this->debugMode = $this->isDebugMode();
    }

    /**
     * Méthode principale à implémenter dans les contrôleurs enfants
     */
    abstract public function handle(): void;

    /**
     * Récupère les headers de la requête
     */
    protected function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    /**
     * Récupère les données d'entrée (JSON ou Form)
     */
    protected function getInput(): array
    {
        // Essayer de récupérer le JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // Sinon, récupérer les données POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $_POST;
        }

        return [];
    }

    /**
     * Vérifie si le mode debug est activé
     */
    protected function isDebugMode(): bool
    {
        return isset($_GET['debug']) || getenv('APP_DEBUG') === 'true';
    }

    /**
     * Récupère une valeur de la requête avec valeur par défaut
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Récupère une valeur requise de la requête
     */
    protected function inputRequired(string $key): mixed
    {
        if (!isset($this->request[$key])) {
            $this->respondValidationError("Le champ '{$key}' est requis");
        }
        return $this->request[$key];
    }

    /**
     * Vérifie que tous les champs requis sont présents
     */
    protected function validateRequired(array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($this->request[$field]) || $this->request[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->respondValidationError("Champs requis manquants: " . implode(', ', $missing));
        }
    }

    /**
     * Envoie une réponse JSON avec en-têtes de sécurité
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        $this->statusCode = $statusCode;
        
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            
            if ($statusCode >= 400) {
                header('Cache-Control: no-store, no-cache, must-revalidate');
            }
        }

        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Répond avec un succès
     */
    protected function respondSuccess(mixed $data = null, string $message = 'Opération réussie', int $code = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];

        $this->json($response, $code);
    }

    /**
     * Répond avec une erreur
     */
    protected function respondError(string $message, int $code = 400, ?array $details = null): void
    {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ];

        if ($details !== null && $this->debugMode) {
            $response['details'] = $details;
        }

        $this->json($response, $code);
    }

    /**
     * Répond avec une erreur de validation
     */
    protected function respondValidationError(string $message, array $errors = []): void
    {
        $response = [
            'success' => false,
            'error' => $message,
            'validation_errors' => $errors,
            'timestamp' => date('c')
        ];

        $this->json($response, 422);
    }

    /**
     * Répond avec une ressource non trouvée
     */
    protected function respondNotFound(string $resource = 'Ressource'): void
    {
        $this->respondError("{$resource} non trouvé(e)", 404);
    }

    /**
     * Répond avec une erreur de serveur
     */
    protected function respondServerError(string $message, ?\Throwable $e = null): void
    {
        $details = null;
        if ($this->debugMode && $e !== null) {
            $details = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        $this->respondError($message, 500, $details);
    }

    /**
     * Récupère la méthode HTTP
     */
    protected function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Vérifie si la méthode est GET
     */
    protected function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Vérifie si la méthode est POST
     */
    protected function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Vérifie si la méthode est PUT/PATCH
     */
    protected function isPut(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }

    /**
     * Vérifie si la méthode est DELETE
     */
    protected function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * Vérifie l'authentification (à surcharger)
     */
    protected function authenticate(): ?array
    {
        // Par défaut, pas d'authentification requise
        return null;
    }

    /**
     * Exécute le contrôleur
     */
    public function run(): void
    {
        try {
            // Authentification si nécessaire
            $user = $this->authenticate();
            if ($user !== null && is_array($user)) {
                $this->request['_user'] = $user;
            }

            // Exécuter la méthode correspondante
            $this->handle();
        } catch (ValidationException $e) {
            $this->respondValidationError($e->getMessage(), $e->getErrors());
        } catch (NotFoundException $e) {
            $this->respondNotFound($e->getMessage());
        } catch (PDOException $e) {
            $this->respondServerError('Erreur de base de données', $e);
            Logger::error('Database Error', [
                'message' => $e->getMessage(),
                'sql' => $e->getTraceAsString()
            ]);
        } catch (\Throwable $e) {
            $this->respondServerError('Une erreur est survenue', $e);
            Logger::error('Controller Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}

/**
 * Exception personnalisée pour les erreurs de validation
 */
class ValidationException extends \Exception
{
    private array $errors = [];

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Exception personnalisée pour les ressources non trouvées
 */
class NotFoundException extends \Exception {}

