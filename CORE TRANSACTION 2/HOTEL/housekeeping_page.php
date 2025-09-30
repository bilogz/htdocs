<?php require_once __DIR__ . '/auth.php'; auth_start_session(); if (!auth_user()) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Housekeeping</title>
</head>
<body>
  <nav class="navbar">
    <div class="site-name">Hotel Core Transaction 2</div>
    <button class="nav-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
    <ul class="nav-list">
      <li><a href="front_office_page.php"><i class="fas fa-concierge-bell"></i> Front Office</a></li>
      <li><a href="billing_page.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a></li>
      <li><a href="room_facilities_page.php"><i class="fas fa-bed"></i> Room Facilities</a></li>
      <li><a href="supplier_management_page.php"><i class="fas fa-truck"></i> Supplier Management</a></li>
      <li><a class="active" href="housekeeping_page.php"><i class="fas fa-broom"></i> Housekeeping</a></li>
      <li><a href="laundry_page.php"><i class="fas fa-tshirt"></i> Laundry</a></li>
      <li><a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
    </ul>
    <div id="auth-area" class="auth-area"></div>
  </nav>
  <div class="container">
    <section class="module">
      <h2><i class="fas fa-broom"></i> Assign Cleaning</h2>
      <form class="module-form" data-endpoint="housekeeping.php" data-action="assignCleaning">
        <div class="form-grid">
          <label>Room<input type="text" name="room" placeholder="RM-302"></label>
          <label>Staff<input type="text" name="staff" placeholder="Housekeeper"></label>
        </div>
        <button type="submit" class="button">Assign</button>
      </form>
    </section>
    <section class="module">
      <h2>Log Inspection</h2>
      <form class="module-form" data-endpoint="housekeeping.php" data-action="logInspection">
        <div class="form-grid">
          <label>Room<input type="text" name="room" placeholder="RM-302"></label>
          <label>Score<input type="number" name="score" min="0" max="100" placeholder="95"></label>
        </div>
        <button type="submit" class="button">Log</button>
      </form>
    </section>
    <section class="module">
      <h2>Recent Housekeeping Actions</h2>
      <div class="transaction-table-container">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Date</th><th>Module</th><th>Reference</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody id="hk-tbody"></tbody>
        </table>
      </div>
    </section>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded',function(){
    const t=document.querySelector('.nav-toggle');
    const l=document.querySelector('.nav-list');
    if(t&&l){t.addEventListener('click',()=>{l.classList.toggle('nav-list-open');t.classList.toggle('open');});}
  });
  async function refreshAuth(){
    try{
      const r = await fetch('auth_api.php?action=me');
      const j = await r.json();
      const el = document.getElementById('auth-area');
      if(j && j.user){
        el.innerHTML = `<span>Hi, ${j.user.name} (${j.user.role})</span> <a class="button" href="#" id="logout-btn">Logout</a>`;
      } else {
        el.innerHTML = `<a class="button" href="login.php">Login</a>`;
        location.href = 'login.php';
      }
    }catch(e){}
  }
  refreshAuth();
  document.addEventListener('click', async (e)=>{
    if(e.target && e.target.id==='logout-btn'){
      e.preventDefault();
      await fetch('auth_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'logout'})});
      location.reload();
    }
  });
  const tbody=document.getElementById('hk-tbody');
  function addRow({date,module,ref,amount,status}){ const tr=document.createElement('tr'); tr.innerHTML=`<td data-label="Date">${date}</td><td data-label="Module">${module}</td><td data-label="Reference">${ref}</td><td data-label="Amount">${amount}</td><td data-label="Status">${status}</td>`; tbody.prepend(tr); }
  async function submitModuleForm(form){
    const payload = Object.fromEntries(new FormData(form).entries());
    payload.action = form.getAttribute('data-action');
    const r = await fetch(form.getAttribute('data-endpoint'), {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const j = await r.json();
    const date=new Date().toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'});
    const module='Housekeeping';
    const ref=(j.data&&(j.data.task||('HK-'+Math.floor(Math.random()*900+100))))||(j.action||'Action');
    const amt='—';
    const status=j.action==='logInspection'?'Logged':'Assigned';
    addRow({date,module,ref,amount:amt,status});
    try{const bc=new BroadcastChannel('tx-updates'); bc.postMessage({module,ref,amount:null,status,date:new Date().toISOString()});}catch(e){}
    await loadActivities();
    alert('Success: ' + (j.action||'OK'));
  }
  document.querySelectorAll('.module-form').forEach(f=>f.addEventListener('submit',e=>{e.preventDefault(); submitModuleForm(f);}));
  async function loadActivities(){
    try{
      const r = await fetch('activities.php?action=list&module=Housekeeping&limit=10');
      const j = await r.json();
      if(!j.success||!j.data||!Array.isArray(j.data.rows)) return;
      const tbody=document.getElementById('hk-tbody');
      tbody.innerHTML='';
      j.data.rows.forEach(row=>{
        const date = new Date(row.ts).toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'});
        const ref = row.reference || (row.action==='assignCleaning'?'Task':'Inspection');
        const st = row.action==='logInspection'?'Logged':'Assigned';
        const tr=document.createElement('tr');
        tr.innerHTML=`<td data-label="Date">${date}</td><td data-label="Module">Housekeeping</td><td data-label="Reference">${ref}</td><td data-label="Amount">—</td><td data-label="Status">${st}</td>`;
        tbody.prepend(tr);
      });
    }catch(e){}
  }
  loadActivities();
  </script>
</body>
</html>


