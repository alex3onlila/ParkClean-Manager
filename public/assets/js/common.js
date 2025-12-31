/**
 * ParkClean Manager - Application Logic (PRODUCTION OPTIMIZED)
 * Consomme l'API définie dans routes.php
 * Version optimisée sans logs de debug en production
 */

const API_BASE = '/api';

// Configuration pour le mode développement
const DEV_MODE = false; // Mettre à true pour activer les logs

// Logger conditionnel pour éviter les console.log en production
const Logger = {
    log: (...args) => DEV_MODE && console.log(...args),
    error: (...args) => DEV_MODE && console.error(...args),
    warn: (...args) => DEV_MODE && console.warn(...args),
    info: (...args) => DEV_MODE && console.info(...args)
};

// --- Utilitaire Fetch Global avec gestion d'erreurs améliorée ---
const ParkCleanAPI = {
    async request(endpoint, method = 'GET', data = null) {
        try {
            const options = {
                method,
                headers: { 'Content-Type': 'application/json' }
            };
            if (data) options.body = JSON.stringify(data);
            
            Logger.info(`API Request: ${method} ${endpoint}`);
            const response = await fetch(`${API_BASE}${endpoint}`, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            Logger.info(`API Success: ${endpoint}`, result);
            return result;
            
        } catch (error) {
            Logger.error(`API Error (${endpoint}):`, error);
            this.notify("Une erreur est survenue lors de l'opération.", "danger");
            return null;
        }
    },

    notify(message, type = "info") {
        // Utilisation d'un système de toast si disponible
        const toastContainer = document.getElementById('toastContainer');
        if (toastContainer && typeof bootstrap !== 'undefined') {
            this.showToast(message, type, toastContainer);
        } else {
            // Fallback pour les alertes
            alert(`${type.toUpperCase()}: ${message}`);
        }
    },

    showToast(message, type, container) {
        const bgClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger', 
            warning: 'text-bg-warning',
            info: 'text-bg-info'
        }[type] || 'text-bg-primary';

        const toastHtml = `
            <div class="toast align-items-center ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${this.escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = toastHtml;
        const toastEl = tempDiv.firstElementChild;
        container.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// --- Initialisation Automatique selon la page ---
document.addEventListener('DOMContentLoaded', () => {
    const pageTitle = document.querySelector('h1')?.innerText.toLowerCase() || "";
    
    // Init theme toggle
    if (typeof initThemeToggle === 'function') initThemeToggle();

    // Auto-détection des modules présents
    if (document.getElementById('clientsTable')) loadEntities('clients');
    if (document.getElementById('vehiclesTable')) loadEntities('vehicles');
    if (document.getElementById('subscriptionTable')) loadEntities('abonnements');
    if (document.getElementById('dailyTable')) loadEntities('entries');
    if (document.getElementById('statDashboard')) loadDashboardStats();
});

// --- Gestion des Statistiques Dashboard ---
async function loadDashboardStats() {
    const stats = ['clients', 'vehicles', 'entries', 'abonnements'];
    for (const entity of stats) {
        const data = await ParkCleanAPI.request(`/${entity}/list.php`);
        const el = document.getElementById(`stat${entity.charAt(0).toUpperCase() + entity.slice(1)}`);
        if (el) el.innerText = Array.isArray(data) ? data.length : 0;
    }
}

// --- Moteur de chargement des Tables (CRUD : Read) ---
async function loadEntities(entity) {
    const tableId = entity === 'entries' ? 'dailyTable' : `${entity}Table`;
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (!tbody) return;

    // Loader state
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div> Chargement...</td></tr>';
    
    const data = await ParkCleanAPI.request(`/${entity}/list.php`);

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Aucune donnée trouvée</td></tr>';
        return;
    }

    // Utiliser la fonction de rendu spécifique si disponible
    const renderFunction = getRenderFunction(entity);
    tbody.innerHTML = data.map(item => renderFunction(item)).join('');
}

// Fonction pour obtenir le bon renderer selon l'entité
function getRenderFunction(entity) {
    switch (entity) {
        case 'clients': return renderClientRow;
        case 'vehicles': return renderVehicleRow;
        case 'entries': return renderEntryRow;
        case 'abonnements': return renderSubscriptionRow;
        default: return renderGenericRow;
    }
}

// Rendu générique pour les entités simples
function renderGenericRow(item) {
    const actions = `
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" onclick="openEditModal('${item.entity || 'unknown'}', ${item.id})">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteEntity('${item.entity || 'unknown'}', ${item.id})">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;

    return `<tr><td>${item.id}</td><td>${Object.values(item).slice(1, 4).join('</td><td>')}</td>${actions}</tr>`;
}

// --- CRUD : Create / Update ---
async function saveEntity(entity, formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const isUpdate = data.id && data.id !== "";
    const endpoint = isUpdate ? `/${entity}/update.php` : `/${entity}/create.php`;
    const result = await ParkCleanAPI.request(endpoint, 'POST', data);

    if (result && (result.success || result.error === undefined)) {
        ParkCleanAPI.notify(isUpdate ? "Modifié avec succès !" : "Ajouté avec succès !", "success");
        
        // Fermer la modal
        const modalEl = document.querySelector('.modal.show');
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        
        loadEntities(entity);
    }
}

// --- CRUD : Delete ---
async function deleteEntity(entity, id) {
    if (!confirm("Voulez-vous vraiment supprimer cet élément ?")) return;

    const result = await ParkCleanAPI.request(`/${entity}/delete.php`, 'POST', { id });
    if (result && (result.success || result.error === undefined)) {
        ParkCleanAPI.notify("Supprimé avec succès", "success");
        loadEntities(entity);
    }
}

// --- Ouvre la modale d'édition générique ---
async function openEditModal(entity, id) {
    if (!entity || !id) return;
    
    const result = await ParkCleanAPI.request(`/${entity}/get.php`, 'POST', { id });
    if (!result) return;

    // Déléguer aux fonctions spécifiques
    switch (entity) {
        case 'clients':
            if (typeof openClientModal === 'function') openClientModal(result);
            break;
        case 'vehicles':
            if (typeof openVehicleModal === 'function') openVehicleModal(result);
            break;
        default:
            ParkCleanAPI.notify(`Édition non implémentée pour ${entity}`, 'info');
    }
}

// ============================================================================
// FONCTIONS SPÉCIFIQUES AUX MODULES
// ============================================================================

// --- Module Clients ---
function renderClientRow(item) {
    const avatarUrl = item.image && item.image.trim() !== "" 
        ? item.image 
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(item.nom)}+${encodeURIComponent(item.prenom)}&background=random&color=fff&size=128`;

    const actions = `
        <td class="text-end">
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick='openClientModal(${JSON.stringify(item)})' 
                        title="Modifier">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteEntity('clients', ${item.id})" 
                        title="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </td>`;

    return `
    <tr class="align-middle">
        <td><span class="text-muted fw-bold">#${item.id}</span></td>
        <td>
            <div class="d-flex align-items-center">
                <img src="${avatarUrl}" 
                     class="rounded-circle me-3 border shadow-sm" 
                     width="40" height="40" 
                     style="object-fit:cover"
                     onerror="this.src='assets/images/default-avatar.png'">
                <div>
                    <div class="fw-bold text-dark">${item.nom} ${item.prenom}</div>
                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Client Fidèle</small>
                </div>
            </div>
        </td>
        <td>
            <div class="small mb-1 text-truncate" style="max-width: 150px;">
                <i class="bi bi-envelope text-muted me-2"></i>${item.email || '<span class="text-muted opacity-50">Aucun</span>'}
            </div>
            <div class="small text-primary fw-medium">
                <i class="bi bi-telephone me-2"></i>${item.telephone || '-'}
            </div>
        </td>
        <td class="text-center">
            <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info px-3">
                ${item.nbr_vehicules || 0} <i class="bi bi-car-front-fill ms-1"></i>
            </span>
        </td>
        ${actions}
    </tr>`;
}

