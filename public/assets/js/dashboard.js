/**
 * ParkClean Manager - Dashboard Logic
 * Gère la récupération des statistiques et des derniers mouvements
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialisation
    loadDashboardStats();
    loadRecentMovements();

    // Rafraîchissement automatique toutes les 5 minutes
    setInterval(() => {
        loadDashboardStats();
        loadRecentMovements();
    }, 300000);
});

/**
 * Récupère les compteurs globaux (Clients, Véhicules, etc.)
 */
async function loadDashboardStats() {
    try {
        // On récupère les données via vos endpoints API
        const endpoints = {
            clients: '/api/clients/list.php',
            vehicles: '/api/vehicles/list.php',
            entries: '/api/entries/list.php',
            abonnements: '/api/abonnements/list.php'
        };

        // Exécution des requêtes en parallèle pour plus de vitesse
        const [clients, vehicles, entries, abonnements] = await Promise.all(
            Object.values(endpoints).map(url => fetch(url).then(res => res.json()))
        );

        // Mise à jour de l'affichage avec animation
        updateCounter('statClients', clients.data?.length || 0);
        updateCounter('statVehicles', vehicles.data?.length || 0);
        updateCounter('statAbonnements', abonnements.data?.length || 0);

        // Pour les entrées du jour, on filtre les données
        const today = new Date().toISOString().split('T')[0];
        const entriesToday = entries.data?.filter(e => e.date_enregistrement.includes(today)) || [];
        updateCounter('statEntries', entriesToday.length);

    } catch (error) {
        console.error('Erreur lors du chargement des stats:', error);
        showErrorInStats();
    }
}

/**
 * Récupère et affiche les derniers mouvements dans le tableau
 */
async function loadRecentMovements() {
    const tableBody = document.querySelector('#dailyTable tbody');
    
    try {
        const response = await fetch('/api/entries/list.php');
        const result = await response.json();

        if (result.success && result.data) {
            // On ne garde que les 10 derniers mouvements
            const recentData = result.data.slice(0, 10);
            
            tableBody.innerHTML = ''; // On vide le spinner

            if (recentData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Aucun mouvement aujourd\'hui</td></tr>';
                return;
            }

            recentData.forEach(entry => {
                const row = `
                    <tr class="animate-fade-in">
                        <td>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-mono">
                                ${entry.vehicle_plate || entry.vehicle_id}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted font-medium">${entry.vehicle_type || 'Véhicule'}</span>
                        </td>
                        <td>
                            <span class="text-emerald-600 fw-bold">${formatCurrency(entry.montant_recu)}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="small fw-bold text-gray-700">${formatDate(entry.date_enregistrement)}</span>
                                <span class="text-xs text-gray-400">${formatTime(entry.date_enregistrement)}</span>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        }
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erreur de connexion aux données</td></tr>';
    }
}

/**
 * Utilitaires de formatage
 */
function updateCounter(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF' }).format(amount);
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function showErrorInStats() {
    ['statClients', 'statVehicles', 'statEntries', 'statAbonnements'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = 'Err';
    });
}