<div class="dashboard-container p-4">

  <!-- HEADER -->
  <section class="page-header mb-4">
    <h2 class="text-primary fw-bold">
      <i class="bi bi-speedometer2"></i> Tableau de bord
    </h2>
  </section>

  <!-- STATS -->
  <section class="row g-4 mb-5" aria-label="Statistiques principales">
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-primary text-white p-3">
        <p class="small mb-1">Total Clients</p>
        <h3 id="statClients" class="fw-bold mb-0">--</h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-success text-white p-3">
        <p class="small mb-1">Véhicules Actifs</p>
        <h3 id="statVehicles" class="fw-bold mb-0">--</h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-warning text-dark p-3">
        <p class="small mb-1">Entrées du Jour</p>
        <h3 id="statEntries" class="fw-bold mb-0">--</h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-info text-white p-3">
        <p class="small mb-1">Abonnements</p>
        <h3 id="statAbonnements" class="fw-bold mb-0">--</h3>
      </div>
    </div>
  </section>

  <!-- DAILY MOVEMENTS -->
  <section class="card border-0 shadow-sm" aria-label="Derniers mouvements">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold">Derniers Mouvements</h5>
      <a href="?page=daily" class="btn btn-sm btn-outline-primary">Voir journal</a>
    </div>
    <div class="table-responsive">
      <table id="dailyTable" class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th>Plaque</th>
            <th>Marque</th>
            <th>Montant</th>
            <th>Date & Heure</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4" class="text-center text-muted">Chargement des mouvements...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

</div>