// --- Module Véhicules ---
function renderVehicleRow(item) {
    const actions = `
        <td class="text-end">
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="editVehicle(${item.id})" 
                        title="Modifier">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteEntity('vehicles', ${item.id})" 
                        title="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </td>`;

    return `
    <tr class="align-middle">
        <td>
            <div class="fw-bold text-dark">${item.marque || 'Marque inconnue'}</div>
        </td>
        <td>
            <span class="badge bg-dark font-monospace text-uppercase px-2">${item.immatriculation || 'N/A'}</span>
        </td>
        <td>
            <div class="small"><i class="bi bi-person-fill text-muted me-1"></i>
                ${item.client_nom ? `${item.client_nom} ${item.client_prenom || ''}`.trim() : 'Propriétaire inconnu'}
            </div>
        </td>
        <td>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                ${item.type_nom || 'Standard'}
            </span>
        </td>
        <td>
            <img src="${item.image || 'assets/images/car-placeholder.png'}" 
                 alt="Photo du véhicule" 
                 class="rounded border shadow-sm" 
                 style="width: 40px; height: 40px; object-fit: cover;" 
                 loading="lazy">
        </td>
        ${actions}
    </tr>`;
}

// --- Module Entrées ---
function renderEntryRow(item) {
    const actions = `
        <td class="text-end">
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="editEntry(${item.id})" 
                        title="Modifier">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteEntity('entries', ${item.id})" 
                        title="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </td>`;

    return `
    <tr class="align-middle">
        <td><span class="text-muted fw-bold">#${item.id}</span></td>
        <td><span class="badge-plaque">${item.immatriculation || 'N/A'}</span></td>
        <td>${item.date_enregistrement || 'Date inconnue'}</td>
        <td class="finances-col">
            <b class="${item.montant_recu >= 0 ? 'text-success' : 'text-danger'}">
                ${item.montant_recu || 0} €
            </b>
        </td>
        <td>
            <span class="status-pill ${item.statut === 'payé' ? 'bg-success' : 'bg-warning'}">
                ${item.statut || 'En attente'}
            </span>
        </td>
        ${actions}
    </tr>`;
}

