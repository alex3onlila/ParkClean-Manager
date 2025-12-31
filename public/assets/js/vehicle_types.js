async function loadTypes() {
  const res = await fetch('/api/vehicle_types/list.php');
  const json = await res.json();
  const select = document.getElementById('typeSelect');
  if (json.success) {
    select.innerHTML = json.items.map(t =>
      `<option value="${t.id}">${t.type}</option>`
    ).join('');
  } else {
    select.innerHTML = '<option value="">Erreur chargement types</option>';
  }
}
