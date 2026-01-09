  <!-- Header Section -->
  <section class="page-header">
    <div class="flex justify-between items-center">
      <div>
        <h2><i class="bi bi-grid-1x2-fill"></i> Performance du Parking</h2>
        <p>Aperçu en temps réel de votre exploitation</p>
      </div>
      <div class="text-right">
        <span>Dernière mise à jour</span>
        <p id="lastUpdateTime"><?php echo date('d/m/Y H:i'); ?></p>
      </div>
    </div>
    
    <!-- Date Range Selector -->
    <div class="date-selector">
      <label for="dateRangeSelector">
        <i class="bi bi-calendar-range"></i>Période d'analyse :
      </label>
      <select id="dateRangeSelector">
        <option value="7">7 derniers jours</option>
        <option value="14">14 derniers jours</option>
        <option value="30">30 derniers jours</option>
        <option value="60">60 derniers jours</option>
        <option value="90">90 derniers jours</option>
        <option value="180">6 derniers mois</option>
        <option value="365">12 derniers mois</option>
      </select>
    </div>
  </section>

  <!-- Finance Section -->
  <section class="finance-section">
    <div class="section-header flex justify-between items-center">
      <div>
        <h3><i class="bi bi-cash-stack"></i> Bilan Financier</h3>
        <p>Aperçu des revenus et créances</p>
      </div>
      <button class="btn btn-ghost" onclick="refreshFinance()">
        <i class="bi bi-arrow-clockwise"></i> Actualiser
      </button>
    </div>
    
    <!-- Financial Cards -->
    <div class="finance-cards" aria-label="Indicateurs financiers">
      <div class="stat-card stat-card-primary">
        <div class="stat-icon">
          <i class="bi bi-calendar-day"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Revenus du Jour</p>
          <h4 id="financeRevenueToday">--</h4>
          <p class="stat-desc">Total entrées aujourd'hui</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon stat-icon-indigo">
          <i class="bi bi-credit-card-2-front"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Revenus Abonnements</p>
          <h4 id="financeSubscriptions">--</h4>
          <p class="stat-desc">Mensualités actives</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon stat-icon-amber">
          <i class="bi bi-calendar-month"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Revenus du Mois</p>
          <h4 id="financeRevenueMonth">--</h4>
          <p class="stat-desc"><?php echo date('F Y'); ?></p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon stat-icon-rose">
          <i class="bi bi-exclamation-circle"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Créances en Attente</p>
          <h4 id="financePending">--</h4>
          <p class="stat-desc">Montants non soldés</p>
        </div>
      </div>
    </div>
    
    <!-- Financial Tables -->
    <div class="finance-tables">
      <!-- Monthly Summary -->
      <div class="card">
        <div class="card-header">
          <h5><i class="bi bi-graph-up"></i> Récapitulatif Mensuel</h5>
        </div>
        <div class="table-responsive">
          <table class="table finance-table">
            <thead>
              <tr>
                <th>Mois</th>
                <th>Entrées</th>
                <th>Abonnements</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody id="monthlySummaryBody">
              <tr>
                <td colspan="4" class="text-center">
                  <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                  <span>Chargement...</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Top Clients -->
      <div class="card">
        <div class="card-header">
          <h5><i class="bi bi-trophy"></i> Top Clients</h5>
        </div>
        <ul id="topClientsList" class="top-clients-list">
          <li class="text-center text-muted">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span>Chargement...</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Main Stats Section -->
  <section class="stats-section" aria-label="Statistiques principales">
    <div class="stat-card">
      <div class="stat-icon stat-icon-blue">
        <i class="bi bi-people"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">Portefeuille Clients</p>
        <h3 id="statClients">--</h3>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon stat-icon-emerald">
        <i class="bi bi-truck"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">Parc Véhicules</p>
        <h3 id="statVehicles">--</h3>
      </div>
    </div>

    <div class="stat-card stat-card-primary">
      <div class="stat-icon">
        <i class="bi bi-arrow-down-left-circle"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">Entrées du Jour</p>
        <h3 id="statEntries">--</h3>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon stat-icon-purple">
        <i class="bi bi-calendar-check"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">Abonnements Actifs</p>
        <h3 id="statAbonnements">--</h3>
      </div>
    </div>
  </section>

  <!-- Cash Flow Section -->
  <section class="card">
    <div class="card-header flex justify-between items-center">
      <div>
        <h5>Flux de Trésorerie Récents</h5>
        <p>Dernières transactions enregistrées</p>
      </div>
      <a href="?page=daily" class="btn btn-primary">
        <i class="bi bi-list-ul"></i>Journal Complet
      </a>
    </div>
    
    <div class="table-responsive">
      <table id="dailyTable" class="table">
        <thead>
          <tr>
            <th>Heure</th>
            <th>Client & Véhicule</th>
            <th>Immatriculation</th>
            <th>Catégorie</th>
            <th>Finances</th>
            <th>Entrée</th>
            <th>Sortie</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="7" class="text-center">
              <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
              <span>Synchronisation des données directes...</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>


<link rel="stylesheet" href="assets/css/dashboard.css">

