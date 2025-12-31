/* ============================================================
   PARKCLEAN - SUBSCRIPTION.JS (COMPLET & OPTIMISÉ)
   ============================================================ */

let allVehicles = [];

// Configuration des durées et multiplicateurs de prix
const PLAN_CONFIG = {
    'hebdo': { days: 7, mult: 6 },       // 7 jours, paye 6
    'mensuel': { days: 30, mult: 25 },   // 30 jours, paye 25
    'trimestriel': { days: 90, mult: 70 }, // 90 jours, paye 70
    'custom': { days: 0, mult: 1 }       // Libre
};

document.addEventListener('DOMContentLoaded', () => {
    loadSubscriptions();
    loadVehiclesList();

    const form = document.getElementById('subForm');
    if (form) form.addEventListener('submit', handleSubSubmit);
});

// --- 1. CHARGEMENT DES DONNÉES (API list.php) ---

async function loadSubscriptions() {
    const tbody = document.getElementById('subsTableBody');
    if (!tbody) return;

    try {
        const res = await fetch('../api/abonnements/list.php');
        const data = await res.json();
        
        // On s'assure que data est un tableau
        const subs = Array.isArray(data) ? data : (data.data || []);
        
        renderTable(subs);
        updateStats(subs);
    } catch (error) {
        console.error("Erreur API list:", error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erreur de chargement des abonnements.</td></tr>';
    }
}

function renderTable(subs) {
    const tbody = document.getElementById('subsTableBody');
    tbody.innerHTML = '';

    if (!subs || subs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Aucun abonnement trouvé.</td></tr>';
        return;
    }

    subs.forEach(s => {
        // 1. Badge d'état (Utilise est_valide de ton JSON)
        const statusBadge = s.est_valide 
            ? `<span class="badge bg-success-subtle text-success border border-success px-3 rounded-pill fw-bold small">ACTIF</span>`
            : `<span class="badge bg-danger-subtle text-danger border border-danger px-3 rounded-pill fw-bold small">EXPIRÉ</span>`;

        // 2. Formatage des montants
        const total = parseFloat(s.montant_total).toLocaleString();
        const recu = parseFloat(s.montant_recu).toLocaleString();
        const reste = parseFloat(s.montant_restant).toLocaleString();
        const aUneDette = parseFloat(s.montant_restant) > 0;

        const tr = document.createElement('tr');
        tr.className = "border-bottom";
        
        tr.innerHTML = `
            <td class="ps-4">${statusBadge}</td>
            <td>
                <div class="fw-bold text-dark">${formatDate(s.date_debut)}</div>
                <div class="small text-muted">au ${formatDate(s.date_fin)}</div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div>
                        <div class="fw-bold text-primary mb-0">${s.immatriculation}</div>
                        <div class="text-dark fw-medium" style="font-size: 0.9rem;">${s.nom} ${s.prenom}</div>
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <span class="badge bg-light text-dark border-0 p-0 small text-muted">
                                <i class="bi bi-tag-fill me-1"></i>${s.type_vehicule}
                            </span>
                            ${s.obs ? `<i class="bi bi-info-circle text-warning" title="${s.obs}"></i>` : ''}
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div class="small text-muted">Total: <span class="text-dark fw-bold">${total} F</span></div>
                <div class="small text-muted">Reçu: <span class="text-success fw-medium">${recu} F</span></div>
                <div class="small mt-1 ${aUneDette ? 'text-danger fw-bold' : 'text-success'}">
                    ${aUneDette ? `<i class="bi bi-exclamation-triangle-fill me-1"></i>Reste: ${reste} F` : '<i class="bi bi-check-circle-fill me-1"></i>Réglé'}
                </div>
            </td>
            <td class="text-end pe-4">
                <div class="btn-group shadow-sm">
                    <button class="btn btn-sm btn-white border" onclick="editSub(${s.id})" title="Modifier">
                        <i class="bi bi-pencil-square text-primary"></i>
                    </button>
                    <button class="btn btn-sm btn-white border" onclick="deleteSub(${s.id})" title="Supprimer">
                        <i class="bi bi-trash3-fill text-danger"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}
// --- 2. CALCULS AUTOMATIQUES & LOGIQUE MÉTIER ---

function calculateDatesFromPlan() {
    const planKey = document.getElementById('planSelect').value;
    const vehicleId = document.getElementById('vehicleSelect').value;
    const dateDebutInput = document.getElementById('dateDebut');
    const dateFinInput = document.getElementById('dateFin');
    const montantTotalInput = document.getElementById('montantTotal');

    if (!dateDebutInput.value) return;

    // A. Calcul de la date de fin suggérée
    if (planKey !== 'custom') {
        const startDate = new Date(dateDebutInput.value);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + PLAN_CONFIG[planKey].days);
        dateFinInput.value = endDate.toISOString().split('T')[0];
    }

    // B. Calcul du montant suggéré
    const vehicle = allVehicles.find(v => v.id == vehicleId);
    if (vehicle) {
        const basePrice = parseFloat(vehicle.prix_lavage) || 0;
        document.getElementById('basePrice').value = basePrice.toLocaleString() + " F";
        
        // On ne suggère le prix que si on est en mode "création"
        if (!document.getElementById('subId').value) {
            const suggestedTotal = basePrice * PLAN_CONFIG[planKey].mult;
            montantTotalInput.value = suggestedTotal;
            document.getElementById('montantRecu').value = suggestedTotal; // Par défaut, on suppose payé
        }
    }

    updateRemaining();
}

function updateRemaining() {
    const total = parseFloat(document.getElementById('montantTotal').value) || 0;
    const recu = parseFloat(document.getElementById('montantRecu').value) || 0;
    
    // Règle métier : Jamais en dessous de zéro
    const reste = Math.max(0, total - recu);
    
    const display = document.getElementById('montantResteDisplay');
    display.value = reste.toLocaleString() + " F";
    display.className = reste > 0 ? "form-control-plaintext fw-bold text-danger" : "form-control-plaintext fw-bold text-success";
}

// --- 3. GESTION DES ACTIONS (APIS CREATE, GET, UPDATE, DELETE) ---

async function handleSubSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Détermination de l'API à appeler
    const isUpdate = data.id && data.id !== "";
    const url = isUpdate ? '../api/abonnements/update.php' : '../api/abonnements/create.php';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('subModal')).hide();
            loadSubscriptions();
        } else {
            alert("Erreur : " + result.message);
        }
    } catch (error) {
        alert("Erreur lors de la communication avec le serveur.");
    }
}

async function editSub(id) {
    try {
        const res = await fetch(`../api/abonnements/get.php?id=${id}`);
        const s = await res.json();

        if (s) {
            document.getElementById('subId').value = s.id;
            document.getElementById('vehicleSelect').value = s.vehicle_id;
            document.getElementById('dateDebut').value = s.date_debut;
            document.getElementById('dateFin').value = s.date_fin;
            document.getElementById('montantTotal').value = s.montant_total;
            document.getElementById('montantRecu').value = s.montant_recu;
            document.getElementById('subObs').value = s.obs || "";
            document.getElementById('planSelect').value = 'custom'; // On passe en custom pour l'édition

            document.getElementById('modalTitle').innerText = "Modifier l'Abonnement";
            updateRemaining();
            new bootstrap.Modal(document.getElementById('subModal')).show();
        }
    } catch (error) {
        alert("Impossible de charger les données de l'abonnement.");
    }
}

async function deleteSub(id) {
    if (!confirm("Voulez-vous vraiment supprimer cet abonnement ?")) return;

    try {
        const res = await fetch('../api/abonnements/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) loadSubscriptions();
    } catch (error) {
        alert("Erreur lors de la suppression.");
    }
}

// --- 4. UTILITAIRES & INITIALISATION ---

async function loadVehiclesList() {
    try {
        const res = await fetch('../api/vehicles/list.php');
        allVehicles = await res.json();
        const sel = document.getElementById('vehicleSelect');
        
        sel.innerHTML = '<option value="">-- Choisir véhicule --</option>';
        allVehicles.forEach(v => {
            sel.innerHTML += `<option value="${v.id}">${v.immatriculation} (${v.type || '-'}) - ${v.client_nom || 'Client'}</option>`;
        });
    } catch (e) {
        console.error("Erreur chargement véhicules:", e);
    }
}

function openSubModal() {
    const form = document.getElementById('subForm');
    form.reset();
    document.getElementById('subId').value = "";
    document.getElementById('modalTitle').innerText = "Nouvel Abonnement";
    document.getElementById('dateDebut').valueAsDate = new Date();
    document.getElementById('planSelect').value = 'mensuel';
    calculateDatesFromPlan(); // Initialise les suggestions
    new bootstrap.Modal(document.getElementById('subModal')).show();
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function updateStats(subs) {
    document.getElementById('statTotalSubs').innerText = subs.length;
    
    const active = subs.filter(s => s.est_valide).length;
    document.getElementById('statActiveSubs').innerText = active;
    
    const totalReste = subs.reduce((acc, curr) => acc + parseFloat(curr.montant_restant || 0), 0);
    document.getElementById('statReste').innerText = totalReste.toLocaleString() + " F";
}