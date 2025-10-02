<?php /* Login Page */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Login</title>
</head>
<body>
  <div class="container">
    <section class="module" style="max-width:520px;margin:40px auto;">
      <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
      <form class="module-form" id="login-form">
        <div class="form-grid">
          <label>Email<input type="email" name="email" placeholder="you@example.com" required></label>
          <label>Password<input type="password" name="password" required></label>
        </div>
        <button type="submit" class="button">Login</button>
      </form>
      
    </section>
  </div>
  <script>
    document.getElementById('login-form').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(e.target).entries());
      payload.action = 'login';
      const r = await fetch('auth_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const j = await r.json();
      if(j.success){
        location.href = 'index.php';
      } else { alert(j.error||'Login failed'); }
    });
  </script>
</body>
</html>



