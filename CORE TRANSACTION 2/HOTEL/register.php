<?php /* Register Page */ ?>
<?php require_once __DIR__ . '/auth.php'; auth_start_session(); $me = auth_user(); if (!$me || $me['role'] !== 'admin') { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Register</title>
</head>
<body>
  <div class="container">
    <section class="module" style="max-width:520px;margin:40px auto;">
      <h2><i class="fas fa-user-plus"></i> Register</h2>
      <form class="module-form" id="register-form">
        <div class="form-grid">
          <label>Name<input type="text" name="name" placeholder="Full name" required></label>
          <label>Email<input type="email" name="email" placeholder="you@example.com" required></label>
          <label>Password<input type="password" name="password" required></label>
          <input type="hidden" name="role" value="staff">
        </div>
        <button type="submit" class="button">Create Account</button>
      </form>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </section>
  </div>
  <script>
    document.getElementById('register-form').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(e.target).entries());
      payload.action = 'register';
      const r = await fetch('auth_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const j = await r.json();
      if(j.success){ location.href = 'index.php'; } else { alert(j.error||'Registration failed'); }
    });
  </script>
</body>
</html>



