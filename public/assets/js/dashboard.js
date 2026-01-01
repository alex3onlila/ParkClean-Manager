/**
 * ParkClean Manager - Logiciel de Gestion v2.3
 * Dashboard : Cycle Entrée/Sortie & Synchronisation Temporelle Locale
 */

document.addEventListener('DOMContentLoaded', () => {
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
    refreshDashboard();
    setInterval(refreshDashboard, 60000); // Auto-refresh toutes les minutes
});

async function refreshDashboard() {
    console.log(`[${new Date().toLocaleTimeString()}] Actualisation des données...`);
    await loadDashboardStats();
    await loadRecentMovements();
}

/**
 * HORLOGE : Calée sur l'heure du système client (Brazzaville)
 */
function updateLiveClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.innerText = now.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: false 
        });
    }
}

/**
 * STATISTIQUES : Compteurs et Chiffre d'Affaires du jour
 */
async function loadDashboardStats() {
    try {
        const endpoints = {
            clients: '../api/clients/list.php',
            vehicles: '../api/vehicles/list.php',
            entries: '../api/entries/list.php',
            abonnements: '../api/abonnements/list.php'
        };

        const results = await Promise.allSettled(
            Object.values(endpoints).map(url => fetch(url).then(res => res.json()))
        );

        const extractData = (res) => {
            if (res.status !== 'fulfilled') return [];
            const val = res.value;
            return Array.isArray(val) ? val : (val.data || val.items || []);
        };

        const clientsData = extractData(results[0]);
        const vehiclesData = extractData(results[1]);
        const entriesData = extractData(results[2]);
        const abonnementsData = extractData(results[3]);

        // Calcul Date Locale YYYY-MM-DD
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        const todayStr = (new Date(now - offset)).toISOString().split('T')[0];

        // Mise à jour des compteurs globaux
        updateCounter('statClients', clientsData.length);
        updateCounter('statVehicles', vehiclesData.length);
        updateCounter('statAbonnements', abonnementsData.filter(a => a.est_actif == 1).length);

        // Filtrage Entrées du jour
        const entriesToday = entriesData.filter(e => {
            const entryDate = e.date_enregistrement || e.created_at;
            return entryDate && entryDate.startsWith(todayStr);
        });

        updateCounter('statEntries', entriesToday.length);

        // CA du jour
        const totalRevenue = entriesToday.reduce((sum, e) => sum + (parseFloat(e.montant_recu || 0)), 0);
        const revenueEl = document.getElementById('cardRevenueToday');
        if (revenueEl) revenueEl.innerText = formatCurrency(totalRevenue);

    } catch (error) {
        console.error('Erreur stats dashboard:', error);
        showErrorInStats();
    }
}

/**
 * JOURNAL DE BORD : Flux Entrée/Sortie
 */
async function loadRecentMovements() {
    const tableBody = document.querySelector('#dailyTable tbody');
    if (!tableBody) return;

    try {
        const res = await fetch('../api/entries/list.php');
        const response = await res.json();
        const entries = Array.isArray(response) ? response : (response.data || []);
        
        // --- GESTION FUSEAU HORAIRE LOCAL ---
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        const todayStr = (new Date(now - offset)).toISOString().split('T')[0];

        const todayEntries = entries.filter(e => {
            const d = e.date_enregistrement || e.created_at;
            return d && d.startsWith(todayStr);
        });

        if (todayEntries.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted fw-bold">Aucun véhicule enregistré pour le moment.</td></tr>';
            return;
        }

        // Tri par plus récent
        todayEntries.sort((a, b) => new Date(b.date_enregistrement) - new Date(a.date_enregistrement));

        tableBody.innerHTML = todayEntries.map(e => {
            // 1. Logique Financière (Inspirée de daily.js)
            const total = parseFloat(e.montant_total || 0);
            const recu = parseFloat(e.montant_recu || 0);
            const reste = Math.max(0, total - recu);

            // 2. Formatage Heure (Inspiré de daily.js)
            let heure = "--:--";
            if (e.date_enregistrement) {
                const parts = e.date_enregistrement.split(' ');
                if (parts[1]) heure = parts[1].substring(0, 5);
            }

            // 3. Logique des Badges OK / PARKING (Inspirée de daily.js)
            const badgeEntree = (e.est_entree == 1) 
                ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>OK</span>` 
                : `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="bi bi-p-square-fill me-1"></i>PARKING</span>`;

            const badgeSortie = (e.est_sorti == 1) 
                ? `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-all me-1"></i>OK</span>` 
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-hourglass-split me-1"></i>PARKING</span>`;

            return `
                <tr class="align-middle border-bottom">
                    <td class="py-3 fw-bold text-primary font-monospace">${heure}</td>
                    
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark">${e.client_nom || 'Client Anonyme'}</span>
                            <span class="small text-muted">${e.marque || '-'}</span>
                        </div>
                    </td>
                    
                    <td><span class="badge bg-dark text-white font-monospace px-2 py-1 border shadow-sm">${e.immatriculation || '---'}</span></td>
                    
                    <td><span class="text-uppercase small fw-bold text-muted bg-light border px-2 py-1 rounded">${e.categorie || 'Standard'}</span></td>
                    
                    <td>
                        <div class="small text-success fw-bold">${recu.toLocaleString()} F</div>
                        ${reste > 0 
                            ? `<div class="small text-danger" style="font-size: 0.75rem;">Reste: ${reste.toLocaleString()} F</div>` 
                            : '<div class="small text-muted" style="font-size: 0.75rem;">Soldé</div>'}
                    </td>
                    
                    <td class="text-center">${badgeEntree}</td>
                    
                    <td class="text-center">${badgeSortie}</td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        console.error("Erreur Dashboard:", error);
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Erreur de liaison API</td></tr>';
    }
}

/**
 * OUTILS DE FORMATAGE
 */
function updateCounter(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(amount || 0);
}

function formatTime(dateStr) {
    if (!dateStr) return '--:--';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false });
}

function showErrorInStats() {
    ['statClients', 'statVehicles', 'statEntries', 'statAbonnements'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i>';
    });
}