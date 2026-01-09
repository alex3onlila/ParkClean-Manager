<header class="header" role="banner">
  <div class="container header-inner">

    <!-- LEFT -->
    <div class="header-left">
      <a href="?page=dashboard" class="logo-box logo-brand" aria-label="ParkClean home">
        <img src="assets/images/logo.png" alt="ParkClean logo" class="logo-icon">
        <span class="logo-text">ParkClean</span>
      </a>
    </div>

    <!-- NAV -->
    <nav class="nav" id="navMenu" role="navigation" aria-label="Main navigation">
      <a href="?page=dashboard" class="<?= $page==='dashboard' ? 'active' : '' ?>">Accueil</a>
      <a href="?page=clients" class="<?= $page==='clients' ? 'active' : '' ?>">Clients</a>
      <a href="?page=vehicles" class="<?= $page==='vehicles' ? 'active' : '' ?>">Véhicules</a>
      <a href="?page=daily" class="<?= $page==='daily' ? 'active' : '' ?>">Journalier</a>
      <a href="?page=subscription" class="<?= $page==='subscription' ? 'active' : '' ?>">Abonnements</a>
      <a href="?page=about" class="<?= $page==='about' ? 'active' : '' ?>">À propos</a>
    </nav>

    <!-- ACTIONS -->
    <div class="header-actions">
      <button id="btnSearch" class="btn-icon" aria-label="Rechercher"><i class="bi bi-search"></i></button>
      <button id="themeToggle" class="btn-icon" aria-label="Changer le thème" title="Changer le thème"><i class="bi bi-moon-stars"></i></button>

      <!-- USER -->
      <div class="user-wrap">
        <button id="userMenu" class="avatar" aria-haspopup="true" aria-expanded="false" aria-controls="accountMenu" aria-label="Ouvrir le menu du compte">
          <i class="bi bi-person-circle" aria-hidden="true"></i>
        </button>

        <div id="accountMenu" class="account-menu" role="menu" aria-hidden="true" aria-labelledby="userMenu">
          <div class="account-profile">
            <div id="userAvatar" class="avatar">
              <?php 
                // Afficher les initiales de l'utilisateur
                $userName = $_SESSION['user']['name'] ?? 'U';
                $userInitial = strtoupper(substr($userName, 0, 2));
                echo htmlspecialchars($userInitial);
              ?>
            </div>
            <div>
              <strong id="userNameDisplay">
                <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Utilisateur') ?>
              </strong><br>
              <span id="userEmailDisplay" class="small text-muted">
                <?= htmlspecialchars($_SESSION['user']['email'] ?? 'email@parkclean.com') ?>
              </span>
            </div>
          </div>
          <div class="account-actions">
            <a role="menuitem" href="?page=profile">Profil</a>
            <button id="btnLogout" role="menuitem">Déconnexion</button>
          </div>
        </div>
      </div>

      <!-- Script pour charger les données utilisateur via API si session incomplète -->
      <script>
      (function() {
        const userNameDisplay = document.getElementById('userNameDisplay');
        const userEmailDisplay = document.getElementById('userEmailDisplay');
        const userAvatar = document.getElementById('userAvatar');

        // Si les données sont déjà complètes, ne rien faire
        const currentName = userNameDisplay?.textContent?.trim();
        if (currentName && currentName !== 'Utilisateur') return;

        // Sinon, charger via API
        fetch('/api/users/list.php')
          .then(res => res.json())
          .then(data => {
            if (data && data.length > 0) {
              const user = data[0];
              if (userNameDisplay) userNameDisplay.textContent = user.name || 'Utilisateur';
              if (userEmailDisplay) userEmailDisplay.textContent = user.email || 'email@parkclean.com';
              if (userAvatar) userAvatar.textContent = (user.name || 'U').substring(0, 2).toUpperCase();
            }
          })
          .catch(err => console.log('Impossible de charger les données utilisateur'));
      })();
      </script>

      <!-- HAMBURGER -->
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

  </div>

  <div class="nav-overlay" id="navOverlay"></div>
</header>
