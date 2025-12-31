<section class="card" style="max-width:520px;margin:30px auto">
  <h1>Mot de passe oublié</h1>
  <p class="muted">Entrez votre nom d'utilisateur pour recevoir un code de réinitialisation (mode dev affiche le code).</p>
  <div id="forgotMsg" class="muted"></div>
  <form id="forgotForm">
    <label class="small muted">Nom d'utilisateur</label>
    <input id="forgotUsername" class="form-control" required>
    <div style="margin-top:12px">
      <button class="btn-primary" type="submit">Demander le code</button>
    </div>
  </form>

  <script>
  document.getElementById('forgotForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const user = document.getElementById('forgotUsername').value;
    const res = await fetch('/api/auth/request_reset.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({username:user})});
    const j = await res.json();
    document.getElementById('forgotMsg').innerText = j.message || JSON.stringify(j);
  });
  </script>
</section>
