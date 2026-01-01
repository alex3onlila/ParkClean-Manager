<div class="dashboard-container p-4 lg:p-8 bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">

  <section class="page-header mb-8 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-black text-gray-800 flex items-center gap-3">
          <i class="bi bi-grid-1x2-fill text-blue-600"></i> Performance du Parking
        </h2>
        <p class="text-gray-500 mt-1">Aperçu en temps réel de votre exploitation.</p>
      </div>
      <div class="text-right hidden md:block">
        <span class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Mise à jour</span>
        <p class="font-mono text-blue-600"><?php echo date('d/m/Y H:i'); ?></p>
      </div>
    </div>
  </section>

  <section class="row g-4 mb-8" aria-label="Statistiques principales">
    <div class="col-md-3">
      <div class="card border-0 rounded-3xl bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all p-4 border-l-8 border-blue-500">
        <div class="flex justify-between items-center mb-2">
            <p class="text-gray-500 font-bold small uppercase">Portefeuille Clients</p>
            <i class="bi bi-people text-blue-500 fs-4"></i>
        </div>
        <h3 id="statClients" class="text-3xl font-black text-gray-800 mb-0">--</h3>
      </div>
    </div>
    
    <div class="col-md-3">
      <div class="card border-0 rounded-3xl bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all p-4 border-l-8 border-emerald-500">
        <div class="flex justify-between items-center mb-2">
            <p class="text-gray-500 font-bold small uppercase">Parc Véhicules</p>
            <i class="bi bi-truck text-emerald-500 fs-4"></i>
        </div>
        <h3 id="statVehicles" class="text-3xl font-black text-gray-800 mb-0">--</h3>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-xl hover:shadow-2xl transition-all p-4">
        <div class="flex justify-between items-center mb-2">
            <p class="text-blue-100 font-bold small uppercase">Entrées du Jour</p>
            <i class="bi bi-arrow-down-left-circle fs-4"></i>
        </div>
        <h3 id="statEntries" class="text-3xl font-black mb-0">--</h3>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 rounded-3xl bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all p-4 border-l-8 border-purple-500">
        <div class="flex justify-between items-center mb-2">
            <p class="text-gray-500 font-bold small uppercase">Abonnements Actifs</p>
            <i class="bi bi-calendar-check text-purple-500 fs-4"></i>
        </div>
        <h3 id="statAbonnements" class="text-3xl font-black text-gray-800 mb-0">--</h3>
      </div>
    </div>
  </section>

  <section class="card border-0 rounded-3xl shadow-2xl bg-white/80 backdrop-blur-xl overflow-hidden">
    <div class="card-header bg-transparent py-4 px-6 border-0 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0 font-black text-gray-800">Flux de Trésorerie Récents</h5>
        <p class="text-xs text-gray-400 mb-0 uppercase tracking-tighter">Dernières transactions enregistrées</p>
      </div>
      <a href="?page=daily" class="btn btn-primary rounded-pill px-4 font-bold shadow-lg shadow-blue-200">
        <i class="bi bi-list-ul me-2"></i>Journal Complet
      </a>
    </div>
    
    <div class="table-responsive px-4 pb-4">
  <table id="dailyTable" class="table table-hover align-middle mb-0">
    <thead>
      <tr class="text-uppercase small fw-bold text-muted border-bottom">
        <th class="py-3">Heure</th>
        <th class="py-3">Client & Véhicule</th>
        <th class="py-3">Immatriculation</th>
        <th class="py-3">Catégorie</th>
        <th class="py-3">Finances</th>
        <th class="py-3">Entrée</th>
        <th class="py-3">Sortie</th>
      </tr>
    </thead>
    <tbody class="border-top-0">
      <tr>
        <td colspan="7" class="text-center py-5">
          <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
          <span class="text-muted font-medium">Synchronisation des données directes...</span>
        </td>
      </tr>
    </tbody>
  </table>
</div>
  </section>

</div>

<link rel="stylesheet" href="assets/css/dashboard.css">