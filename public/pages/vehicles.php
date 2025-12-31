<?php
declare(strict_types=1);

$dbFile = __DIR__ . '/../../database/parkclean.db'; 

try {
    $conn = new PDO("sqlite:" . $dbFile);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur connexion SQLite: " . $e->getMessage());
}

// Charger clients
$clients = $conn->query("SELECT id, prenom, nom FROM clients ORDER BY nom ASC")->fetchAll();

// Charger types
$types = $conn->query("SELECT id, type FROM vehicle_types ORDER BY type ASC")->fetchAll();
?>


<div class="container-fluid p-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold mb-0 text-primary">
        <i class="bi bi-car-front-fill me-2"></i>Gestion des Véhicules
      </h2>
      <p class="text-muted small">Ajoutez des véhicules et gérez leurs types.</p>
    </div>
    <button class="btn btn-primary shadow-sm px-4" onclick="openVehicleModal()">
      <i class="bi bi-plus-lg me-1"></i> Nouveau Véhicule
    </button>
<button class="btn btn-outline-success shadow-sm px-4"
        data-bs-toggle="modal" data-bs-target="#typeModal">
  <i class="bi bi-plus-lg me-1"></i> Nouveau Type
</button>
  </div>
<br>
  <!-- Recherche / tri -->
  <section class="mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input type="search" id="vehicleSearch" class="form-control bg-light border-start-0 ps-0"
                     placeholder="Rechercher par marque, plaque ou propriétaire...">
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <div class="d-inline-flex align-items-center gap-2">
              <label for="sortVehicles" class="text-muted small mb-0 me-2">Trier par :</label>
              <select id="sortVehicles" class="form-select form-select-sm w-auto">
                <option value="recent">Plus récents</option>
                <option value="marque">Marque A→Z</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

   <!-- Table -->
  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table id="vehiclesTable" class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th>Marque / Modèle</th>
            <th>Plaque</th>
            <th>Propriétaire</th>
            <th>Type / Catégorie</th>
            <th>Image</th>
            <th class="text-end" >Actions</th>
          </tr>
        </thead>
        <tbody id="vehiclesTableBody"></tbody>
      </table >
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="vehicleForm" class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title fw-bold" id="vehicleModalLabel">
          <i class="bi bi-pencil-square me-2" id="id"></i>Détails du Véhicule
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" name="id">

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-bold text-uppercase text-muted">Propriétaire</label>
          <select name="client_id" id="clientSelect" class="form-select" required>
            <option value="">Sélectionner un client</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold text-uppercase text-muted">Marque / Modèle</label>
            <input type="text" name="marque" class="form-control" placeholder="ex: Toyota Rav4" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-uppercase text-muted">Immatriculation</label>
            <input type="text" name="immatriculation" class="form-control font-monospace" placeholder="ABC-123-DE" required>
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold text-uppercase text-muted">Type de véhicule</label>
            <select name="type_id" id="typeSelect" class="form-select" required>
              <option value="">Type de véhicule</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>">
                  <?= htmlspecialchars($t['type']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
  <label class="form-label small fw-bold text-uppercase text-muted">Photo du véhicule (Optionnel)</label>
  <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
  <div id="imagePreview" class="mt-2"></div>
</div>

        </div>
      </div>
      <div class="modal-footer border-0 bg-light p-3">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary px-4 fw-bold">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Assets -->
<link rel="stylesheet" href="assets/css/vehicles.css">
<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Bootstrap JS (nécessaire pour Modal et Toast) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal pour les types de véhicules -->
<div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="typeForm" class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title fw-bold" id="typeModalLabel">
          <i class="bi bi-plus-lg me-2"></i>Nouveau Type de Véhicule
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-bold text-uppercase text-muted">Nom du type</label>
            <input type="text" name="type" class="form-control" placeholder="ex: Utilitaire, Berline, SUV..." required>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light p-3">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-success px-4 fw-bold">Ajouter</button>
      </div>
    </form>
  </div>
</div>

<!-- Main Script -->
<script src="assets/js/vehicles.js" defer></script>
