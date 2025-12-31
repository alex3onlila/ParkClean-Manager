<?php
session_start();

/* ================= CONFIG ================= */
define('APP_NAME', 'ParkClean Manager');

// Pages autorisées
$allowedPages = [
    'dashboard','clients','vehicles','daily',
    'subscription','about','login','profile','settings'
];

/* ================= ROUTING ================= */
$page = preg_replace('/[^a-z0-9_]/i', '', $_GET['page'] ?? 'dashboard');
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Authentification
$isAuthenticated = !empty($_SESSION['user']);

// Redirections login
if ($page === 'login' && $isAuthenticated) {
    header('Location: ?page=dashboard');
    exit;
}
if (!$isAuthenticated && $page !== 'login') {
    $page = 'login';
}

// Fichier page
$pageFile = __DIR__ . "/pages/{$page}.php";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/images/logo.png">

  <!-- Fonts -->
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <!-- Favicon --> <link rel="icon" type="image/png" href="assets/images/logo.png"> 
   <link rel="apple-touch-icon" href="assets/images/logo.png"> 
   <link rel="shortcut icon" href="assets/images/logo.png"> 
   <link rel="stylesheet" href="assets/css/header.css"> 
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body class="<?= $isAuthenticated ? 'app-auth' : 'app-guest' ?>">

<!-- ================= LOADER ================= -->
<div id="appLoader" class="app-loader" aria-hidden="false">
  <div class="loader-content">
    <img src="assets/images/logo.png" class="loader-logo" alt="<?= htmlspecialchars(APP_NAME) ?>">
    <span class="loader-text"><?= htmlspecialchars(APP_NAME) ?></span>
  </div>
</div>

<!-- ================= HEADER ================= -->
<?php if ($isAuthenticated): ?>
  <?php include __DIR__ . '/partials/header.php'; ?>
<?php endif; ?>

<!-- ================= MAIN ================= -->
<main id="app" data-page="<?= htmlspecialchars($page) ?>">
  <?php
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        echo "<section class='page-error'><h2>Page introuvable</h2></section>";
    }
  ?>
</main>

<!-- ================= FOOTER ================= -->
<?php if ($isAuthenticated): ?>
  <?php include __DIR__ . '/partials/footer.php'; ?>
<?php endif; ?>

<!-- ================= JS ================= -->
<!-- JS commun -->
<script src="assets/js/common.js"></script>

<?php
$pageJs = "assets/js/{$page}.js";
if (file_exists(__DIR__ . "/$pageJs")) {
    echo "<script src='$pageJs'></script>";
}
?>


</body>
</html>
