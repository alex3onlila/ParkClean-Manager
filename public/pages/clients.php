<?php
// pages/clients.php
// Affiche la liste des clients en respectant l'ordre des champs de la table:
// id, nom, prenom, email, telephone, image, created_at
?>
<main class="container-fluid p-4" aria-labelledby="clientsTitle">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 id="clientsTitle" class="fw-bold text-dark mb-1">
        <i class="bi bi-people-fill text-primary me-2" aria-hidden="true"></i> Annuaire Clients
      </h2>
      <p class="text-muted small mb-0">Gérez vos clients, leurs coordonnées et leur historique.</p>
    </div>

    <div class="d-flex gap-2 align-items-center">
      <button id="btnExportClients" class="btn btn-outline-secondary btn-sm" title="Exporter">
        <i class="bi bi-download"></i>
      </button>
      <a href="#modalLabel" class="btn btn-primary px-4 py-2 shadow-sm fw-medium" aria-haspopup="dialog" data-bs-toggle="modal">
        <i class="bi bi-person-plus-fill me-2"></i> Nouveau Client
      </a>
    </div>
  </div>
<br>
  <section class="mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted" aria-hidden="true">
                <i class="bi bi-search"></i>
              </span>
              <input type="search" id="clientSearch" class="form-control bg-light border-start-0 ps-0"
                     placeholder="Rechercher par nom, prénom, email ou téléphone..." aria-label="Rechercher clients">
            </div>
          </div>

          <div class="col-md-6 text-md-end">
            <div class="d-inline-flex align-items-center gap-2">
              <label for="sortClients" class="text-muted small mb-0 me-2">Trier par :</label>
              <select id="sortClients" class="form-select form-select-sm w-auto">
                <option value="recent">Plus récents</option>
                <option value="name">Nom A→Z</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm overflow-hidden" aria-live="polite">
    <div class="table-responsive">
      <table id="clientsTable" class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr class="text-uppercase text-muted small fw-bold">
            <th scope="col" class="ps-4" style="width: 80px;">ID</th>
            <th scope="col">Nom</th>
            <th scope="col">Prénom</th>
            <th scope="col">Email</th>
            <th scope="col">Téléphone</th>
            <th scope="col">Image</th>
            <th scope="col">Créé le</th>
            <th scope="col" class="text-end pe-4">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white">
          <tr><td colspan="8" class="text-center text-muted py-4">Chargement des clients…</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Toast accessible -->
<div id="clientsToast" class="pc-toast" role="status" aria-live="polite" aria-atomic="true" hidden></div>

<!-- Modal : Client (ordre des champs respecté) -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form id="clientForm" class="modal-content border-0 shadow-lg" novalidate>
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalLabel">Fiche Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>

      <div class="modal-body p-4">
        <input type="hidden" name="id">

        <div class="d-flex flex-column flex-md-row align-items-center gap-3 mb-3">
          <div class="me-md-3 text-center">
            <div id="clientAvatarPreview" class="client-avatar bg-light rounded-circle d-flex align-items-center justify-content-center text-primary">
              <i class="bi bi-person" aria-hidden="true"></i>
            </div>
            <div class="mt-2 d-flex gap-2 justify-content-center">
              <button type="button" id="btnChangeAvatar" class="btn btn-ghost btn-sm" title="Modifier l'image" aria-label="Modifier l'image">
                <i class="bi bi-camera-fill"></i> Changer
              </button>
              <button type="button" id="btnRemoveAvatar" class="btn btn-ghost btn-sm" title="Supprimer l'image" aria-label="Supprimer l'image">
                <i class="bi bi-x-lg"></i> Supprimer
              </button>
            </div>
          </div>

          <div class="flex-fill w-100" >
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase">Nom</label>
                <input type="text" name="nom" class="form-control" required placeholder="ex: Dupont" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase">Prénom</label>
                <input type="text" name="prenom" class="form-control" required placeholder="ex: Jean" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                  <input type="email" name="email" class="form-control border-start-0" placeholder="client@exemple.com" />
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase">Téléphone</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone"></i></span>
                  <input type="tel" name="telephone" class="form-control border-start-0" placeholder="06 12 34 56 78" />
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold text-muted text-uppercase">URL Image</label>
                <input type="url" name="image" class="form-control" placeholder="https://..." />
                <div class="form-text small text-muted">Collez une URL d'image ou utilisez le bouton "Changer" pour uploader localement.</div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold text-muted text-uppercase">Créé le</label>
                <input type="text" name="created_at" class="form-control" readonly />
              </div>
            </div>
          </div>
        </div>

        <!-- hidden file input for avatar upload -->
        <input type="file" id="clientAvatarFile" accept="image/*" style="display:none" />
      </div>

      <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-ghost px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary px-4 fw-medium">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
</main>

<script src="../assets/js/clients.js"></script>
<link rel="stylesheet" href="../assets/css/clients.css">