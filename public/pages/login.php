<?php
declare(strict_types=1);

// ----------------------
// Production-safe bootstrap
// ----------------------
session_start();

// Ne jamais afficher d'erreurs en production
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Log des erreurs (adapter le chemin hors webroot si possible)
$logPath = __DIR__ . '/../../logs/php_errors.log';
@ini_set('log_errors', '1');
@ini_set('error_log', $logPath);

// Convertir warnings/notices en exceptions pour mieux contrôler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Gestionnaire d'exceptions global : log et message générique
set_exception_handler(function($ex) {
    error_log(sprintf("Uncaught exception: %s in %s on line %d\n%s", $ex->getMessage(), $ex->getFile(), $ex->getLine(), $ex->getTraceAsString()));
    http_response_code(500);
    echo "Une erreur est survenue. Veuillez réessayer plus tard.";
    exit;
});

// ----------------------
// Authentification
// ----------------------
$error = '';
$user = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération et normalisation des entrées
    $rawUser = (string)($_POST['username'] ?? '');
    $user = trim($rawUser);
    $pass = (string)($_POST['password'] ?? '');
    $ok = false;

    // Détecter si l'entrée ressemble à un email (pour normaliser)
    $isEmail = filter_var($user, FILTER_VALIDATE_EMAIL) !== false;
    $userForQuery = $isEmail ? strtolower($user) : $user;

    // ======================
    // Tentative DB
    // ======================
    try {
        require __DIR__ . '/../../api/config/database.php';

        if (isset($conn)) {
            // Sélectionner les champs utiles ; si la colonne email n'existe pas, la requête échouera proprement et tombera dans le catch
            $stmt = $conn->prepare('SELECT id, username, email, password FROM users WHERE username = :u OR email = :e LIMIT 1');
            $stmt->execute([':u' => $userForQuery, ':e' => $userForQuery]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['password'])) {
                $stored = (string)$row['password'];

                // Si l'entrée était un email, et que la DB stocke les emails en lowercase, on a déjà normalisé.
                // Vérifier hash bcrypt
                if (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$2b$') === 0) {
                    $ok = password_verify($pass, $stored);
                } else {
                    // fallback legacy : comparaison sûre (timing-safe)
                    // stocker les mots de passe en clair est fortement déconseillé ; migrer vers password_hash ASAP
                    $ok = hash_equals($stored, $pass);
                }
            }
        }
    } catch (Throwable $e) {
        // erreur loggée par le handler global ; on continue vers le fallback config
        // Ne rien afficher à l'utilisateur
    }

    // ======================
    // Fallback config
    // ======================
    if (!$ok) {
        try {
            $cfg = require __DIR__ . '/../../api/config/auth.php';
            // support direct key by username/email
            if (isset($cfg['users'][$user])) {
                $stored = $cfg['users'][$user];
                if (is_string($stored) && strpos($stored, '$2y$') === 0) {
                    $ok = password_verify($pass, $stored);
                } else {
                    $ok = hash_equals((string)$stored, $pass);
                }
            } else {
                // si la config contient des entrées structurées, tenter de trouver par email
                foreach ($cfg['users'] as $k => $v) {
                    if (is_array($v) && isset($v['email']) && strcasecmp($v['email'], $user) === 0) {
                        $stored = $v['password'] ?? null;
                        if ($stored) {
                            $ok = (strpos($stored, '$2y$') === 0) ? password_verify($pass, $stored) : hash_equals((string)$stored, $pass);
                        }
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            // silence, erreur loggée
        }
    }

    // ======================
    // Résultat
    // ======================
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['username' => $user];
        header('Location: ?page=dashboard');
        exit;
    }

    // ❌ mot de passe incorrect → rester ici
    $error = 'Identifiants invalides';
}
?>

<section class="card" style="max-width:520px;margin:30px auto">
  <h1>Connexion</h1>

  <?php if ($error): ?>
    <div class="muted" style="color:#b91c1c;margin-bottom:10px">
      <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="on" novalidate>
    <label class="small muted">Nom d'utilisateur ou email</label>
    <input
      name="username"
      class="form-control"
      required
      autocomplete="username"
      value="<?= htmlspecialchars($user, ENT_QUOTES) ?>"
    >

    <br><br>

    <label class="small muted">Mot de passe</label>
    <div style="position:relative;display:flex;gap:8px;align-items:center">
      <input
        id="password"
        name="password"
        type="password"
        class="form-control"
        required
        autocomplete="current-password"
      >

      <button
        type="button"
        class="btn-icon"
        data-toggle="password"
        data-target="#password"
        aria-controls="password"
        aria-label="Afficher le mot de passe"
        title="Afficher/masquer"
      >
        <i class="bi bi-eye"></i>
      </button>
    </div>

    <div style="margin-top:8px">
      <a href="?page=forgot" class="small">Mot de passe oublié ?</a>
    </div>

    <div style="margin-top:12px">
      <button class="btn-primary" type="submit">Se connecter</button>
    </div>
  </form>
</section>

<link rel="stylesheet" href="assets/css/login.css">
<script src="assets/js/common.js" defer></script>
