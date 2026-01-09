/**
 * ParkClean Manager - Dashboard v2.4
 * Gestion des données en temps réel depuis la base de données
 * Auto-refresh avec notifications visuelles
 */

document.addEventListener('DOMContentLoaded', () => {
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
    
    // Initialiser le sélecteur de plage de dates
    initDateRangeSelector();
    
    // Premier chargement complet
    refreshDashboard(true);
    
    // Auto-refresh toutes les minutes
    setInterval(() => refreshDashboard(false), 60000);
    
    // Chargement données financières (intervalle différent)
    loadFinanceData();
    setInterval(loadFinanceData, 120000);
});

/**
 * Utilitaire : Extraction des données quelle que soit la structure de l'API
 * Gère les formats: [array], {data: array}, {items: array}, {success: true, items: array}
 */
function extractApiData(response) {
    if (!response) return [];
    
    // Si c'est déjà un tableau
    if (Array.isArray(response)) return response;
    
    // Si c'est un objet avec propriété data
    if (response.data && Array.isArray(response.data)) return response.data;
    
    // Si c'est un objet avec propriété items
    if (response.items && Array.isArray(response.items)) return response.items;
    
    // Si c'est un objet avec propriété success et data
    if (response.success && response.data && Array.isArray(response.data)) return response.data;
    
    // Si c'est un objet avec propriété success et items
    if (response.success && response.items && Array.isArray(response.items)) return response.items;
    
    // Retourner tableau vide par défaut
    return [];
}

/**
 * Sélecteur de plage de dates
 */
function initDateRangeSelector() {
    const selector = document.getElementById('dateRangeSelector');
    if (!selector) return;
    
    // Définir la valeur par défaut (7 jours)
    selector.value = '7';
    
    selector.addEventListener('change', (e) => {
        const days = parseInt(e.target.value);
        localStorage.setItem('dashboardDays', days);
        refreshDashboard(true);
        loadFinanceData();
    });
    
    // Restaurer la dernière sélection
    const savedDays = localStorage.getItem('dashboardDays');
    if (savedDays) {
        selector.value = savedDays;
    }
}

function getDateRangeDays() {
    const selector = document.getElementById('dateRangeSelector');
    return selector ? parseInt(selector.value) : 7;
}

/**
 * Horloge en temps réel (Brazzaville - WAT, UTC+1)
 */
function updateLiveClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        const brazzavilleTime = new Date(now.getTime() + (60 * 60 * 1000));
        clockEl.innerText = brazzavilleTime.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: false 
        });
    }
}

/**
 * Obtenir la date actuelle au format YYYY-MM-DD (heure de Brazzaville)
 */
function getLocalDateString() {
    const now = new Date();
    const brazzavilleOffset = 60 * 60 * 1000;
    const localNow = new Date(now.getTime() + brazzavilleOffset);
    return localNow.toISOString().split('T')[0];
}

/**
 * Obtenir le mois actuel au format YYYY-MM (heure de Brazzaville)
 */
function getLocalMonthString() {
    const now = new Date();
    const brazzavilleOffset = 60 * 60 * 1000;
    const localNow = new Date(now.getTime() + brazzavilleOffset);
    return localNow.toISOString().substring(0, 7);
}

/**
 * Notifications visuelles - Utilise le système unifié
 */
function showNotification(message, type = 'info') {
    if (typeof ParkCleanAPI !== 'undefined') {
        ParkCleanAPI.showToast(message, type);
    } else {
        // Fallback si ParkCleanAPI n'est pas disponible
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

function showLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) el.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Chargement...';
}

function showError(elementId, message = 'Erreur') {
    const el = document.getElementById(elementId);
    if (el) el.innerHTML = `<i class="bi bi-exclamation-triangle text-danger" title="${message}"></i>`;
}

/**
 * Rafraîchissement complet du dashboard
 */
