/**
 * ParkClean Manager - Gestion des Véhicules
 * Interface JavaScript pour la gestion CRUD des véhicules et types de véhicules.
 * Compatible avec Bootstrap 5 et APIs PHP.
 * 
 * Fonctionnalités :
 * - Chargement, affichage, création, modification et suppression des véhicules.
 * - Gestion des types de véhicules.
 * - Recherche et tri locaux.
 * - Aperçu d'images avant upload.
 * - Notifications via toasts.
 * 
 * @author [Votre Nom ou Équipe]
 * @version 1.0.0
 * @date 2023
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeApplication();
});

// --- CONFIGURATION ET CONSTANTES ---
const API_URLS = {
    list: '/api/vehicles/list.php',
    create: '/api/vehicles/create.php',
    update: '/api/vehicles/update.php',
    delete: '/api/vehicles/delete.php',
    get: '/api/vehicles/get.php',
    createType: '/api/vehicle_types/create.php',
    listTypes: '/api/vehicle_types/list.php'
};

const DEFAULT_IMAGE_PLACEHOLDER = 'assets/images/car-placeholder.png';
let vehiclesData = []; // Stockage local des données pour recherche/tri
let isLoading = false; // Indicateur de chargement pour éviter les appels multiples

// --- INITIALISATION DE L'APPLICATION ---
function initializeApplication() {
    loadVehicles();
    setupEventListeners();
    refreshTypeSelect(); // Charger les types au démarrage
}

// --- CONFIGURATION DES ÉVÉNEMENTS ---
function setupEventListeners() {
    // Soumission du formulaire véhicule
    const vehicleForm = document.getElementById('vehicleForm');
    if (vehicleForm) {
        vehicleForm.addEventListener('submit', handleVehicleSubmit);
    }

    // Soumission du formulaire type
    const typeForm = document.getElementById('typeForm');
    if (typeForm) {
        typeForm.addEventListener('submit', handleTypeSubmit);
    }

    // Recherche en temps réel
    const searchInput = document.getElementById('vehicleSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce((e) => filterVehicles(e.target.value), 300));
    }

    // Tri
    const sortSelect = document.getElementById('sortVehicles');
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => sortVehicles(e.target.value));
    }

    // Aperçu d'image
    const imageInput = document.getElementById('imageInput');
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }
}

// --- GESTION DES DONNÉES VÉHICULES (READ) ---
async function loadVehicles() {
    if (isLoading) return; // Éviter les appels multiples
    isLoading = true;

    const tbody = document.getElementById('vehiclesTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div> Chargement en cours...</td></tr>';

    try {
        const response = await fetch(API_URLS.list);
        if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);

        vehiclesData = await response.json();
        if (!Array.isArray(vehiclesData)) throw new Error('Format de données invalide');

        renderVehicles(vehiclesData);
        showToast('Données chargées avec succès', 'success');
    } catch (error) {
        console.error('Erreur lors du chargement des véhicules:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur lors du chargement des données. Veuillez réessayer.</td></tr>';
        showToast('Erreur de chargement', 'danger');
    } finally {
        isLoading = false;
    }
}

function renderVehicles(data) {
    const tbody = document.getElementById('vehiclesTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-2"></i>Aucun véhicule trouvé.</td></tr>';
        return;
    }

    data.forEach(vehicle => {
        const imgPath = vehicle.image && vehicle.image.trim() !== '' ? vehicle.image : DEFAULT_IMAGE_PLACEHOLDER;
        const clientName = vehicle.client_nom ? `${vehicle.client_nom} ${vehicle.client_prenom || ''}`.trim() : 'Propriétaire inconnu';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${escapeHtml(vehicle.marque)}</div>
            </td>
            <td>
                <span class="badge bg-dark font-monospace text-uppercase px-2">${escapeHtml(vehicle.immatriculation)}</span>
            </td>
            <td>
                <div class="small"><i class="bi bi-person-fill text-muted me-1"></i>${escapeHtml(clientName)}</div>
            </td>
            <td>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">${escapeHtml(vehicle.type_nom || 'Standard')}</span>
            </td>
            <td>
                <img src="${imgPath}" alt="Photo du véhicule" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" loading="lazy">
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary border-0 me-1" onclick="editVehicle(${vehicle.id})" title="Modifier le véhicule" aria-label="Modifier">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteVehicle(${vehicle.id})" title="Supprimer le véhicule" aria-label="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// --- GESTION DES FORMULAIRES (CREATE / UPDATE) ---
async function handleVehicleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Validation côté client basique
    if (!formData.get('marque') || !formData.get('immatriculation') || !formData.get('client_id') || !formData.get('type_id')) {
        showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        return;
    }

    const isUpdate = !!formData.get('id');
    const url = isUpdate ? API_URLS.update : API_URLS.create;

    // Désactiver le bouton de soumission pour éviter les doubles soumissions
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast(isUpdate ? 'Véhicule modifié avec succès.' : 'Véhicule ajouté avec succès.', 'success');

            // Fermer la modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('vehicleModal'));
            if (modalInstance) modalInstance.hide();

            // Réinitialiser le formulaire et recharger les données
            form.reset();
            document.getElementById('imagePreview').innerHTML = '';
            loadVehicles();
        } else {
            showToast(result.error || 'Une erreur est survenue lors de l\'enregistrement.', 'danger');
        }
    } catch (error) {
        console.error('Erreur lors de la soumission:', error);
        showToast('Erreur de communication avec le serveur.', 'danger');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Enregistrer';
    }
}

async function handleTypeSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.type || data.type.trim() === '') {
        showToast('Veuillez saisir un nom pour le type.', 'warning');
        return;
    }

    try {
        const response = await fetch(API_URLS.createType, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('Nouveau type ajouté avec succès.', 'success');

            // Fermer la modal et réinitialiser
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('typeModal'));
            if (modalInstance) modalInstance.hide();
            form.reset();

            // Rafraîchir la liste des types
            await refreshTypeSelect();
        } else {
            showToast(result.error || 'Erreur lors de l\'ajout du type.', 'danger');
        }
    } catch (error) {
        console.error('Erreur lors de la création du type:', error);
        showToast('Erreur de communication avec le serveur.', 'danger');
    }
}

// --- RAFRAÎCHISSEMENT DES TYPES ---
async function refreshTypeSelect() {
    try {
        const response = await fetch(API_URLS.listTypes);
        if (!response.ok) throw new Error('Erreur lors du chargement des types');

        const types = await response.json();
        if (!Array.isArray(types)) throw new Error('Format des types invalide');

        const select = document.getElementById('typeSelect');
        if (select) {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Sélectionnez un type</option>';
            types.forEach(type => {
                const option = document.createElement('option');
                option.value = type.id;
                option.textContent = escapeHtml(type.type);
                select.appendChild(option);
            });
            // Restaurer la sélection si possible
            if (currentValue && types.some(t => t.id == currentValue)) {
                select.value = currentValue;
            }
        }
    } catch (error) {
        console.error('Erreur lors du rafraîchissement des types:', error);
        showToast('Impossible de rafraîchir les types de véhicules.', 'warning');
    }
}

// --- ACTIONS UNITAIRES (EDIT / DELETE) ---
window.openVehicleModal = function() {
    const form = document.getElementById('vehicleForm');
    form.reset();
    form.querySelector('[name="id"]').value = '';

    // Mise à jour du titre et reset de l'aperçu
    document.getElementById('vehicleModalLabel').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Nouveau Véhicule';
    document.getElementById('imagePreview').innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('vehicleModal'));
    modal.show();
};

window.editVehicle = async function(id) {
    if (!id || isNaN(id)) {
        showToast('ID de véhicule invalide.', 'warning');
        return;
    }

    try {
        const response = await fetch(`${API_URLS.get}?id=${id}`);
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const vehicle = await response.json();
        if (vehicle.error) {
            showToast(vehicle.error, 'danger');
            return;
        }

        // Remplir le formulaire
        const form = document.getElementById('vehicleForm');
        form.querySelector('[name="id"]').value = vehicle.id;
        form.querySelector('[name="client_id"]').value = vehicle.client_id;
        form.querySelector('[name="marque"]').value = vehicle.marque;
        form.querySelector('[name="immatriculation"]').value = vehicle.immatriculation;
        form.querySelector('[name="type_id"]').value = vehicle.type_id;

        // Titre de la modal
        document.getElementById('vehicleModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Modifier Véhicule';

        // Aperçu de l'image existante
        const previewContainer = document.getElementById('imagePreview');
        if (vehicle.image && vehicle.image.trim() !== '') {
            // Construire le chemin complet de l'image
            const imagePath = vehicle.image.startsWith('uploads/') ? vehicle.image : vehicle.image;
            previewContainer.innerHTML = `<img src="${imagePath}" alt="Aperçu" class="img-thumbnail mt-2" style="max-height: 100px;" onerror="this.style.display='none';">`;
        } else {
            previewContainer.innerHTML = '';
        }

        const modal = new bootstrap.Modal(document.getElementById('vehicleModal'));
        modal.show();
    } catch (error) {
        console.error('Erreur lors de l\'édition:', error);
        showToast('Impossible de charger les détails du véhicule.', 'danger');
    }
};

window.deleteVehicle = async function(id) {
    if (!id || isNaN(id)) {
        showToast('ID de véhicule invalide.', 'warning');
        return;
    }

    if (!confirm('Voulez-vous vraiment supprimer ce véhicule ? Cette action est irréversible.')) return;

    try {
        const response = await fetch(API_URLS.delete, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        });

        const result = await response.json();

        if (result.success) {
            showToast('Véhicule supprimé avec succès.', 'success');
            loadVehicles();
        } else {
            showToast(result.error || 'Erreur lors de la suppression.', 'danger');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showToast('Erreur de communication avec le serveur.', 'danger');
    }
};

// --- UTILITAIRES (FILTRES, TRI, IMAGE, TOAST) ---
function filterVehicles(query) {
    const lowerQuery = query.toLowerCase().trim();
    const filtered = vehiclesData.filter(vehicle =>
        vehicle.marque.toLowerCase().includes(lowerQuery) ||
        vehicle.immatriculation.toLowerCase().includes(lowerQuery) ||
        (vehicle.client_nom && vehicle.client_nom.toLowerCase().includes(lowerQuery)) ||
        (vehicle.client_prenom && vehicle.client_prenom.toLowerCase().includes(lowerQuery))
    );
    renderVehicles(filtered);
}

function sortVehicles(criteria) {
    let sorted = [...vehiclesData];
    switch (criteria) {
        case 'marque':
            sorted.sort((a, b) => a.marque.localeCompare(b.marque, 'fr', { sensitivity: 'base' }));
            break;
        case 'recent':
        default:
            sorted.sort((a, b) => b.id - a.id); // Tri par ID décroissant (plus récents en premier)
            break;
    }
    renderVehicles(sorted);
}

function handleImagePreview(event) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById('imagePreview');

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.innerHTML = `<img src="${e.target.result}" alt="Aperçu de l'image" class="img-thumbnail mt-2" style="max-height: 100px;">`;
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.innerHTML = file ? '<div class="text-danger mt-2">Fichier non valide. Veuillez sélectionner une image.</div>' : '';
    }
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) {
        console.warn('Conteneur de toast non trouvé.');
        return;
    }

    const bgClass = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info'
    }[type] || 'text-bg-primary';

    const toastHtml = `
        <div class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        </div>
    `;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = toastHtml;
    const toastEl = tempDiv.firstElementChild;

    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

// --- FONCTIONS UTILITAIRES ---
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}