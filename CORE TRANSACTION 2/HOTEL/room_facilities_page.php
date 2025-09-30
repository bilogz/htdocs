<?php require_once __DIR__ . '/auth.php'; auth_start_session(); if (!auth_user()) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Room Facilities</title>
</head>
<body>
  <nav class="navbar">
    <div class="site-name">Hotel Core Transaction 2</div>
    <button class="nav-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
    <ul class="nav-list">
      <li><a href="front_office_page.php"><i class="fas fa-concierge-bell"></i> Front Office</a></li>
      <li><a href="billing_page.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a></li>
      <li><a class="active" href="room_facilities_page.php"><i class="fas fa-bed"></i> Room Facilities</a></li>
      <li><a href="supplier_management_page.php"><i class="fas fa-truck"></i> Supplier Management</a></li>
      <li><a href="housekeeping_page.php"><i class="fas a-broom"></i> Housekeeping</a></li>
      <li><a href="laundry_page.php"><i class="fas fa-tshirt"></i> Laundry</a></li>
      <li><a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
    </ul>
    <div id="auth-area" class="auth-area"></div>
  </nav>
  <div class="container">
    <section class="module">
      <h2><i class="fas fa-bed"></i> Block Room</h2>
      <form class="module-form" data-endpoint="room_facilities.php" data-action="blockRoom">
        <div class="form-grid">
          <label>Room<input type="text" name="room" placeholder="RM-205"></label>
          <label>Reason<input type="text" name="reason" placeholder="Maintenance"></label>
        </div>
        <button type="submit" class="button">Block</button>
      </form>
    </section>
    <section class="module">
      <h2>Create Maintenance Ticket</h2>
      <form class="module-form" data-endpoint="room_facilities.php" data-action="createMaintenance">
        <div class="form-grid">
          <label>Room<input type="text" name="room" placeholder="RM-205"></label>
          <label>Cost<input type="number" step="0.01" name="cost" placeholder="1200"></label>
        </div>
        <button type="submit" class="button">Create</button>
      </form>
    </section>
    <section class="module">
      <h2>Recent Room Facilities Actions</h2>
      <div class="transaction-table-container">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Date</th><th>Module</th><th>Reference</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody id="rf-tbody"></tbody>
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
  const tbody=document.getElementById('rf-tbody');
  function addRow({date,module,ref,amount,status}){
    const tr=document.createElement('tr');
    tr.innerHTML=`<td data-label="Date">${date}</td><td data-label="Module">${module}</td><td data-label="Reference">${ref}</td><td data-label="Amount">${amount}</td><td data-label="Status">${status}</td>`;
    tbody.prepend(tr);
  }
  async function submitModuleForm(form){
    const payload = Object.fromEntries(new FormData(form).entries());
    payload.action = form.getAttribute('data-action');
    const r = await fetch(form.getAttribute('data-endpoint'), {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const j = await r.json();
    const date=new Date().toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'});
    const module='Room Facilities';
    const ref=(j.data&&(j.data.ticket||('Block '+(j.data.room||''))))||(j.action||'Action');
    const amt=j.data&&j.data.cost!=null?('₱'+Number(j.data.cost).toFixed(2)):'—';
    const status=j.action==='createMaintenance'?'Created':'Blocked';
    addRow({date,module,ref,amount:amt,status});
    try{const bc=new BroadcastChannel('tx-updates'); bc.postMessage({module,ref,amount:j.data?j.data.cost:null,status,date:new Date().toISOString()});}catch(e){}
    alert('Success: ' + (j.action||'OK'));
  }
  document.querySelectorAll('.module-form').forEach(f=>f.addEventListener('submit',e=>{e.preventDefault(); submitModuleForm(f);}));
  </script>
</body>
</html>