// --- Module Abonnements ---
function renderSubscriptionRow(item) {
    const actions = `
        <td class="text-end">
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="editSubscription(${item.id})" 
                        title="Modifier">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteEntity('abonnements', ${item.id})" 
                        title="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </td>`;

    return `
    <tr class="align-middle">
        <td><span class="text-muted fw-bold">#${item.id}</span></td>
        <td><span class="badge-plaque">${item.immatriculation || 'N/A'}</span></td>
        <td>${item.date_debut || 'N/A'}</td>
        <td>${item.date_fin || 'N/A'}</td>
        <td class="finances-col">
            <b class="text-success">${item.montant_recu || 0} €</b>
        </td>
        ${actions}
    </tr>`;
}

// ============================================================================
// GESTION DE L'AUTHENTIFICATION
// ============================================================================

async function handleLogin(e) {
    e.preventDefault();
    const data = {
        username: e.target.username.value,
        password: e.target.password.value
    };

    const result = await ParkCleanAPI.request('/auth/login.php', 'POST', data);
    if (result && (result.success || result.error === undefined)) {
        window.location.href = '?page=dashboard';
    } else {
        ParkCleanAPI.notify("Identifiants incorrects", "danger");
    }
}

// ============================================================================
// FONCTIONS UTILITAIRES UI
// ============================================================================

// --- Navigation et Menu ---
(function() {
    const hamburger = document.getElementById('hamburger');
    const navOverlay = document.getElementById('navOverlay');

    const toggleNav = () => {
        const open = document.getElementById('navMenu').classList.toggle('open');
        hamburger?.classList.toggle('active', open);
        navOverlay?.classList.toggle('active', open);
    };

    hamburger?.addEventListener('click', toggleNav);
    navOverlay?.addEventListener('click', toggleNav);
})();

// --- Theme Toggle ---
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try { localStorage.setItem('parkclean-theme', theme); } catch(e){}
    
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    
    const icon = btn.querySelector('i');
    if (!icon) return;
    
    icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    btn.title = theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre';
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    setTheme(next);
}

function initThemeToggle() {
    const saved = (function(){ try { return localStorage.getItem('parkclean-theme'); } catch(e){ return null } })();
    const prefer = saved || document.documentElement.getAttribute('data-theme') || 'light';
    setTheme(prefer);
    
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        toggleTheme();
    });
}

