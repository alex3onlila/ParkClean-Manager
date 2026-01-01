/**
 * ParkClean Manager - Gestion des Véhicules
 * Version 1.3 - Finale & Optimisée (Sans focus Modal)
 */

const DEFAULT_IMAGE_PLACEHOLDER = 'assets/images/car-placeholder.png';

const API_URLS = {
    list: '/api/vehicles/list.php',
    create: '/api/vehicles/create.php',
    update: '/api/vehicles/update.php',
    delete: '/api/vehicles/delete.php',
    get: '/api/vehicles/get.php',
    listTypes: '/api/vehicle_types/list.php'
};

let vehiclesData = [];

document.addEventListener('DOMContentLoaded', () => {
    initializeApplication();
});

function initializeApplication() {
    loadVehicles();
    setupEventListeners();
    refreshTypeSelect();
}

function setupEventListeners() {
    const vehicleForm = document.getElementById('vehicleForm');
    if (vehicleForm) {
        vehicleForm.addEventListener('submit', handleVehicleSubmit);
    }

    const searchInput = document.getElementById('vehicleSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => filterVehicles(e.target.value));
    }

    const imageInput = document.getElementById('imageInput');
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }
}

// --- CHARGEMENT ET RENDU ---

async function loadVehicles() {
    const tbody = document.getElementById('vehiclesTableBody');
    if (!tbody) return;

    try {
        const response = await fetch(API_URLS.list);
        vehiclesData = await response.json();
        renderVehicles(vehiclesData);
    } catch (error) {
        console.error('Erreur chargement:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Erreur de communication avec le serveur</td></tr>';
    }
}

function renderVehicles(data) {
    const tbody = document.getElementById('vehiclesTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Aucun véhicule trouvé</td></tr>';
        return;
    }

    data.forEach(v => {
        let imgPath = DEFAULT_IMAGE_PLACEHOLDER;
        if (v.image && v.image.trim() !== '') {
            imgPath = v.image.replace(/^public\//, '');
        }

        const prenom = v.prenom || v.client_prenom || '';
        const nom = v.nom || v.client_nom || '';
        const clientFull = `${prenom} ${nom}`.trim() || 'Inconnu';

        const row = document.createElement('tr');
        row.className = "align-middle";
        row.innerHTML = `
            <td class="fw-bold text-dark">${v.marque}</td>
            <td><span class="badge bg-dark font-monospace" style="letter-spacing:1px">${v.immatriculation}</span></td>
            <td><div class="small"><i class="bi bi-person me-1"></i>${clientFull}</div></td>
            <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">${v.type_nom || 'Standard'}</span></td>
            <td>
                <img src="${imgPath}" class="rounded border shadow-sm" 
                     style="width: 40px; height: 40px; object-fit: cover;" 
                     onerror="this.src='${DEFAULT_IMAGE_PLACEHOLDER}'">
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary border-0" onclick="editVehicle(${v.id})">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteVehicle(${v.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// --- ACTIONS (EDIT / DELETE / SUBMIT) ---

window.editVehicle = async function(id) {
    try {
        const response = await fetch(`${API_URLS.get}?id=${id}`);
        const vehicle = await response.json();

        if (vehicle.error) return alert(vehicle.error);

        const form = document.getElementById('vehicleForm');
        
        form.querySelector('[name="id"]').value = vehicle.id;
        form.querySelector('[name="marque"]').value = vehicle.marque;
        form.querySelector('[name="immatriculation"]').value = vehicle.immatriculation;

        if (vehicle.client_id) form.querySelector('[name="client_id"]').value = String(vehicle.client_id);
        if (vehicle.type_id) form.querySelector('[name="type_id"]').value = String(vehicle.type_id);

        const preview = document.getElementById('imagePreview');
        if (vehicle.image) {
            const cleanPath = vehicle.image.replace(/^public\//, '');
            preview.innerHTML = `<img src="${cleanPath}" class="img-thumbnail mt-2" style="max-height: 100px;">`;
        } else {
            preview.innerHTML = "";
        }

        document.getElementById('vehicleModalLabel').innerText = "Modifier le Véhicule";
        
        // MODIFICATION ICI : Ajout de focus: false pour enlever l'effet de mise au point auto
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleModal'), {
            focus: false
        });
        modal.show();
    } catch (e) { console.error("Erreur édit:", e); }
};

async function handleVehicleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const isUpdate = formData.get('id') !== "";
    const url = isUpdate ? API_URLS.update : API_URLS.create;

    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('vehicleModal')).hide();
            loadVehicles();
            form.reset();
        } else {
            alert("Erreur: " + (result.message || result.error));
        }
    } catch (e) { console.error("Erreur submit:", e); }
}

window.openVehicleModal = function() {
    const form = document.getElementById('vehicleForm');
    form.reset();
    form.querySelector('[name="id"]').value = "";
    document.getElementById('imagePreview').innerHTML = "";
    document.getElementById('vehicleModalLabel').innerText = "Nouveau Véhicule";
    
    // MODIFICATION ICI : Ajout de focus: false
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleModal'), {
        focus: false
    });
    modal.show();
};

window.deleteVehicle = async function(id) {
    if (!confirm('Voulez-vous vraiment supprimer ce véhicule ?')) return;
    try {
        const res = await fetch(API_URLS.delete, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) loadVehicles();
    } catch (e) { console.error("Erreur delete:", e); }
};

// --- UTILITAIRES ---

function handleImagePreview(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail mt-2" style="max-height: 100px;">`;
        };
        reader.readAsDataURL(file);
    }
}

async function refreshTypeSelect() {
    const select = document.getElementById('typeSelect');
    if (!select) return;
    try {
        const res = await fetch(API_URLS.listTypes);
        const types = await res.json();
        const existingOptions = select.options[0].outerHTML; 
        select.innerHTML = existingOptions + types.map(t => `<option value="${t.id}">${t.type}</option>`).join('');
    } catch (e) { console.error("Erreur types:", e); }
}

function filterVehicles(query) {
    const q = query.toLowerCase().trim();
    const filtered = vehiclesData.filter(v => 
        v.marque.toLowerCase().includes(q) || 
        v.immatriculation.toLowerCase().includes(q) ||
        (v.client_nom && v.client_nom.toLowerCase().includes(q))
    );
    renderVehicles(filtered);
}