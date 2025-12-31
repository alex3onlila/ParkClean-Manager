/* ============================================================
   DAILY.JS - GESTION JOURNALIÈRE OPTIMISÉE
   ============================================================ */

let allVehicles = [];

document.addEventListener('DOMContentLoaded', () => {
    displayActivePeriod();
    loadEntries(); // Charge la journée en cours
    loadVehiclesList();

    const form = document.getElementById('entryForm');
    if (form) form.addEventListener('submit', handleFormSubmit);
    
    // Initialisation date recherche
    const dateInput = document.getElementById('searchDate');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
});

// --- 1. LOGIQUE DE DATE & PÉRIODE ---
function displayActivePeriod() {
    const now = new Date();
    // Règle métier : la journée commence à 06h00
    if (now.getHours() < 6) now.setDate(now.getDate() - 1);
    
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const dateStr = now.toLocaleDateString('fr-FR', options);
    
    const el = document.getElementById('activePeriodDisplay');
    if (el) el.innerText = `Journée active du ${dateStr} (06:00 → 05:59)`;
}

// --- 2. CHARGEMENT & AFFICHAGE (TABLEAU) ---
async function loadEntries(dateParam = null) {
    const tbody = document.getElementById('entriesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Chargement des données...</td></tr>';

    try {
        const url = dateParam ? `../api/entries/list.php?date=${dateParam}` : '../api/entries/list.php';
        const res = await fetch(url);
        if (!res.ok) throw new Error("Erreur serveur");
        
        const response = await res.json();
        const entries = Array.isArray(response) ? response : (response.data || []);
        
        renderTable(entries);
        updateStats(entries);

    } catch (error) {
        console.error("Erreur loadEntries:", error);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erreur de connexion API.</td></tr>';
    }
}

function renderTable(entries) {
    const tbody = document.getElementById('entriesTableBody');
    tbody.innerHTML = '';

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">Aucun véhicule enregistré pour le moment.</td></tr>';
        return;
    }

    entries.forEach(e => {
        const total = parseFloat(e.montant_total || 0);
        const recu = parseFloat(e.montant_recu || 0);
        const reste = Math.max(0, total - recu);
        
        // Formatage Heure
        let heure = "--:--";
        if (e.date_enregistrement) {
            const parts = e.date_enregistrement.split(' ');
            if (parts[1]) heure = parts[1].substring(0, 5);
        }

        // --- CONDITION 1 & 2 : LOGIQUE BADGES OK / PARKING ---
        // Si est_entree = 1 -> OK (Vert), Sinon -> PARKING (Orange)
        const badgeEntree = (e.est_entree == 1) 
            ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>OK</span>` 
            : `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="bi bi-p-square-fill me-1"></i>PARKING</span>`;

        // Si est_sorti = 1 -> OK (Vert), Sinon -> PARKING (Gris)
        const badgeSortie = (e.est_sorti == 1) 
            ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-all me-1"></i>OK</span>` 
            : `<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-hourglass-split me-1"></i>PARKING</span>`;

        const tr = document.createElement('tr');
        
        // --- RENDU DES COLONNES (Compatible avec daily.php) ---
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
                    ? `<div class="small text-danger fw-bold pulse-danger">Reste: <b>${reste.toLocaleString()} F</b></div>` 
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

function updateStats(entries) {
    let totalRecu = 0;
    let totalReste = 0;

    entries.forEach(e => {
        const total = parseFloat(e.montant_total || 0);
        const recu = parseFloat(e.montant_recu || 0);
        totalRecu += recu;
        totalReste += Math.max(0, total - recu);
    });

    if(document.getElementById('statTotalRecu')) document.getElementById('statTotalRecu').innerText = totalRecu.toLocaleString() + " F";
    if(document.getElementById('statTotalReste')) document.getElementById('statTotalReste').innerText = totalReste.toLocaleString() + " F";
    if(document.getElementById('statCount')) document.getElementById('statCount').innerText = entries.length;
}

// --- 3. ACTIONS UTILISATEUR (FORMULAIRE) ---

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    // Conversion des Checkbox en Int (1 ou 0)
    data.est_entree = document.getElementById('est_entree').checked ? 1 : 0;
    data.est_sorti = document.getElementById('est_sorti').checked ? 1 : 0;
    
    // Si c'est un disabled input, il n'est pas dans FormData, on le force
    data.montant_total = document.getElementById('montant_total_display').value;

    const url = data.id ? '../api/entries/update.php' : '../api/entries/create.php';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        
        if (res.ok && (result.success || result.id)) {
            // SUCCÈS
            const modalEl = document.getElementById('entryModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            loadEntries(); // Rafraîchir le tableau
            showToast("Opération enregistrée avec succès !", "success");
        } else {
            // ERREUR (ex: Doublon)
            alert("⛔ ATTENTION : " + (result.message || "Erreur inconnue"));
        }
    } catch (err) {
        console.error(err);
        alert("Erreur réseau. Vérifiez votre connexion.");
    }
}

// --- 4. MODAL & INTERFACE ---

function openEntryModal() {
    const form = document.getElementById('entryForm');
    form.reset(); 
    document.getElementById('entryId').value = '';
    document.getElementById('modalTitle').innerText = "Nouvelle Entrée";
    
    // Date par défaut : Maintenant
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('entryDate').value = now.toISOString().slice(0, 16);
    
    // Valeurs par défaut : Entrée = OUI, Sortie = NON
    document.getElementById('est_entree').checked = true;
    document.getElementById('est_sorti').checked = false;

    // Calculs Reset
    document.getElementById('montant_total_display').value = "";
    document.getElementById('montant_restant_display').value = "";

    new bootstrap.Modal(document.getElementById('entryModal')).show();
}

async function editEntry(id) {
    try {
        const res = await fetch(`../api/entries/get.php?id=${id}`);
        const e = await res.json();
        
        document.getElementById('entryId').value = e.id;
        document.getElementById('vehicleSelect').value = e.vehicle_id;
        document.getElementById('entryDate').value = (e.date_enregistrement || "").replace(' ', 'T').substring(0, 16);
        document.getElementById('montant_total_display').value = e.montant_total;
        document.getElementById('montant_recu').value = e.montant_recu;
        
        // Cocher les cases selon la BDD
        document.getElementById('est_entree').checked = (e.est_entree == 1);
        document.getElementById('est_sorti').checked = (e.est_sorti == 1);
        
        document.getElementById('entryObs').value = e.obs || "";
        
        calculateRemaining();
        document.getElementById('modalTitle').innerText = "Modifier l'Opération";
        new bootstrap.Modal(document.getElementById('entryModal')).show();
    } catch (err) { console.error(err); }
}

async function deleteEntry(id) {
    if(!confirm("Êtes-vous sûr de vouloir supprimer cette ligne du journal ?")) return;
    try {
        const res = await fetch('../api/entries/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const result = await res.json();
        if(result.success) loadEntries();
    } catch(err) { console.error(err); }
}

// --- 5. LOGIQUE MÉTIER & CALCULS ---

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

function onVehicleChange() {
    const select = document.getElementById('vehicleSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value !== "") {
        const prixDefaut = parseFloat(selectedOption.dataset.prix) || 0;
        document.getElementById('montant_total_display').value = prixDefaut;
        document.getElementById('montant_recu').value = prixDefaut; // Par défaut, on suppose qu'il paie tout
        calculateRemaining();
    }
}

function calculateRemaining() {
    const total = parseFloat(document.getElementById('montant_total_display').value) || 0;
    const recu = parseFloat(document.getElementById('montant_recu').value) || 0;
    const reste = Math.max(0, total - recu);
    
    const resteDisplay = document.getElementById('montant_restant_display');
    if(resteDisplay) {
        resteDisplay.value = reste;
        // Changement visuel si dette
        if(reste > 0) resteDisplay.classList.add('bg-danger', 'text-white');
        else resteDisplay.classList.remove('bg-danger', 'text-white');
    }
}

// --- UTILITAIRES ---

function loadEntriesByDate() {
    const selectedDate = document.getElementById('searchDate').value;
    if(selectedDate) loadEntries(selectedDate);
}

function filterLocal() {
    const filter = document.getElementById('searchPlate').value.toUpperCase();
    const rows = document.querySelectorAll('#entriesTableBody tr');
    rows.forEach(row => {
        const text = row.innerText.toUpperCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}

function showToast(message, type = 'success') {
    // Fonction optionnelle pour afficher un petit popup en bas
    // Nécessite un élément HTML Toast si tu veux l'utiliser
    console.log(type.toUpperCase() + ": " + message);
}

// Note: printDailyReport est conservée telle quelle ou peut être ajustée selon besoin
function printDailyReport() {
    const period = document.getElementById('activePeriodDisplay').innerText;
    const tableHtml = document.getElementById('entriesTable').outerHTML;
    // On nettoie le HTML pour l'impression (enlever les boutons actions)
    
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