<link rel="stylesheet" href="assets/css/daily.css">

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-journal-text text-primary"></i> Journal du Jour</h2>
            <p class="text-muted small mb-0" id="activePeriodDisplay">Chargement de la date...</p>
        </div>
        <button class="btn btn-primary shadow-sm px-4 py-2 fw-bold" onclick="openEntryModal()">
            <i class="bi bi-plus-lg me-2"></i> Nouvelle Entrée
        </button>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4 bg-light">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase" style="font-size: 0.7rem;">Rechercher par Plaque</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchPlate" class="form-control border-start-0" placeholder="Ex: ABC-123..." onkeyup="filterLocal()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase" style="font-size: 0.7rem;">Filtrer par Date</label>
                <input type="date" id="searchDate" class="form-control" onchange="loadEntriesByDate()">
            </div>
            <div class="col-md-5 text-end">
                <button class="btn btn-outline-dark me-2" onclick="printDailyReport()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <button class="btn btn-success" onclick="loadEntries()">
                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center border-start border-success border-4">
                <span class="text-muted small fw-bold">RECETTE (REÇU)</span>
                <h3 id="statTotalRecu" class="text-success fw-bold mb-0">0 F</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center border-start border-danger border-4">
                <span class="text-muted small fw-bold">RESTE À PERCEVOIR</span>
                <h3 id="statTotalReste" class="text-danger fw-bold mb-0">0 F</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center border-start border-dark border-4">
                <span class="text-muted small fw-bold">VÉHICULES</span>
                <h3 id="statCount" class="text-dark fw-bold mb-0">0</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="entriesTable">
                <thead class="bg-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4">Heure</th>
                        <th>Client & Véhicule</th>
                        <th>Immatriculation</th>
                        <th class="col-categorie">Catégorie</th>
                        <th>Finances</th>
                        <th class="text-center">Entrée</th>
                        <th class="text-center">Sortie</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="entriesTableBody">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="entryForm" class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle text-white">Fiche d'Opération</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="entryId">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Véhicule (Immatriculation)</label>
                        <select name="vehicle_id" id="vehicleSelect" class="form-select shadow-sm" required onchange="onVehicleChange()">
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date & Heure d'entrée</label>
                        <input type="datetime-local" name="date_enregistrement" id="entryDate" class="form-control shadow-sm" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Prix Lavage (Fixe)</label>
                        <input type="number" id="montant_total_display" class="form-control fw-bold" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Montant Versé</label>
                        <input type="number" name="montant_recu" id="montant_recu" class="form-control border-primary fw-bold shadow-sm" oninput="calculateRemaining()" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reste à payer</label>
                        <input type="number" id="montant_restant_display" class="form-control fw-bold text-danger" readonly>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="status-container d-flex justify-content-around">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="est_entree" id="est_entree" value="1" checked>
                                <label class="form-check-label fw-bold text-success">VÉHICULE ENTRÉ</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="est_sorti" id="est_sorti" value="1">
                                <label class="form-check-label fw-bold text-secondary">VÉHICULE SORTI</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observations / État du véhicule</label>
                        <textarea name="obs" id="entryObs" class="form-control shadow-sm" rows="2" placeholder="Ex: Rayure porte gauche, clés laissées..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-ghost-daily px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary px-5 shadow">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/daily.js"></script>