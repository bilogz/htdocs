<?php /* Front Office Page */ ?>
<?php require_once __DIR__ . '/auth.php'; auth_start_session(); if (!auth_user()) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Front Office</title>
  <style>
    .drawer { position: fixed; top: 0; right: -420px; width: 400px; height: 100vh; background: #1e2b38; box-shadow: -2px 0 16px rgba(0,0,0,0.3); transition: right .25s ease; z-index: 1200; padding: 18px; }
    .drawer.open { right: 0; }
    .drawer h3 { margin-top: 10px; color: #fbc777; }
    .backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: none; z-index: 1100; }
    .backdrop.show { display: block; }
    .table-actions { display: flex; gap: 8px; }
    .danger { background: #b71c1c !important; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="site-name">Hotel Core Transaction 2</div>
    <button class="nav-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
    <ul class="nav-list">
      <li><a class="active" href="front_office_page.php"><i class="fas fa-concierge-bell"></i> Front Office</a></li>
      <li><a href="billing_page.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a></li>
      <li><a href="room_facilities_page.php"><i class="fas fa-bed"></i> Room Facilities</a></li>
      <li><a href="supplier_management_page.php"><i class="fas fa-truck"></i> Supplier Management</a></li>
      <li><a href="housekeeping_page.php"><i class="fas fa-broom"></i> Housekeeping</a></li>
      <li><a href="laundry_page.php"><i class="fas fa-tshirt"></i> Laundry</a></li>
      <li><a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
    </ul>
    <div id="auth-area" class="auth-area"></div>
  </nav>
  <div class="container">
    <section class="module">
      <h2><i class="fas fa-concierge-bell"></i> Create Reservation</h2>
      <form class="module-form" id="fo-create" data-endpoint="front_office.php" data-action="createReservation">
        <div class="form-grid">
          <label>Guest<input type="text" name="guest" placeholder="Guest name" required></label>
          <label>Room<input type="text" name="room" placeholder="RM-101" required></label>
          <label>Check-in<input type="date" name="checkIn" required></label>
          <label>Check-out<input type="date" name="checkOut" required></label>
          <label>Source
            <select name="source">
              <option value="Online">Online</option>
              <option value="Walk-in">Walk-in (Admin)</option>
            </select>
          </label>
          <label>Remarks<textarea name="remarks" placeholder="Notes..." rows="2"></textarea></label>
        </div>
        <button type="submit" class="button">Create Reservation</button>
      </form>
    </section>

    <section class="module">
      <h2>Reservations</h2>
      <div class="transaction-table-container">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>No.</th><th>Guest</th><th>Room</th><th>Dates</th><th>Status</th><th>Source</th><th>Remarks</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="reservations-tbody"></tbody>
        </table>
      </div>
    </section>

    <section class="module">
      <h2>Check-in / Check-out</h2>
      <form class="module-form inline" id="fo-in" data-endpoint="front_office.php" data-action="checkInGuest">
        <div class="form-grid">
          <label>Reservation #<input type="text" name="reservationNo" placeholder="RF-1234"></label>
        </div>
        <button type="submit" class="button">Check-in</button>
      </form>
      <form class="module-form inline" id="fo-out" data-endpoint="front_office.php" data-action="checkOutGuest">
        <div class="form-grid">
          <label>Reservation #<input type="text" name="reservationNo" placeholder="RF-1234"></label>
          <label>Amount<input type="number" step="0.01" name="amount" placeholder="2500"></label>
        </div>
        <button type="submit" class="button">Check-out</button>
      </form>
    </section>

    <section class="module">
      <h2>Recent Front Office Actions</h2>
      <div class="transaction-table-container">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Date</th><th>Module</th><th>Reference</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody id="fo-tbody"></tbody>
        </table>
      </div>
    </section>
  </div>
  <div class="backdrop" id="fo-backdrop"></div>
  <aside class="drawer" id="fo-editor">
    <h3><i class="fas fa-pen"></i> Edit Reservation</h3>
    <form id="fo-edit-form" class="module-form" data-endpoint="front_office.php" data-action="updateReservation">
      <input type="hidden" name="reservationNo">
      <div class="form-grid" style="grid-template-columns: 1fr;">
        <label>Guest<input type="text" name="guest"></label>
        <label>Room<input type="text" name="room"></label>
        <label>Check-in<input type="date" name="checkIn"></label>
        <label>Check-out<input type="date" name="checkOut"></label>
        <label>Status
          <select name="status">
            <option>Booked</option>
            <option>Checked-in</option>
            <option>Checked-out</option>
            <option>Cancelled</option>
          </select>
        </label>
        <label>Source
          <select name="source">
            <option>Online</option>
            <option>Walk-in</option>
          </select>
        </label>
        <label>Remarks<input type="text" name="remarks"></label>
      </div>
      <div style="display:flex; gap:8px; margin-top:12px;">
        <button type="submit" class="button"><i class="fas fa-save"></i> Save</button>
        <button type="button" id="fo-close-editor" class="button danger"><i class="fas fa-times"></i> Close</button>
      </div>
    </form>
  </aside>
  <script>
    document.addEventListener('DOMContentLoaded',function(){
      const t=document.querySelector('.nav-toggle');
      const l=document.querySelector('.nav-list');
      if(t&&l){t.addEventListener('click',()=>{l.classList.toggle('nav-list-open');t.classList.toggle('open');});}
    });
  </script>
  <script>
  async function refreshAuth(){
    try{
      const r = await fetch('auth_api.php?action=me');
      const j = await r.json();
      const el = document.getElementById('auth-area');
      if(j && j.user){
        el.innerHTML = `<span>Hi, ${j.user.name} (${j.user.role})</span> <a class="button" href="#" id="logout-btn">Logout</a>`;
        const srcSel = document.querySelector('select[name="source"]');
        if(srcSel){ srcSel.value = 'Walk-in'; }
      } else {
        el.innerHTML = `<a class="button" href="login.php">Login</a>`;
        // redirect anonymous users to login
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
  const tbody = document.getElementById('fo-tbody');
  const rtbody = document.getElementById('reservations-tbody');
  function addRow({date,module,ref,amount,status}){
    if(!tbody) return; const tr=document.createElement('tr');
    tr.innerHTML = `<td data-label="Date">${date}</td><td data-label="Module">${module}</td><td data-label="Reference">${ref}</td><td data-label="Amount">${amount}</td><td data-label="Status">${status}</td>`;
    tbody.prepend(tr);
  }
  async function submitModuleForm(form) {
    const endpoint = form.getAttribute('data-endpoint');
    const action = form.getAttribute('data-action');
    const payload = Object.fromEntries(new FormData(form).entries());
    payload.action = action;
    const res = await fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    try {
      const bc = new BroadcastChannel('tx-updates');
      const ref = (json.data && (json.data.reservationNo || json.data.invoiceNo)) || (json.action || 'Action');
      const amount = (json.data && json.data.amount != null) ? json.data.amount : null;
      const status = (json.data && (json.data.status || 'Booked')) || 'Booked';
      bc.postMessage({ module: 'Front Office', ref, amount, status, date: new Date().toISOString() });
    } catch(e) {}
    // add to local table as well
    const date=new Date().toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'});
    const module='Front Office';
    const refLocal = (json.data && (json.data.reservationNo || json.data.invoiceNo)) || (json.action || 'Action');
    const amtLocal = (json.data && json.data.amount != null) ? ('₱'+Number(json.data.amount).toFixed(2)) : '—';
    const statusLocal = (json.data && (json.data.status || 'Booked')) || 'Booked';
    addRow({date:date,module:module,ref:refLocal,amount:amtLocal,status:statusLocal});
    // reload reservations and activities after success for persistence visibility
    await loadReservations();
    await loadActivities();
    alert('Success: ' + (json.action || action));
  }
  document.querySelectorAll('.module-form').forEach(form => {
    form.addEventListener('submit', e => { e.preventDefault(); submitModuleForm(form); });
  });

  async function loadReservations(){
    // Require staff/admin to view full list
    try{
      const me = await (await fetch('auth_api.php?action=me')).json();
      if(!me.user || (me.user.role!=='admin' && me.user.role!=='staff')){
        rtbody.innerHTML = `<tr><td colspan="8">Login as staff/admin to view reservations list.</td></tr>`;
        return;
      }
    }catch(e){}
    const res = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'listReservations', limit:100})});
    const j = await res.json();
    if(!j.success||!j.data||!Array.isArray(j.data.rows)) return;
    rtbody.innerHTML = '';
    j.data.rows.forEach(row=>{
      const tr=document.createElement('tr');
      const dates = `${row.check_in} → ${row.check_out}`;
      tr.innerHTML = `
        <td data-label="No.">${row.reservation_no}</td>
        <td data-label="Guest">${row.guest}</td>
        <td data-label="Room">${row.room}</td>
        <td data-label="Dates">${dates}</td>
        <td data-label="Status">${row.status}</td>
        <td data-label="Source">${row.source}</td>
        <td data-label="Remarks">${row.remarks||''}</td>
        <td data-label="Actions">
          <div class="table-actions">
            <button class="button" data-edit="${row.reservation_no}"><i class="fas fa-pen"></i> Edit</button>
            <button class="button" data-checkin="${row.reservation_no}"><i class="fas fa-door-open"></i> Check-in</button>
            <button class="button" data-checkout="${row.reservation_no}"><i class="fas fa-door-closed"></i> Check-out</button>
            <button class="button danger" data-delete="${row.reservation_no}"><i class="fas fa-trash"></i> Delete</button>
          </div>
        </td>`;
      rtbody.appendChild(tr);
    });
  }

  async function loadActivities(){
    try{
      const r = await fetch('activities.php?action=list&module=Front%20Office&limit=10');
      const j = await r.json();
      if(!j.success||!j.data||!Array.isArray(j.data.rows)) return;
      tbody.innerHTML = '';
      j.data.rows.forEach(row=>{
        const date = new Date(row.ts).toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'});
        const amt = (row.meta && row.meta.amount!=null) ? ('₱'+Number(row.meta.amount).toFixed(2)) : '—';
        const st = row.action==='checkOutGuest'?'Checked-out': (row.action==='checkInGuest'?'Checked-in': (row.action==='createReservation'?'Booked': row.action));
        addRow({date,module:'Front Office',ref:row.reference||row.action,amount:amt,status:st});
      });
    }catch(e){}
  }

  const foBackdrop = document.getElementById('fo-backdrop');
  const foEditor = document.getElementById('fo-editor');
  const foEditForm = document.getElementById('fo-edit-form');
  function openFoEditor(row){
    foEditForm.reservationNo.value = row.reservation_no;
    foEditForm.guest.value = row.guest || '';
    foEditForm.room.value = row.room || '';
    foEditForm.checkIn.value = row.check_in || '';
    foEditForm.checkOut.value = row.check_out || '';
    foEditForm.status.value = row.status || 'Booked';
    foEditForm.source.value = row.source || 'Online';
    foEditForm.remarks.value = row.remarks || '';
    foEditor.classList.add('open');
    foBackdrop.classList.add('show');
  }
  function closeFoEditor(){ foEditor.classList.remove('open'); foBackdrop.classList.remove('show'); }
  document.getElementById('fo-close-editor').addEventListener('click', closeFoEditor);
  foBackdrop.addEventListener('click', closeFoEditor);
  foEditForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(foEditForm).entries());
    payload.action = 'updateReservation';
    const r = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const j = await r.json();
    if(j.success){ closeFoEditor(); await loadReservations(); }
  });

  async function handleDelete(reservationNo){
    if(!confirm('Delete reservation '+reservationNo+'?')) return;
    const r = await fetch('front_office.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'deleteReservation',reservationNo})});
    const j = await r.json();
    if(j.success){ await loadReservations(); alert('Deleted'); }
  }

  rtbody.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button');
    if(!btn) return;
    const ed = btn.getAttribute('data-edit');
    const del = btn.getAttribute('data-delete');
    const ckIn = btn.getAttribute('data-checkin');
    const ckOut = btn.getAttribute('data-checkout');
    if(ed){
      const rowEl = btn.closest('tr');
      if(!rowEl) return;
      const cells = Array.from(rowEl.querySelectorAll('td'));
      const datesText = (cells[3] && cells[3].textContent) ? cells[3].textContent : '';
      const parts = datesText.split('→');
      const row = {
        reservation_no: cells[0] ? cells[0].textContent.trim() : ed,
        guest: cells[1] ? cells[1].textContent.trim() : '',
        room: cells[2] ? cells[2].textContent.trim() : '',
        check_in: parts[0] ? parts[0].trim() : '',
        check_out: parts[1] ? parts[1].trim() : '',
        status: cells[4] ? cells[4].textContent.trim() : 'Booked',
        source: cells[5] ? cells[5].textContent.trim() : 'Online',
        remarks: cells[6] ? cells[6].textContent.trim() : ''
      };
      openFoEditor(row);
    }
    if(del){ handleDelete(del); }
    if(ckIn){ const r = await fetch('front_office.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'checkInGuest',reservationNo:ckIn})}); const j = await r.json(); if(j.success){ await loadReservations(); } }
    if(ckOut){ const r = await fetch('front_office.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'checkOutGuest',reservationNo:ckOut})}); const j = await r.json(); if(j.success){ await loadReservations(); } }
  });

  loadReservations();
  loadActivities();
  </script>
</body>
</html>


