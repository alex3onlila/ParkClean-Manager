<link rel="stylesheet" href="assets/css/subscription.css">

<main class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-card-checklist text-primary me-2"></i> Gestion des Abonnements
            </h2>
            <p class="text-muted small mb-0">Suivi des forfaits, validité et paiements.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 shadow-sm fw-medium" onclick="openSubModal()">
            <i class="bi bi-plus-lg me-2"></i> Nouvel Abonnement
        </button>
    </div>
<br>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div class="icon-box bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted text-uppercase small fw-bold mb-0">Total Abonnés</h6>
                    <h3 class="fw-bold mb-0" id="statTotalSubs">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div class="icon-box bg-success-subtle text-success rounded-circle p-3">
                    <i class="bi bi-check-lg fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted text-uppercase small fw-bold mb-0">Actifs Aujourd'hui</h6>
                    <h3 class="fw-bold mb-0" id="statActiveSubs">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div class="icon-box bg-danger-subtle text-danger rounded-circle p-3">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted text-uppercase small fw-bold mb-0">Reste à Percevoir</h6>
                    <h3 class="fw-bold mb-0" id="statReste">0 F</h3>
                </div>
            </div>
        </div>
    </div>

    <section class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="subsTable">
                <thead class="bg-light">
                    <tr class="text-uppercase text-muted small fw-bold">
                        <th class="ps-4" style="width: 120px;">État</th>
                        <th style="width: 180px;">Période (Début - Fin)</th>
                        <th>Véhicule / Client</th>
                        <th style="width: 200px;">Finances</th>
                        <th class="text-end pe-4" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white" id="subsTableBody">
                    <tr><td colspan="5" class="text-center py-4 text-muted">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal fade" id="subModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form id="subForm" class="modal-content border-0 shadow-lg" novalidate>
                <div class="modal-header border-bottom-0 pb-0 bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">Fiche Abonnement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="subId">

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-uppercase text-primary small fw-bold border-bottom pb-2">1. Sélection du véhicule</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Véhicule</label>
                            <select name="vehicle_id" id="vehicleSelect" class="form-select shadow-sm" required onchange="calculateDatesFromPlan()">
                                <option value="">Choisir un véhicule...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Forfait de référence</label>
                            <select id="planSelect" class="form-select shadow-sm" onchange="calculateDatesFromPlan()">
                                <option value="custom">Personnalisé (Saisie libre)</option>
                                <option value="hebdo">Hebdomadaire (7 jours)</option>
                                <option value="mensuel" selected>Mensuel (30 jours)</option>
                                <option value="trimestriel">Trimestriel (90 jours)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 bg-light p-3 rounded border">
                        <div class="col-12">
                            <h6 class="text-uppercase text-success small fw-bold mb-3">
                                <i class="bi bi-calendar-range"></i> Période & Tarification
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date de Début</label>
                            <input type="date" name="date_debut" id="dateDebut" class="form-control fw-bold" required onchange="calculateDatesFromPlan()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date de Fin</label>
                            <input type="date" name="date_fin" id="dateFin" class="form-control fw-bold text-primary" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Prix Lavage (Base)</label>
                            <input type="text" id="basePrice" class="form-control-plaintext form-control-sm fw-bold text-muted" readonly value="0 F">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Total à Payer (F)</label>
                            <input type="number" name="montant_total" id="montantTotal" class="form-control fw-bold border-primary" oninput="updateRemaining()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-danger">Reste (Dette)</label>
                            <input type="text" id="montantResteDisplay" class="form-control-plaintext fw-bold text-danger" readonly value="0 F">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Montant Versé (Reçu)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-cash"></i></span>
                                <input type="number" name="montant_recu" id="montantRecu" class="form-control fw-bold" placeholder="0" oninput="updateRemaining()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Note / Observation</label>
                            <textarea name="obs" id="subObs" class="form-control" rows="1"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-ghost px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-5 shadow fw-bold">Valider l'Abonnement</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="assets/js/subscription.js"></script>