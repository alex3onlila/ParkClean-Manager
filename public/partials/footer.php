<footer class="footer compact" role="contentinfo">
  <div class="container footer-grid">

    <!-- Brand -->
    <div class="footer-section" aria-label="Présentation">
      <h4>ParkClean</h4>
      <p class="muted small">
        Solution locale pour la gestion des clients, véhicules, entrées et abonnements.
      </p>
    </div>

    <!-- Navigation -->
    <div class="footer-section" aria-label="Navigation">
      <h4>Navigation</h4>
      <ul class="footer-links">
        <li><a href="?page=dashboard" class="<?= $page==='dashboard' ? 'active' : '' ?>">Accueil</a></li>
        <li><a href="?page=clients" class="<?= $page==='clients' ? 'active' : '' ?>">Clients</a></li>
        <li><a href="?page=vehicles" class="<?= $page==='vehicles' ? 'active' : '' ?>">Véhicules</a></li>
      </ul>
    </div>

    <!-- Support -->
    <div class="footer-section" aria-label="Support">
      <h4>Support</h4>
      <ul class="footer-links">
        <li><a href="?page=about" class="<?= $page==='about' ? 'active' : '' ?>">À propos</a></li>
        <li><a href="mailto:hello@local">hello@local</a></li>
      </ul>
    </div>

  </div>

  <!-- Bottom -->
  <div class="footer-bottom">
    © <?= date("Y") ?> ParkClean Manager — Tous droits réservés
  </div>
</footer>