// --- User Menu ---
(function() {
    const userMenuBtn = document.getElementById('userMenu');
    const accountMenu = document.getElementById('accountMenu');
    const userWrap = document.querySelector('.user-wrap');

    function closeUserMenu() {
        if (!userMenuBtn || !accountMenu) return;
        userMenuBtn.setAttribute('aria-expanded', 'false');
        accountMenu.setAttribute('aria-hidden', 'true');
        userWrap?.classList.remove('open');
        accountMenu.classList.remove('open');
    }

    function openUserMenu() {
        if (!userMenuBtn || !accountMenu) return;
        userMenuBtn.setAttribute('aria-expanded', 'true');
        accountMenu.setAttribute('aria-hidden', 'false');
        userWrap?.classList.add('open');
        accountMenu.classList.add('open');
    }

    function toggleUserMenu(e) {
        if (!userMenuBtn || !accountMenu) return;
        const expanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
        if (expanded) closeUserMenu(); else openUserMenu();
    }

    userMenuBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleUserMenu(e);
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!accountMenu || !userMenuBtn) return;
        if (accountMenu.getAttribute('aria-hidden') === 'false') {
            if (!userMenuBtn.contains(e.target) && !accountMenu.contains(e.target)) {
                closeUserMenu();
            }
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeUserMenu();
    });
})();

// --- Recherche Globale Optimisée ---
(function() {
    const SEARCH_BUTTON_ID = 'btnSearch';
    const SEARCH_OVERLAY_ID = 'globalSearchOverlay';
    const SEARCH_INPUT_ID = 'globalSearchInput';
    const SEARCH_RESULTS_ID = 'globalSearchResults';
    const SEARCH_DEBOUNCE_MS = 250;

    const SEARCH_ENDPOINTS = [
        { key: 'clients', url: '/api/clients/list.php', label: 'Clients' },
        { key: 'vehicles', url: '/api/vehicles/list.php', label: 'Véhicules' },
        { key: 'entries', url: '/api/entries/list.php', label: 'Entrées' },
        { key: 'abonnements', url: '/api/abonnements/list.php', label: 'Abonnements' }
    ];

    function debounce(fn, wait = SEARCH_DEBOUNCE_MS) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    }

    function attachSearchButton() {
        const btn = document.getElementById(SEARCH_BUTTON_ID);
        if (!btn) return;
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            openOverlay(); 
        });
    }

    document.addEventListener('DOMContentLoaded', attachSearchButton);
})();

// ============================================================================
// APP LOADER OPTIMISÉ
// ============================================================================
document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("appLoader");
    if (!loader) return;

    // Si déjà lancé une fois → pas de loader
    if (localStorage.getItem("parkclean_launched")) {
        loader.remove();
        return;
    }

    // Première fois seulement
    loader.classList.remove("hidden");

    setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => {
            loader.remove();
            localStorage.setItem("parkclean_launched", "true");
        }, 600);
    }, 2000); // Réduit à 2 secondes pour de meilleures performances
});

// ============================================================================
// GESTION DU LOGOUT
// ============================================================================
(function() {
    const LOGOUT_BTN_ID = 'btnLogout';
    const LOGOUT_ENDPOINT = '/api/auth/logout.php';
    const LOGIN_PAGE = '?page=login';

    async function logout() {
        try {
            const res = await fetch(LOGOUT_ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin'
            });
            window.location.href = LOGIN_PAGE;
        } catch (err) {
            Logger.error('Logout error', err);
            window.location.href = LOGIN_PAGE;
        }
    }

    function attachLogout() {
        const btn = document.getElementById(LOGOUT_BTN_ID);
        if (!btn) return;
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Voulez‑vous vraiment vous déconnecter ?')) {
                logout();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', attachLogout);
})();

// ============================================================================
// TOGGLE PASSWORD VISIBILITY
// ============================================================================
(function() {
    function togglePasswordForButton(btn) {
        const targetSelector = btn.getAttribute('data-target') || btn.getAttribute('aria-controls') || btn.dataset.target;
        let input;

        if (targetSelector) {
            input = document.querySelector(targetSelector);
        } else {
            const scope = btn.closest('form, .password-row, .input-group') || document;
            input = scope.querySelector('input[type="password"], input[type="text"].password-toggle');
        }

        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';

        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        }

        btn.setAttribute('aria-pressed', String(show));
        btn.title = show ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
        btn.focus();
    }

    function attachToggleButtons() {
        document
            .querySelectorAll('#togglePassword, [data-toggle="password"]')
            .forEach(btn => {
                btn.setAttribute('role', 'button');
                btn.setAttribute('aria-pressed', 'false');

                btn.addEventListener('click', e => {
                    e.preventDefault();
                    togglePasswordForButton(btn);
                });

                btn.addEventListener('keydown', e => {
                    if (e.key === ' ' || e.key === 'Enter') {
                        e.preventDefault();
                        togglePasswordForButton(btn);
                    }
                });
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachToggleButtons);
    } else {
        attachToggleButtons();
    }
})();
