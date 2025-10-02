<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>CRUD Realtime - Reservations</title>
  <style>
    .drawer {
      position: fixed; top: 0; right: -420px; width: 400px; height: 100vh; background: #1e2b38;
      box-shadow: -2px 0 16px rgba(0,0,0,0.3); transition: right .25s ease; z-index: 1200; padding: 18px;
    }
    .drawer.open { right: 0; }
    .drawer h3 { margin-top: 10px; color: #fbc777; }
    .backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: none; z-index: 1100; }
    .backdrop.show { display: block; }
    .table-actions { display: flex; gap: 8px; }
    .danger { background: #b71c1c !important; }
    .success { background: #2e7d32 !important; }
  </style>
  </head>
<body>
  <nav class="navbar">
    <div class="site-name">Reservations CRUD (Realtime)</div>
    <button class="nav-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
    <ul class="nav-list">
      <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li><a class="active" href="crud_realtime_page.php"><i class="fas fa-database"></i> CRUD Realtime</a></li>
      <li><a href="front_office_page.php"><i class="fas fa-concierge-bell"></i> Front Office</a></li>
    </ul>
    <div id="auth-area" class="auth-area"></div>
  </nav>

  <div class="container">
    <section class="module">
      <h2><i class="fas fa-plus-circle"></i> Create Reservation</h2>
      <form id="create-form" class="module-form" data-endpoint="front_office.php" data-action="createReservation">
        <div class="form-grid">
          <label>Guest<input type="text" name="guest" placeholder="Guest name" required></label>
          <label>Room<input type="text" name="room" placeholder="RM-101" required></label>
          <label>Check-in<input type="date" name="checkIn" required></label>
          <label>Check-out<input type="date" name="checkOut" required></label>
          <label>Source
            <select name="source">
              <option value="Online">Online</option>
              <option value="Walk-in">Walk-in</option>
            </select>
          </label>
          <label>Remarks<input type="text" name="remarks" placeholder="Notes..."></label>
        </div>
        <button type="submit" class="button"><i class="fas fa-paper-plane"></i> Create</button>
      </form>
    </section>

    <section class="module">
      <h2><i class="fas fa-list"></i> Reservations</h2>
      <div class="transaction-table-container">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>No.</th>
              <th>Guest</th>
              <th>Room</th>
              <th>Dates</th>
              <th>Status</th>
              <th>Source</th>
              <th>Remarks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="reservations-tbody"></tbody>
        </table>
      </div>
    </section>
  </div>

  <div class="backdrop" id="backdrop"></div>
  <aside class="drawer" id="editor">
    <h3><i class="fas fa-pen"></i> Edit Reservation</h3>
    <form id="edit-form" class="module-form" data-endpoint="front_office.php" data-action="updateReservation">
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
        <button type="button" id="close-editor" class="button danger"><i class="fas fa-times"></i> Close</button>
      </div>
    </form>
  </aside>

  <script>
  document.addEventListener('DOMContentLoaded', function(){
    const navToggle = document.querySelector('.nav-toggle');
    const navList = document.querySelector('.nav-list');
    if (navToggle && navList) { navToggle.addEventListener('click',()=>{navList.classList.toggle('nav-list-open'); navToggle.classList.toggle('open');}); }

    async function refreshAuth(){
      try{
        const r = await fetch('auth_api.php?action=me');
        const j = await r.json();
        const el = document.getElementById('auth-area');
        if(!el) return;
        if(j && j.user){ el.innerHTML = `<span>Hi, ${j.user.name} (${j.user.role})</span> <a class="button" href="#" id="logout-btn">Logout</a>`; }
        else { el.innerHTML = `<a class="button" href="login.php">Login</a>`; }
      } catch(e) {}
    }
    refreshAuth();
    document.addEventListener('click', async (e)=>{
      if(e.target && e.target.id==='logout-btn'){
        e.preventDefault();
        await fetch('auth_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'logout'})});
        location.reload();
      }
    });

    const rtbody = document.getElementById('reservations-tbody');
    const backdrop = document.getElementById('backdrop');
    const editor = document.getElementById('editor');
    const editForm = document.getElementById('edit-form');
    const createForm = document.getElementById('create-form');

    function openEditor(row){
      editForm.reservationNo.value = row.reservation_no;
      editForm.guest.value = row.guest || '';
      editForm.room.value = row.room || '';
      editForm.checkIn.value = row.check_in || '';
      editForm.checkOut.value = row.check_out || '';
      editForm.status.value = row.status || 'Booked';
      editForm.source.value = row.source || 'Online';
      editForm.remarks.value = row.remarks || '';
      editor.classList.add('open');
      backdrop.classList.add('show');
    }
    function closeEditor(){ editor.classList.remove('open'); backdrop.classList.remove('show'); }
    document.getElementById('close-editor').addEventListener('click', closeEditor);
    backdrop.addEventListener('click', closeEditor);

    function renderRow(row){
      const tr = document.createElement('tr');
      const dates = `${row.check_in} → ${row.check_out}`;
      tr.innerHTML = `
        <td data-label="No.">${row.reservation_no}</td>
        <td data-label="Guest">${row.guest}</td>
        <td data-label="Room">${row.room}</td>
        <td data-label="Dates">${dates}</td>
        <td data-label="Status">${row.status}</td>
        <td data-label="Source">${row.source}</td>
        <td data-label="Remarks">${row.remarks || ''}</td>
        <td data-label="Actions">
          <div class="table-actions">
            <button class="button" data-edit="${row.reservation_no}"><i class="fas fa-pen"></i> Edit</button>
            <button class="button success" data-checkin="${row.reservation_no}"><i class="fas fa-door-open"></i> Check-in</button>
            <button class="button" data-checkout="${row.reservation_no}"><i class="fas fa-door-closed"></i> Check-out</button>
            <button class="button danger" data-delete="${row.reservation_no}"><i class="fas fa-trash"></i> Delete</button>
          </div>
        </td>`;
      return tr;
    }

    async function loadReservations(){
      try{
        const me = await (await fetch('auth_api.php?action=me')).json();
        if(!me.user || (me.user.role!=='admin' && me.user.role!=='staff')){
          rtbody.innerHTML = `<tr><td colspan="8">Login as staff/admin to view reservations list.</td></tr>`;
          return;
        }
      }catch(e){}
      const res = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'listReservations', limit:100})});
      const j = await res.json();
      if(!j.success || !j.data || !Array.isArray(j.data.rows)) return;
      rtbody.innerHTML = '';
      j.data.rows.forEach(row=>{ rtbody.appendChild(renderRow(row)); });
    }

    async function submitForm(form){
      const endpoint = form.getAttribute('data-endpoint');
      const action = form.getAttribute('data-action');
      const payload = Object.fromEntries(new FormData(form).entries());
      payload.action = action;
      const r = await fetch(endpoint, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const j = await r.json();
      try{
        const bc = new BroadcastChannel('tx-updates');
        const ref = (j.data && (j.data.reservationNo || j.data.invoiceNo)) || (j.action || 'Action');
        const amount = (j.data && j.data.amount != null) ? j.data.amount : null;
        const status = (j.data && (j.data.status || 'Booked')) || 'Booked';
        bc.postMessage({ module: 'Front Office', ref, amount, status, date: new Date().toISOString() });
      }catch(e){}
      try{ const bc2 = new BroadcastChannel('reservations-updates'); bc2.postMessage({action: action||payload.action}); }catch(e){}
      await loadReservations();
      alert('Success: ' + (j.action || action));
    }

    createForm.addEventListener('submit', function(e){ e.preventDefault(); submitForm(createForm); });
    editForm.addEventListener('submit', function(e){ e.preventDefault(); submitForm(editForm).then(()=>closeEditor()); });

    document.addEventListener('click', async (e)=>{
      const btn = e.target.closest('button');
      if(!btn) return;
      const editNo = btn.getAttribute('data-edit');
      const delNo = btn.getAttribute('data-delete');
      const inNo = btn.getAttribute('data-checkin');
      const outNo = btn.getAttribute('data-checkout');
      if(editNo){
        const rowEl = btn.closest('tr');
        if(!rowEl){ return; }
        const cells = Array.from(rowEl.querySelectorAll('td'));
        const datesText = (cells[3] && cells[3].textContent) ? cells[3].textContent : '';
        const parts = datesText.split('→');
        const row = {
          reservation_no: cells[0] ? cells[0].textContent.trim() : editNo,
          guest: cells[1] ? cells[1].textContent.trim() : '',
          room: cells[2] ? cells[2].textContent.trim() : '',
          check_in: parts[0] ? parts[0].trim() : '',
          check_out: parts[1] ? parts[1].trim() : '',
          status: cells[4] ? cells[4].textContent.trim() : 'Booked',
          source: cells[5] ? cells[5].textContent.trim() : 'Online',
          remarks: cells[6] ? cells[6].textContent.trim() : ''
        };
        openEditor(row);
      }
      if(delNo){
        if(!confirm('Delete reservation ' + delNo + '?')) return;
        const r = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'deleteReservation', reservationNo: delNo})});
        const j = await r.json();
        if(j.success){ try{ const bc2=new BroadcastChannel('reservations-updates'); bc2.postMessage({action:'deleteReservation'});}catch(e){} await loadReservations(); }
      }
      if(inNo){
        const r = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'checkInGuest', reservationNo: inNo})});
        const j = await r.json();
        if(j.success){ try{ const bc2=new BroadcastChannel('reservations-updates'); bc2.postMessage({action:'checkInGuest'});}catch(e){} await loadReservations(); }
      }
      if(outNo){
        const r = await fetch('front_office.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'checkOutGuest', reservationNo: outNo})});
        const j = await r.json();
        if(j.success){ try{ const bc2=new BroadcastChannel('reservations-updates'); bc2.postMessage({action:'checkOutGuest'});}catch(e){} await loadReservations(); }
      }
    });

    let bcRes = null;
    try { bcRes = new BroadcastChannel('reservations-updates'); } catch(e) { bcRes = null; }
    if (bcRes) { bcRes.onmessage = () => { loadReservations(); }; }

    let lastReload = 0;
    async function poll(){
      const now = Date.now();
      if(now - lastReload > 4500){ lastReload = now; await loadReservations(); }
    }
    setInterval(poll, 5000);

    // Warn if opened as file:// which breaks fetch/auth
    try {
      if(location.protocol === 'file:'){
        const warn = document.createElement('div');
        warn.style.cssText = 'position:fixed;top:60px;left:0;right:0;background:#b71c1c;color:#fff;padding:10px;text-align:center;z-index:1300;';
        warn.textContent = 'Open via http://localhost/CORE TRANSACTION 2/HOTEL/crud_realtime_page.php (not file://) to enable API & realtime.';
        document.body.appendChild(warn);
      }
    } catch(e) {}

    loadReservations();
  });
  </script>
  </body>
  </html>


