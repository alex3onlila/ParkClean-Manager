<section class="card" style="max-width:520px;margin:30px auto">
  <h1>Réinitialiser le mot de passe</h1>
  <div id="resetMsg" class="muted"></div>
  <form id="resetForm">
    <label class="small muted">Nom d'utilisateur</label>
    <input id="resetUsername" class="form-control" required>
    <label class="small muted" style="margin-top:8px">Code</label>
    <input id="resetToken" class="form-control" required>
    <label class="small muted" style="margin-top:8px">Nouveau mot de passe</label>
    <input id="resetPassword" type="password" class="form-control" required>
    <div style="margin-top:12px">
      <button class="btn-primary" type="submit">Réinitialiser</button>
    </div>
  </form>

  <script>
  document.getElementById('resetForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const data = {
      username: document.getElementById('resetUsername').value,
      token: document.getElementById('resetToken').value,
      new_password: document.getElementById('resetPassword').value
    };
    const res = await fetch('/api/auth/reset.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const j = await res.json();
    document.getElementById('resetMsg').innerText = j.message || JSON.stringify(j);
    if(j.success) setTimeout(()=>location.href='?page=login',1200);
  });
  </script>
</section>
