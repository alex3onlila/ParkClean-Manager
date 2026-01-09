(() => {
  const API = {
    list: '/api/clients/list.php',
    get: '/api/clients/get.php',
    create: '/api/clients/create.php',
    update: '/api/clients/update.php',
    delete: '/api/clients/delete.php',
    export: '/api/export/pdf.php'
  };

  // DOM
  const tableBody = document.querySelector('#clientsTable tbody');
  const searchInput = document.getElementById('clientSearch');
  const sortSelect = document.getElementById('sortClients');
  const btnNew = document.getElementById('btnNewClient');
  const btnExport = document.getElementById('btnExportClients');
  const toast = document.getElementById('clientsToast');
  const clientModalEl = document.getElementById('clientModal');
  const clientForm = document.getElementById('clientForm');
  let bsModal = (window.bootstrap && clientModalEl) ? new bootstrap.Modal(clientModalEl) : null;

  // State
  const state = { limit: 100, offset: 0, sort: sortSelect?.value || 'recent', query: '' };

  // Debounce
  function debounce(fn, wait = 250) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    attachEvents();
    loadClients();
  });

  function attachEvents() {
    if (searchInput) searchInput.addEventListener('input', debounce(() => {
      state.query = searchInput.value.trim().toLowerCase();
      filterTable('clientsTable', 'clientSearch');
    }, 250));
    if (sortSelect) sortSelect.addEventListener('change', () => { state.sort = sortSelect.value; loadClients(); });
    if (btnNew) btnNew.addEventListener('click', () => openClientModal());
    if (btnExport) btnExport.addEventListener('click', exportClients);
    if (clientForm) clientForm.addEventListener('submit', saveClient);
    if (tableBody) tableBody.addEventListener('click', delegateActions);
  }

  // Fetch helper
  async function apiFetch(url, options = {}, timeout = 10000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);
    try {
      const res = await fetch(url, { credentials: 'same-origin', signal: controller.signal, ...options });
      clearTimeout(timer);
      const text = await res.text();
      let json = null;
      try { json = text ? JSON.parse(text) : null; } catch (e) { throw new Error('Réponse API invalide'); }
      if (!res.ok) throw new Error(json?.message || `Erreur ${res.status}`);
      return json;
    } catch (err) {
      clearTimeout(timer);
      throw err;
    }
  }

  // Load list
  async function loadClients() {
    if (!tableBody) return;
    tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Chargement des clients…</td></tr>`;
    try {
      const qs = new URLSearchParams({ limit: state.limit, offset: state.offset, sort: state.sort }).toString();
      const json = await apiFetch(`${API.list}?${qs}`);
      const items = Array.isArray(json.items) ? json.items : [];
      renderClients(items);
    } catch (err) {
      console.error('loadClients', err);
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Impossible de charger les clients</td></tr>`;
      showToast(err.message || 'Erreur réseau', 'danger');
    }
  }

  // Render clients
  function renderClients(items) {
    if (!tableBody) return;
    const seen = new Set();
    const unique = [];
    for (const it of items) {
      const id = String(it.id ?? '');
      if (!id) continue;
      if (!seen.has(id)) { seen.add(id); unique.push(it); }
    }

    if (!unique.length) {
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Aucun client</td></tr>`;
      return;
    }

    tableBody.innerHTML = unique.map(c => clientRowHtml(c)).join('');
  }

  function clientRowHtml(c) {
  const id = escapeHtml(c.id ?? '');
  const nom = escapeHtml(c.nom ?? '');
  const prenom = escapeHtml(c.prenom ?? '');
  const emailVal = c.email ? String(c.email).trim() : '';
  const email = emailVal ? `<a href="mailto:${escapeHtml(emailVal)}">${escapeHtml(emailVal)}</a>` : '<span class="text-muted small">—</span>';
  const telVal = c.telephone ? String(c.telephone).trim() : '';
  const telephone = telVal ? `<a href="tel:${escapeHtml(telVal)}">${escapeHtml(telVal)}</a>` : '<span class="text-muted small">—</span>';

  const imgRaw = normalizeImage(c.image ?? '');
  const imageHtml = imgRaw
    ? `<img src="${escapeHtml(imgRaw)}" alt="${nom} ${prenom}" class="client-thumb">`
    : `<div class="client-initials">${escapeHtml(getInitials(`${nom} ${prenom}`))}</div>`;

  const createdRaw = c.created_at ?? null;
  const createdHtml = createdRaw ? `<small class="text-muted">${escapeHtml(formatDate(createdRaw))}</small>` : '<small class="text-muted">—</small>';

  return `
  <tr data-id="${id}">
    <td class="ps-4">${id}</td>           <!-- 1. ID -->
    <td>${nom}</td>                        <!-- 2. Nom -->
    <td>${prenom}</td>                     <!-- 3. Prénom -->
    <td>${email}</td>                       <!-- 4. Email -->
    <td>${telephone}</td>                   <!-- 5. Téléphone -->
    <td class="text-center">${imageHtml}</td> <!-- 6. Image -->
    <td class="text-center">${createdHtml}</td> <!-- 7. Créé le -->
    <td class="text-end pe-4">
      <button class="btn btn-sm btn-outline-primary me-2" data-action="edit" data-id="${id}">
        <i class="bi bi-pencil"></i>
      </button>
      <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${id}">
        <i class="bi bi-trash"></i>
      </button>
    </td> <!-- 8. Actions -->
  </tr>
  `;
}

  // Format date
  function formatDate(v) {
    try {
      const d = new Date(v);
      if (isNaN(d.getTime())) return String(v);
      const pad = n => String(n).padStart(2, '0');
      return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    } catch (e) {
      return String(v);
    }
  }

  // Delegation
  function delegateActions(e) {
    const editBtn = e.target.closest('[data-action="edit"]');
    const delBtn = e.target.closest('[data-action="delete"]');
    if (editBtn) openClientModal(editBtn.dataset.id);
    if (delBtn) deleteClient(delBtn.dataset.id);
  }

  // Modal open
  async function openClientModal(id = null) {
    if (!clientForm) return;
    resetForm();
    if (id) {
      try {
        const json = await apiFetch(`${API.get}?id=${encodeURIComponent(id)}`);
        populateForm(json.data || {});
        document.getElementById('modalLabel').textContent = 'Modifier le client';
      } catch (err) {
        showToast(err.message || 'Impossible de charger le client', 'danger');
        return;
      }
    } else {
      document.getElementById('modalLabel').textContent = 'Nouveau client';
    }
    bsModal?.show();
  }

  function resetForm() {
    if (!clientForm) return;
    clientForm.reset();
    const idField = clientForm.querySelector('[name="id"]');
    if (idField) idField.value = '';
    const createdField = clientForm.querySelector('[name="created_at"]');
    if (createdField) createdField.value = '';
    const avatar = document.getElementById('clientAvatarPreview');
    if (avatar) avatar.innerHTML = '<i class="bi bi-person" aria-hidden="true"></i>';
  }

  function populateForm(data = {}) {
    if (!clientForm) return;
    setInputValue('id', data.id);
    setInputValue('nom', data.nom);
    setInputValue('prenom', data.prenom);
    setInputValue('email', data.email);
    setInputValue('telephone', data.telephone);
    setInputValue('image', data.image);
    setInputValue('created_at', data.created_at);
    setInputValue('nbr_vehicules', data.nbr_vehicules ?? data.vehicles_count ?? 0);
    setInputValue('matricules_historique', data.matricules_historique ?? '');

    const avatar = document.getElementById('clientAvatarPreview');
    if (avatar) {
      if (data.image) avatar.innerHTML = `<img src="${escapeHtml(data.image)}" alt="${escapeHtml(data.nom)}" class="client-thumb">`;
      else avatar.innerHTML = `<div class="client-initials">${escapeHtml(getInitials(`${data.nom} ${data.prenom}`))}</div>`;
    }
  }

  function setInputValue(name, value) {
    const el = clientForm.querySelector(`[name="${name}"]`);
    if (!el) return;
    el.value = value ?? '';
  }

  // Save client
  async function saveClient(e) {
    e.preventDefault();
    if (!clientForm) return;
    const formData = new FormData(clientForm);
    const payload = Object.fromEntries(formData.entries());
    const id = payload.id || null;

    if (!payload.nom || !payload.prenom) { showToast('Nom et prénom requis', 'warning'); return; }

    try {
      const url = id ? API.update : API.create;
      const res = await apiFetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      showToast(res.message || 'Enregistré', 'success');
      bsModal?.hide();
      loadClients();
    } catch (err) {
      showToast(err.message || 'Erreur lors de l\'enregistrement', 'danger');
    }
  }

  // Delete client
  async function deleteClient(id) {
    if (!id) return;
    if (!confirm('Confirmer la suppression de ce client ?')) return;
    try {
      const res = await apiFetch(API.delete, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      showToast(res.message || 'Supprimé', 'success');
      loadClients();
    } catch (err) {
      showToast(err.message || 'Erreur suppression', 'danger');
    }
  }

  function exportClients() { window.open(`${API.export}?format=csv`, '_blank'); }

  function showToast(message, type = 'info') {
    if (!toast) return alert(message);
    toast.hidden = false;
    toast.className = `clients-toast clients-toast-${type}`;
    toast.textContent = message;
    setTimeout(() => { toast.hidden = true; }, 3500);
  }

  function escapeHtml(str = '') {
    return String(str).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  }

  function getInitials(name = '') {
    return String(name).split(' ').filter(Boolean).slice(0,2).map(n => n[0]?.toUpperCase() || '').join('');
  }

  window.filterTable = function(tableId, inputId) {
    if (!tableBody) return;
    const q = document.getElementById(inputId).value.toLowerCase().trim();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  };

})();

function normalizeImage(val) {
  if (!val || val === '0' || val === 'null') return null;
  return val.startsWith('http') ? val : `/parkclean/${val.replace(/^\/+/, '')}`;
}


// Toast helper - Utilise le système unifié ParkCleanAPI
function showToast(message, type = 'info', duration = 3500) {
  if (typeof ParkCleanAPI !== 'undefined' && ParkCleanAPI.showToast) {
    ParkCleanAPI.showToast(message, type, duration);
  } else {
    // Fallback vers l'élément toast existant
    const t = document.getElementById('clientsToast');
    if (!t) { alert(message); return; }
    t.className = `pc-toast show ${type}`;
    t.textContent = message;
    t.hidden = false;
    clearTimeout(t._hideTimer);
    t._hideTimer = setTimeout(() => {
      t.classList.remove('show');
      t.hidden = true;
    }, duration);
  }
}

// Avatar helpers
(function() {
  const btnChange = document.getElementById('btnChangeAvatar');
  const btnRemove = document.getElementById('btnRemoveAvatar');
  const fileInput = document.getElementById('clientAvatarFile');
  const preview = document.getElementById('clientAvatarPreview');
  const imageUrlInput = document.querySelector('#clientForm [name="image"]');

  if (btnChange && fileInput && preview) {
    btnChange.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => {
      const f = e.target.files && e.target.files[0];
      if (!f) return;
      const reader = new FileReader();
      reader.onload = () => {
        preview.innerHTML = `<img src="${reader.result}" alt="Avatar">`;
        // set image input to data URL so backend can handle if desired
        if (imageUrlInput) imageUrlInput.value = reader.result;
      };
      reader.readAsDataURL(f);
    });
  }

  if (btnRemove && preview) {
    btnRemove.addEventListener('click', () => {
      preview.innerHTML = '<i class="bi bi-person" aria-hidden="true"></i>';
      if (imageUrlInput) imageUrlInput.value = '';
      if (fileInput) fileInput.value = '';
    });
  }

  // Live preview when user pastes an image URL
  if (imageUrlInput && preview) {
    imageUrlInput.addEventListener('input', () => {
      const v = imageUrlInput.value.trim();
      if (!v) { preview.innerHTML = '<i class="bi bi-person" aria-hidden="true"></i>'; return; }
      // quick validation for URL
      try {
        const u = new URL(v);
        preview.innerHTML = `<img src="${u.href}" alt="Avatar">`;
      } catch (e) {
        // not a valid URL — keep existing preview
      }
    });
  }
})();
