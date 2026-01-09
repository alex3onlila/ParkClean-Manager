/* ============================================================
   DAILY.JS - GESTION JOURNALIÈRE SIMPLE
   ============================================================ */

let allVehicles = [];

document.addEventListener('DOMContentLoaded', () => {
    displayActivePeriod();
    loadEntries();
    loadVehiclesList();

    const form = document.getElementById('entryForm');
    if (form) form.addEventListener('submit', handleFormSubmit);

    const dateInput = document.getElementById('searchDate');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
});

// Affichage de la période active
function displayActivePeriod() {
    const now = new Date();
    if (now.getHours() < 6) now.setDate(now.getDate() - 1);

    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const dateStr = now.toLocaleDateString('fr-FR', options);

    const el = document.getElementById('activePeriodDisplay');
    if (el) el.innerText = `Journée active du ${dateStr} (06:00 → 05:59)`;
}

// Chargement des entrées pour un jour
async function loadEntries(dateParam = null) {
    const tbody = document.getElementById('entriesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Chargement...</td></tr>';

    try {
        const url = dateParam ? `../api/entries/list.php?date=${dateParam}&days=1` : '../api/entries/list.php?days=1';
        const res = await fetch(url);
        if (!res.ok) throw new Error("Erreur serveur");

        const response = await res.json();
        const entries = Array.isArray(response) ? response : (response.data || []);

        renderTable(entries);
        updateStats(entries);

    } catch (error) {
        console.error("Erreur loadEntries:", error);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erreur de connexion.</td></tr>';
        if (typeof ParkCleanAPI !== 'undefined') {
            ParkCleanAPI.showToast('Erreur lors du chargement des entrées', 'danger');
        }
    }
}

// Rendu du tableau
function renderTable(entries) {
    const tbody = document.getElementById('entriesTableBody');
    tbody.innerHTML = '';

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">Aucun véhicule enregistré.</td></tr>';
        return;
    }

    entries.forEach(e => {
        const total = parseFloat(e.montant_total || 0);
        const recu = parseFloat(e.montant_recu || 0);
        const reste = Math.max(0, total - recu);

        let heure = "--:--";
        if (e.date_enregistrement) {
            const parts = e.date_enregistrement.split(' ');
            if (parts[1]) heure = parts[1].substring(0, 5);
        }

        const badgeEntree = (e.est_entree == 1)
            ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>OK</span>`
            : `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="bi bi-p-square-fill me-1"></i>PARKING</span>`;

        const badgeSortie = (e.est_sorti == 1)
            ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-all me-1"></i>OK</span>`
            : `<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-hourglass-split me-1"></i>PARKING</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 fw-bold text-muted">${heure}</td>
            <td>
                <div class="fw-bold text-dark">${e.client_nom || 'Client Anonyme'}</div>
                <div class="small text-muted">${e.marque || '-'}</div>
            </td>
            <td><span class="badge-plaque">${e.immatriculation || '---'}</span></td>
            <td class="col-categorie">
                <span class="badge bg-light text-dark border">${e.categorie || 'Standard'}</span>
            </td>
            <td class="finances-col">
                <div class="small">Total: <b>${total.toLocaleString()} F</b></div>
                <div class="small text-success">Reçu: <b>${recu.toLocaleString()} F</b></div>
                ${reste > 0
                    ? `<div class="small text-danger fw-bold">Reste: <b>${reste.toLocaleString()} F</b></div>`
                    : '<div class="small text-muted">Reste: 0 F</div>'}
            </td>
            <td class="text-center">${badgeEntree}</td>
            <td class="text-center">${badgeSortie}</td>
            <td class="text-end pe-4">
                <div class="btn-group shadow-sm">
                    <button class="btn btn-sm btn-light border" onclick="editEntry(${e.id})" title="Modifier"><i class="bi bi-pencil text-primary"></i></button>
                    <button class="btn btn-sm btn-light border" onclick="deleteEntry(${e.id})" title="Supprimer"><i class="bi bi-trash text-danger"></i></button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Mise à jour des statistiques
function updateStats(entries) {
    let totalRecu = 0;
    let totalReste = 0;

    entries.forEach(e => {
        const total = parseFloat(e.montant_total || 0);
        const recu = parseFloat(e.montant_recu || 0);
        totalRecu += recu;
        totalReste += Math.max(0, total - recu);
    });

    document.getElementById('statTotalRecu').innerText = totalRecu.toLocaleString() + " F";
    document.getElementById('statTotalReste').innerText = totalReste.toLocaleString() + " F";
    document.getElementById('statCount').innerText = entries.length;
}

// Soumission du formulaire
async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    data.est_entree = document.getElementById('est_entree').checked ? 1 : 0;
    data.est_sorti = document.getElementById('est_sorti').checked ? 1 : 0;
    data.montant_total = document.getElementById('montant_total_display').value;

    const url = data.id ? '../api/entries/update.php' : '../api/entries/create.php';

    // Afficher le toast de chargement
    let loadingToast = null;
    if (typeof ParkCleanAPI !== 'undefined') {
        loadingToast = ParkCleanAPI.showLoading('Enregistrement en cours...');
    }

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await res.json();

        if (loadingToast) ParkCleanAPI.hideLoading();

        if (res.ok && (result.success || result.id)) {
            const modalEl = document.getElementById('entryModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            loadEntries();
            if (typeof ParkCleanAPI !== 'undefined') {
                ParkCleanAPI.showToast(data.id ? 'Opération modifiée avec succès !' : 'Nouvelle entrée ajoutée avec succès !', 'success');
            } else {
                alert("Opération enregistrée avec succès !");
            }
        } else {
            const errorMsg = "Erreur : " + (result.message || "Erreur inconnue");
            if (typeof ParkCleanAPI !== 'undefined') {
                ParkCleanAPI.showToast(errorMsg, 'danger');
            } else {
                alert(errorMsg);
            }
        }
    } catch (err) {
        if (loadingToast) ParkCleanAPI.hideLoading();
        console.error(err);
        const errorMsg = "Erreur réseau.";
        if (typeof ParkCleanAPI !== 'undefined') {
            ParkCleanAPI.showToast(errorMsg, 'danger');
        } else {
            alert(errorMsg);
        }
    }
}

// Ouverture de la modal pour nouvelle entrée
function openEntryModal() {
    const form = document.getElementById('entryForm');
    form.reset();
    document.getElementById('entryId').value = '';
    document.getElementById('modalTitle').innerText = "Nouvelle Entrée";

    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('entryDate').value = now.toISOString().slice(0, 16);

    document.getElementById('est_entree').checked = true;
    document.getElementById('est_sorti').checked = false;

    document.getElementById('montant_total_display').value = "";
    document.getElementById('montant_restant_display').value = "";

    new bootstrap.Modal(document.getElementById('entryModal')).show();
}

// Édition d'une entrée
async function editEntry(id) {
    try {
        const res = await fetch(`../api/entries/get.php?id=${id}`);
        const e = await res.json();

        document.getElementById('entryId').value = e.id;
        document.getElementById('vehicleSelect').value = e.vehicle_id;
        document.getElementById('entryDate').value = (e.date_enregistrement || "").replace(' ', 'T').substring(0, 16);
        document.getElementById('montant_total_display').value = e.montant_total;
        document.getElementById('montant_recu').value = e.montant_recu;

        document.getElementById('est_entree').checked = (e.est_entree == 1);
        document.getElementById('est_sorti').checked = (e.est_sorti == 1);

        document.getElementById('entryObs').value = e.obs || "";

        calculateRemaining();
        document.getElementById('modalTitle').innerText = "Modifier l'Opération";
        new bootstrap.Modal(document.getElementById('entryModal')).show();
    } catch (err) { console.error(err); }
}

// Suppression d'une entrée
async function deleteEntry(id) {
    if(!confirm("Êtes-vous sûr de vouloir supprimer cette ligne ?")) return;
    
    let loadingToast = null;
    if (typeof ParkCleanAPI !== 'undefined') {
        loadingToast = ParkCleanAPI.showLoading('Suppression en cours...');
    }
    
    try {
        const res = await fetch('../api/entries/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const result = await res.json();
        
        if (loadingToast) ParkCleanAPI.hideLoading();
        
        if(result.success) {
            loadEntries();
            if (typeof ParkCleanAPI !== 'undefined') {
                ParkCleanAPI.showToast('Entrée supprimée avec succès', 'success');
            }
        } else {
            const errorMsg = result.message || 'Erreur lors de la suppression';
            if (typeof ParkCleanAPI !== 'undefined') {
                ParkCleanAPI.showToast(errorMsg, 'danger');
            } else {
                alert(errorMsg);
            }
        }
    } catch(err) {
        if (loadingToast) ParkCleanAPI.hideLoading();
        console.error(err);
        if (typeof ParkCleanAPI !== 'undefined') {
            ParkCleanAPI.showToast('Erreur de connexion', 'danger');
        } else {
            alert('Erreur de connexion');
        }
    }
}

// Chargement de la liste des véhicules
async function loadVehiclesList() {
    try {
        const res = await fetch('../api/vehicles/list.php');
        const data = await res.json();
        allVehicles = Array.isArray(data) ? data : [];

        const select = document.getElementById('vehicleSelect');
        if (!select) return;

        select.innerHTML = '<option value="">-- Sélectionner un véhicule --</option>';
        allVehicles.forEach(v => {
            select.innerHTML += `<option value="${v.id}" data-prix="${v.prix_lavage}">${v.immatriculation} - ${v.marque}</option>`;
        });
    } catch(e) { console.error("Erreur véhicules:", e); }
}

// Changement de véhicule
function onVehicleChange() {
    const select = document.getElementById('vehicleSelect');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value !== "") {
        const prixDefaut = parseFloat(selectedOption.dataset.prix) || 0;
        document.getElementById('montant_total_display').value = prixDefaut;
        document.getElementById('montant_recu').value = prixDefaut;
        calculateRemaining();
    }
}

// Calcul du reste à payer
function calculateRemaining() {
    const total = parseFloat(document.getElementById('montant_total_display').value) || 0;
    const recu = parseFloat(document.getElementById('montant_recu').value) || 0;
    const reste = Math.max(0, total - recu);

    const resteDisplay = document.getElementById('montant_restant_display');
    if(resteDisplay) {
        resteDisplay.value = reste;
        if(reste > 0) resteDisplay.classList.add('bg-danger', 'text-white');
        else resteDisplay.classList.remove('bg-danger', 'text-white');
    }
}

// Chargement par date
function loadEntriesByDate() {
    const selectedDate = document.getElementById('searchDate').value;
    if(selectedDate) loadEntries(selectedDate);
}

// Filtrage local
function filterLocal() {
    const filter = document.getElementById('searchPlate').value.toUpperCase();
    const rows = document.querySelectorAll('#entriesTableBody tr');
    rows.forEach(row => {
        const text = row.innerText.toUpperCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}

// Impression du rapport
function printDailyReport() {
    const period = document.getElementById('activePeriodDisplay').innerText;
    const tableHtml = document.getElementById('entriesTable').outerHTML;

    const win = window.open('', '', 'height=700,width=900');
    win.document.write(`<html><head><title>Rapport</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{padding:20px} .btn-group, th:last-child, td:last-child {display:none}</style>
    </head><body>
    <h2>Rapport Journalier - ParkClean</h2>
    <h5>${period}</h5>
    <hr>${tableHtml}
    <script>window.print(); window.close();</script>
    </body></html>`);
    win.document.close();
}