async function refreshDashboard(isFullRefresh = false) {
    const timestamp = new Date().toLocaleTimeString('fr-FR');
    console.log(`[${timestamp}] ${isFullRefresh ? 'Chargement initial' : 'Actualisation des données...'}`);
    
    try {
        await Promise.all([
            loadDashboardStats(),
            loadRecentMovements()
        ]);
        
        // Mise à jour de l'heure de dernière mise à jour
        const lastUpdateEl = document.getElementById('lastUpdateTime');
        if (lastUpdateEl) {
            const now = new Date();
            lastUpdateEl.innerText = now.toLocaleDateString('fr-FR') + ' ' + now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Notification uniquement pour les refresh automatiques
        if (!isFullRefresh) {
            showNotification('Données actualisées', 'success');
        }
        
    } catch (error) {
        console.error('Erreur refresh dashboard:', error);
        showNotification('Erreur lors de la mise à jour des données', 'danger');
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

        // Extraction des données avec gestion des différents formats
        const clientsData = extractApiData(results[0].status === 'fulfilled' ? results[0].value : null);
        const vehiclesData = extractApiData(results[1].status === 'fulfilled' ? results[1].value : null);
        const entriesData = extractApiData(results[2].status === 'fulfilled' ? results[2].value : null);
        const abonnementsData = extractApiData(results[3].status === 'fulfilled' ? results[3].value : null);

        // Date locale Brazzaville
        const todayStr = getLocalDateString();

        // Mise à jour des compteurs globaux
        updateCounter('statClients', clientsData.length);
        updateCounter('statVehicles', vehiclesData.length);
        
        // Abonnements actifs (valides et dans la période)
        const activeAbonnements = abonnementsData.filter(a => a.est_valide == 1);
        updateCounter('statAbonnements', activeAbonnements.length);

        // Filtrage Entrées du jour
        const entriesToday = entriesData.filter(e => {
            const entryDate = e.date_enregistrement || e.created_at;
            return entryDate && entryDate.startsWith(todayStr);
        });

        updateCounter('statEntries', entriesToday.length);

        // CA du jour (basé sur montant_recu)
        const totalRevenue = entriesToday.reduce((sum, e) => sum + (parseFloat(e.montant_recu || 0)), 0);
        updateCounter('financeRevenueToday', formatCurrency(totalRevenue));

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
        const days = getDateRangeDays();
        const res = await fetch(`../api/entries/list.php?days=${days}`);
        const response = await res.json();
        const rawEntries = extractApiData(response);
        
        // Données enveloppées dans success/data
        const entries = rawEntries.length > 0 ? rawEntries : (response.data || []);

        // Date locale Brazzaville
        const todayStr = getLocalDateString();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);
        const startDateStr = startDate.toISOString().split('T')[0];

        // Filtrer les entrées dans la plage de dates
        const filteredEntries = entries.filter(e => {
            const d = e.date_enregistrement || e.created_at;
            return d && d >= startDateStr && d <= todayStr;
        });

        if (filteredEntries.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted fw-bold">Aucune entrée enregistrée sur les ${days} derniers jours.</td></tr>`;
            return;
        }

        // Tri par plus récent
        filteredEntries.sort((a, b) => new Date(b.date_enregistrement) - new Date(a.date_enregistrement));

        tableBody.innerHTML = filteredEntries.map(e => {
            // Logique Financière
            const total = parseFloat(e.montant_total || 0);
            const recu = parseFloat(e.montant_recu || 0);
            const reste = Math.max(0, total - recu);

            // Formatage Heure (Heure de Brazzaville)
            let heure = "--:--";
            if (e.date_enregistrement) {
                const parts = e.date_enregistrement.split(' ');
                if (parts[1]) {
                    const timeParts = parts[1].split(':');
                    let hours = parseInt(timeParts[0]) + 1;
                    if (hours >= 24) hours -= 24;
                    heure = `${String(hours).padStart(2, '0')}:${timeParts[1]}`;
                }
            }

            // Badges Entrée/Sortie
            const badgeEntree = (e.est_entree == 1) 
                ? `<span class="badge-status bg-success-subtle"><i class="bi bi-check-circle-fill"></i>OK</span>` 
                : `<span class="badge-status bg-warning-subtle"><i class="bi bi-p-square-fill"></i>PARKING</span>`;

            const badgeSortie = (e.est_sorti == 1) 
                ? `<span class="badge-status bg-success-subtle"><i class="bi bi-check-all"></i>OK</span>` 
                : `<span class="badge-status bg-secondary-subtle"><i class="bi bi-hourglass-split"></i>PARKING</span>`;

            return `
                <tr>
                    <td class="fw-bold text-primary font-monospace">${heure}</td>
                    <td>
                        <span class="fw-bold text-dark">${e.client_nom || 'Client Anonyme'}</span>
                        <span class="text-muted" style="font-size: 0.78rem;">${e.marque || '-'}</span>
                    </td>
                    <td><span class="badge-plaque">${e.immatriculation || '---'}</span></td>
                    <td><span class="text-uppercase fw-bold text-muted" style="font-size: 0.78rem;">${e.categorie || 'Standard'}</span></td>
                    <td>
                        <span class="text-success fw-bold">${recu.toLocaleString()} F</span>
                        ${reste > 0 
                            ? `<span class="text-danger" style="font-size: 0.75rem; display: block;">Reste: ${reste.toLocaleString()} F</span>` 
                            : '<span class="text-muted" style="font-size: 0.75rem; display: block;">Soldé</span>'}
                    </td>
                    <td>${badgeEntree}</td>
                    <td>${badgeSortie}</td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        console.error("Erreur Dashboard:", error);
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erreur de liaison API</td></tr>';
    }
}

/**
 * OUTILS DE FORMATAGE
 */
function updateCounter(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = typeof value === 'number' ? value.toLocaleString('fr-FR') : value;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(amount || 0);
}

function showErrorInStats() {
    ['statClients', 'statVehicles', 'statEntries', 'statAbonnements', 'financeRevenueToday'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i>';
    });
}

/**
 * DONNÉES FINANCIÈRES : Revenus, Abonnements, Créances
 */
async function loadFinanceData() {
    try {
        console.log(`[${new Date().toLocaleTimeString()}] Chargement des données financières...`);

        // Récupération des données depuis les APIs
        const [entriesRes, abonnementsRes, paymentsRes] = await Promise.allSettled([
            fetch('../api/entries/list.php').then(res => res.json()),
            fetch('../api/abonnements/list.php').then(res => res.json()),
            fetch('../api/payments/list.php').then(res => res.json())
        ]);

        // Extraction des données avec gestion des formats
        const entries = extractApiData(entriesRes.status === 'fulfilled' ? entriesRes.value : null);
        const abonnements = extractApiData(abonnementsRes.status === 'fulfilled' ? abonnementsRes.value : null);
        const payments = extractApiData(paymentsRes.status === 'fulfilled' ? paymentsRes.value : null);

        // Dates locales
        const todayStr = getLocalDateString();
        const currentMonth = getLocalMonthString();
        const currentMonthStr = currentMonth;

        // 1. Revenus du jour (déjà fait dans loadDashboardStats, mais on refait pour cohérence)
        const todayEntries = entries.filter(e => {
            const entryDate = e.date_enregistrement || e.created_at;
            return entryDate && entryDate.startsWith(todayStr);
        });
        const revenueToday = todayEntries.reduce((sum, e) => sum + (parseFloat(e.montant_recu || 0)), 0);
        updateCounter('financeRevenueToday', formatCurrency(revenueToday));

        // 2. Revenus abonnements (mensualités actives - mois en cours)
        const activeAbonnements = abonnements.filter(a => {
            if (a.est_valide != true && a.est_valide != 1) return false;
            const debut = a.date_debut ? a.date_debut.substring(0, 7) : '';
            const fin = a.date_fin ? a.date_fin.substring(0, 7) : '';
            return debut <= currentMonthStr && fin >= currentMonthStr;
        });
        
        const subscriptionsRevenue = activeAbonnements.reduce((sum, a) => {
            if (a.montant_mensuel && parseFloat(a.montant_mensuel) > 0) {
                return sum + parseFloat(a.montant_mensuel);
            }
            const total = parseFloat(a.montant_init || 0);
            const months = calculateMonthsBetween(a.date_debut, a.date_fin);
            return sum + (months > 0 ? total / months : 0);
        }, 0);
        updateCounter('financeSubscriptions', formatCurrency(subscriptionsRevenue));

        // 3. Revenus du mois (entrées du mois + abonnements actifs)
        const monthEntries = entries.filter(e => {
            const entryDate = e.date_enregistrement || e.created_at;
            return entryDate && entryDate.startsWith(currentMonth);
        });
        const revenueMonthEntries = monthEntries.reduce((sum, e) => sum + (parseFloat(e.montant_recu || 0)), 0);
        updateCounter('financeRevenueMonth', formatCurrency(revenueMonthEntries + subscriptionsRevenue));

        // 4. Créances en attente
        const pendingAmount = entries.reduce((sum, e) => {
            const total = parseFloat(e.montant_total || 0);
            const recu = parseFloat(e.montant_recu || 0);
            return sum + Math.max(0, total - recu);
        }, 0);
        updateCounter('financePending', formatCurrency(pendingAmount));

        // 5. Récapitulatif mensuel
        await loadMonthlySummary(entries, abonnements);

        // 6. Top clients
        await loadTopClients(payments, entries, abonnements);

    } catch (error) {
        console.error('Erreur chargement finances:', error);
        showErrorInFinance();
    }
}

/**
 * Helper: Calculer le nombre de mois entre deux dates
 */
function calculateMonthsBetween(startDate, endDate) {
    if (!startDate || !endDate) return 0;
    const start = new Date(startDate);
    const end = new Date(endDate);
    return (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth()) + 1;
}

/**
 * RÉCAPITULATIF MENSUEL
 */
async function loadMonthlySummary(entries, abonnements) {
    const tbody = document.getElementById('monthlySummaryBody');
    if (!tbody) return;

    try {
        const monthlyData = {};
        const days = getDateRangeDays();
        
        // Dates locales
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);
        const startMonth = startDate.toISOString().substring(0, 7);
        const endMonth = getLocalMonthString();
        
        // Initialiser tous les mois
        const current = new Date(startDate);
        while (current.toISOString().substring(0, 7) <= endMonth) {
            const mKey = current.toISOString().substring(0, 7);
            monthlyData[mKey] = { entries: 0, subscriptions: 0 };
            current.setMonth(current.getMonth() + 1);
        }

        // Calcul des Entrées
        entries.forEach(e => {
            const dateStr = e.date_enregistrement || e.created_at;
            if (!dateStr) return;
            const month = dateStr.substring(0, 7);
            if (monthlyData[month]) {
                monthlyData[month].entries += parseFloat(e.montant_recu || 0);
            }
        });

        // Calcul des Abonnements
        abonnements.forEach(a => {
            if (a.est_valide != 1 && a.est_valide != true) return;
            if (!a.date_debut || !a.date_fin) return;
            
            const monthlyAmount = parseFloat(a.montant_mensuel || 0);
            const start = a.date_debut.substring(0, 7);
            const end = a.date_fin.substring(0, 7);

            Object.keys(monthlyData).forEach(mKey => {
                if (mKey >= start && mKey <= end) {
                    monthlyData[mKey].subscriptions += monthlyAmount;
                }
            });
        });

        // Tri et Rendu
        const sortedMonths = Object.keys(monthlyData).sort().reverse();

        tbody.innerHTML = sortedMonths.map(month => {
            const data = monthlyData[month];
            const total = data.entries + data.subscriptions;
            
            const dateObj = new Date(month + "-01");
            const monthName = dateObj.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
            const capitalizedMonth = monthName.charAt(0).toUpperCase() + monthName.slice(1);

            return `
                <tr>
                    <td class="fw-bold">${capitalizedMonth}</td>
                    <td class="text-primary fw-bold font-monospace">${formatCurrency(data.entries)}</td>
                    <td class="fw-bold font-monospace" style="color: #4f46e5;">${formatCurrency(data.subscriptions)}</td>
                    <td class="text-success fw-bold font-monospace">${formatCurrency(total)}</td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        console.error('Erreur récapitulatif mensuel:', error);
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Erreur de calcul des données</td></tr>';
    }
}

/**
 * TOP CLIENTS : Liste des meilleurs clients (inclut entrées, paiements et abonnements)
 */
async function loadTopClients(payments, entries, abonnements) {
    const list = document.getElementById('topClientsList');
    if (!list) return;

    try {
        const clientTotals = {};

        // 1. Agréger les paiements par client
        payments.forEach(p => {
            const clientId = p.client_id;
            if (!clientId) return;
            if (!clientTotals[clientId]) {
                clientTotals[clientId] = { total: 0, name: p.client_nom || 'Client Anonyme' };
            }
            clientTotals[clientId].total += parseFloat(p.montant || 0);
        });

        // 2. Ajouter les entrées (si pas déjà dans payments)
        entries.forEach(e => {
            const clientId = e.client_id;
            if (!clientId) return;
            const montant = parseFloat(e.montant_recu || 0);
            if (!clientTotals[clientId]) {
                clientTotals[clientId] = { total: montant, name: e.client_nom || 'Client Anonyme' };
            } else if (montant > 0) {
                clientTotals[clientId].total += montant;
            }
        });

        // 3. Ajouter les abonnements actifs
        const currentMonthStr = getLocalMonthString();
        abonnements.forEach(a => {
            // Vérifier si l'abonnement est actif ce mois-ci
            if (a.est_valide != 1 && a.est_valide != true) return;
            
            const debut = a.date_debut ? a.date_debut.substring(0, 7) : '';
            const fin = a.date_fin ? a.date_fin.substring(0, 7) : '';
            
            if (debut <= currentMonthStr && fin >= currentMonthStr) {
                const clientId = a.client_id;
                if (!clientId) return;
                
                const monthlyAmount = a.montant_mensuel && parseFloat(a.montant_mensuel) > 0 
                    ? parseFloat(a.montant_mensuel)
                    : parseFloat(a.montant_init || 0) / calculateMonthsBetween(a.date_debut, a.date_fin);
                
                if (!clientTotals[clientId]) {
                    clientTotals[clientId] = { total: monthlyAmount, name: (a.nom || a.client_nom || 'Client Anonyme') + ' ' + (a.prenom || '') };
                } else {
                    clientTotals[clientId].total += monthlyAmount;
                }
            }
        });

        // Trier et prendre top 5
        const topClients = Object.values(clientTotals)
            .sort((a, b) => b.total - a.total)
            .slice(0, 5);

        if (topClients.length === 0) {
            list.innerHTML = '<li class="text-center text-muted py-4">Aucune donnée disponible</li>';
            return;
        }

        list.innerHTML = topClients.map((client, index) => `
            <li>
                <span class="badge">${index + 1}</span>
                <span class="fw-bold">${client.name}</span>
                <span class="text-success">${formatCurrency(client.total)}</span>
            </li>
        `).join('');

    } catch (error) {
        console.error('Erreur top clients:', error);
        list.innerHTML = '<li class="text-center text-danger py-4">Erreur de chargement</li>';
    }
}

/**
 * RAFRAÎCHIR LES FINANCES : Fonction appelée par le bouton
 */
function refreshFinance() {
    const btn = document.querySelector('button[onclick="refreshFinance()"]');
    if (btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualisation...';
        btn.disabled = true;
    }

    loadFinanceData().finally(() => {
        if (btn) {
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualiser';
            btn.disabled = false;
        }
        showNotification('Données financières actualisées', 'success');
    });
}

/**
 * GESTION ERREURS FINANCES
 */
function showErrorInFinance() {
    ['financeRevenueToday', 'financeSubscriptions', 'financeRevenueMonth', 'financePending'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i>';
    });

    const tbody = document.getElementById('monthlySummaryBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Erreur de chargement</td></tr>';

    const list = document.getElementById('topClientsList');
    if (list) list.innerHTML = '<li class="list-group-item text-center text-danger py-4">Erreur de chargement</li>';
}
