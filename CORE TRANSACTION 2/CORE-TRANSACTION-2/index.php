<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <title>Hotel Core Transaction 2</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
</head>
<body>

  <nav class="navbar">
    <div class="site-name">Hotel Core Transaction 2</div>
    <button class="nav-toggle" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
    <ul class="nav-list">
      <li><a href="crud_realtime_page.php"><i class="fas fa-database"></i> CRUD Realtime</a></li>
      <li><a href="front_office_page.php"><i class="fas fa-concierge-bell"></i> Front Office</a></li>
      <li><a href="billing_page.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a></li>
      <li><a href="room_facilities_page.php"><i class="fas fa-bed"></i> Room Facilities</a></li>
      <li><a href="supplier_management_page.php"><i class="fas fa-truck"></i> Supplier Management</a></li>
      <li><a href="housekeeping_page.php"><i class="fas fa-broom"></i> Housekeeping</a></li>
      <li><a href="laundry_page.php"><i class="fas fa-tshirt"></i> Laundry</a></li>
      
    </ul>
    <div id="auth-area" class="auth-area"></div>
  </nav>

  <div class="container">
  <section class="hero" id="home" data-view>
    <h1>Welcome to Hotel Core Transaction 2</h1>
    <p>Manage Front Office, Billing, Rooms, Suppliers, Housekeeping, and Laundry seamlessly.</p>
  </section>

  <section class="stats">
    <div class="stat-item">
      <div class="stat-number">92%</div>
      <div class="stat-label">Avg. Occupancy</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">128</div>
      <div class="stat-label">Active Reservations</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">24</div>
      <div class="stat-label">Rooms Under Cleaning</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">18</div>
      <div class="stat-label">Laundry Orders</div>
    </div>
  </section>

  <section class="dashboard">
    <h2>Hotel Operations Overview</h2>
    <div class="dashboard-main">
      <div class="cards">
        <div class="card">
          <h3>Front Office: Check-ins vs Check-outs</h3>
          <canvas id="budgetExpensesChart"></canvas>
        </div>
        <div class="card">
          <h3>Billing: Collections Trend</h3>
          <canvas id="collectionsTrendChart"></canvas>
        </div>
        <div class="card">
          <h3>Room Facilities: Usage Distribution</h3>
          <canvas id="expenseDistributionChart"></canvas>
        </div>
        <div class="card">
          <h3>Housekeeping & Laundry: Workload</h3>
          <canvas id="cashFlowChart"></canvas>
        </div>
      </div>
      <aside class="sidebar">
        <h3>Module Status</h3>
        <ul>
          <li><i class="fas fa-concierge-bell"></i> Front Office: <span class="status-value">Online</span></li>
          <li><i class="fas fa-file-invoice-dollar"></i> Billing: <span class="status-value">OK</span></li>
          <li><i class="fas fa-bed"></i> Room Facilities: <span class="status-value">Stable</span></li>
          <li><i class="fas fa-broom"></i> Housekeeping: <span class="status-value">In Progress</span></li>
          <li><i class="fas fa-tshirt"></i> Laundry: <span class="status-value">Queued</span></li>
          <li><i class="fas fa-truck"></i> Suppliers: <span class="status-pending">Pending</span></li>
        </ul>
      </aside>
    </div>
  </section>

  <section class="features" id="modules" data-view>
    <h2>Hotel Modules</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-concierge-bell"></i></div>
        <h3>Front Office</h3>
        <p>Reservations, check-ins/outs, room assignments, and guest profiles.</p>
        </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <h3>Billing</h3>
        <p>Folio charges, invoices, payments, and receipts for guests and accounts.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-bed"></i></div>
        <h3>Room Facilities</h3>
        <p>Track room statuses, amenities, maintenance, and facility usage.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-truck"></i></div>
        <h3>Supplier Management</h3>
        <p>Vendors, purchase orders, deliveries, and inventory updates.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-broom"></i></div>
        <h3>Housekeeping</h3>
        <p>Room cleaning schedules, inspections, and staff assignments.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-tshirt"></i></div>
        <h3>Laundry</h3>
        <p>Linen tracking, guest laundry orders, and processing status.</p>
      </div>
  </section>

  


  <!-- summary lang to ng transactions natin kada department, dapat meron tayong history ng disbursements natin !-->
  <!-- Module Views -->
  <!-- Module views moved to dedicated pages -->

  

  

  

  

  
  <section class="transactions" id="transactions" data-view>
    <h2>Recent Core Transactions</h2>
    <div class="transaction-table-container">
      <table class="transaction-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Module</th>
            <th>Reference</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Front Office</td>
            <td data-label="Reference">Reservation #RF-1023</td>
            <td data-label="Amount">₱8,200.00</td>
            <td data-label="Status">Checked-out</td>
          </tr>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Billing</td>
            <td data-label="Reference">Invoice #INV-5589</td>
            <td data-label="Amount">₱3,450.00</td>
            <td data-label="Status">Paid</td>
          </tr>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Room Facilities</td>
            <td data-label="Reference">Maintenance #MT-302</td>
            <td data-label="Amount">₱1,200.00</td>
            <td data-label="Status">Completed</td>
          </tr>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Supplier Management</td>
            <td data-label="Reference">PO #PO-7741</td>
            <td data-label="Amount">₱12,900.00</td>
            <td data-label="Status">Delivered</td>
          </tr>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Housekeeping</td>
            <td data-label="Reference">Cleaning Batch #HK-220</td>
            <td data-label="Amount">—</td>
            <td data-label="Status">In Progress</td>
          </tr>
          <tr>
            <td data-label="Date">Aug 31, 2025</td>
            <td data-label="Module">Laundry</td>
            <td data-label="Reference">Order #LD-884</td>
            <td data-label="Amount">₱520.00</td>
            <td data-label="Status">Queued</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>


  <footer>
    <div class="footer-section">
      <h3>BSIT 3201 Hotel and Restaurant Management</h3>
      <p>Hotel ng BSIT-3201</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">Features</a></li>
        <li><a href="#">Pricing</a></li>
        <li><a href="#">Reservations</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
    </div>
    <div class="footer-section">
      <h3>Support</h3>
      <ul>
        <li><a href="#">Help Center</a></li>
        <li><a href="#">Bookings</a></li>
        <li><a href="#">Departments</a></li>
      </ul>
    </div>
    <div class="footer-section">
      <h3>Contact Us</h3>
      <ul>
        <li><i class="fas fa-map-marker-alt"></i> 123 Financial Street, Manila</li>
        <li><i class="fas fa-phone"></i> +63 912 345 6789</li>
        <li><i class="fas fa-envelope"></i> info@financialmodule3201.com</li>
      </ul>
    </div>
    <div class="copyright">
      <p>&copy; 2025 Financial Module. All rights reserved.</p>
    </div>
  </footer>

</div>
</body>
<script>
// Mobile nav toggle
document.addEventListener('DOMContentLoaded', function() {
  const navToggle = document.querySelector('.nav-toggle');
  const navList = document.querySelector('.nav-list');
  const views = Array.from(document.querySelectorAll('[data-view]'));
  const links = Array.from(document.querySelectorAll('.nav-list a[data-route]'));
  const transactionsBody = document.querySelector('.transaction-table tbody');

  navToggle.addEventListener('click', function() {
    navList.classList.toggle('nav-list-open');
    navToggle.classList.toggle('open');
  });

  // Auth area
  async function refreshAuth(){
    try{
      const r = await fetch('auth_api.php?action=me');
      const j = await r.json();
      const el = document.getElementById('auth-area');
      if(!el) return;
      if(j && j.user){
        el.innerHTML = `<span>Hi, ${j.user.name} (${j.user.role})</span> <a class="button" href="#" id="logout-btn">Logout</a>`;
      } else {
        el.innerHTML = `<a class="button" href="login.php">Login</a>`;
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

  function setActive(linkHash) {
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === linkHash));
  }

  function showView(id) {
    views.forEach(v => v.hidden = true);
    const el = document.getElementById(id) || document.getElementById('home');
    if (el) el.hidden = false;
    setActive('#' + id);
    navList.classList.remove('nav-list-open');
  }

  function parseHash() {
    const hash = (location.hash || '#home').replace('#','');
    showView(hash);
  }
  window.addEventListener('hashchange', parseHash);
  parseHash();

  const seenKeys = new Set();
  function addTxn({date,module,ref,amount,status}) {
    const key = `${module}|${ref}|${status}`;
    if (seenKeys.has(key)) return;
    seenKeys.add(key);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td data-label="Date">${date}</td>
      <td data-label="Module">${module}</td>
      <td data-label="Reference">${ref}</td>
      <td data-label="Amount">${amount}</td>
      <td data-label="Status">${status}</td>`;
    transactionsBody.prepend(tr);
  }

  document.body.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const now = new Date();
    const date = now.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
    switch(action) {
      case 'reservation':
        addTxn({date, module:'Front Office', ref:'Reservation #RF-' + Math.floor(Math.random()*9000+1000), amount:'—', status:'Booked'}); break;
      case 'checkin':
        addTxn({date, module:'Front Office', ref:'Check-in #CI-' + Math.floor(Math.random()*9000+1000), amount:'—', status:'Checked-in'}); break;
      case 'checkout':
        addTxn({date, module:'Front Office', ref:'Check-out #CO-' + Math.floor(Math.random()*9000+1000), amount:'₱' + (Math.floor(Math.random()*5000)+1500) + '.00', status:'Checked-out'}); break;
      case 'post-charge':
        addTxn({date, module:'Billing', ref:'Charge #CH-' + Math.floor(Math.random()*9000+1000), amount:'₱' + (Math.floor(Math.random()*1500)+300) + '.00', status:'Posted'}); break;
      case 'invoice':
        addTxn({date, module:'Billing', ref:'Invoice #INV-' + Math.floor(Math.random()*9000+1000), amount:'—', status:'Generated'}); break;
      case 'payment':
        addTxn({date, module:'Billing', ref:'Receipt #RC-' + Math.floor(Math.random()*9000+1000), amount:'₱' + (Math.floor(Math.random()*3000)+500) + '.00', status:'Paid'}); break;
      case 'block-room':
        addTxn({date, module:'Room Facilities', ref:'Block RM-' + Math.floor(Math.random()*500), amount:'—', status:'Blocked'}); break;
      case 'maintenance':
        addTxn({date, module:'Room Facilities', ref:'Maintenance #MT-' + Math.floor(Math.random()*900), amount:'₱' + (Math.floor(Math.random()*1200)+200) + '.00', status:'Created'}); break;
      case 'po':
        addTxn({date, module:'Supplier Management', ref:'PO #PO-' + Math.floor(Math.random()*9000+1000), amount:'₱' + (Math.floor(Math.random()*10000)+2000) + '.00', status:'Issued'}); break;
      case 'receive':
        addTxn({date, module:'Supplier Management', ref:'DR #DR-' + Math.floor(Math.random()*9000+1000), amount:'—', status:'Received'}); break;
      case 'assign-cleaning':
        addTxn({date, module:'Housekeeping', ref:'Clean #HK-' + Math.floor(Math.random()*900), amount:'—', status:'Assigned'}); break;
      case 'inspection':
        addTxn({date, module:'Housekeeping', ref:'Inspect #IN-' + Math.floor(Math.random()*900), amount:'—', status:'Logged'}); break;
      case 'laundry-order':
        addTxn({date, module:'Laundry', ref:'Laundry #LD-' + Math.floor(Math.random()*9000+1000), amount:'₱' + (Math.floor(Math.random()*800)+120) + '.00', status:'Queued'}); break;
      case 'laundry-complete':
        addTxn({date, module:'Laundry', ref:'Laundry #LD-' + Math.floor(Math.random()*9000+1000), amount:'—', status:'Completed'}); break;
      default: return;
    }
  });

  // Handle module forms -> call PHP endpoints
  async function submitModuleForm(form) {
    const endpoint = form.getAttribute('data-endpoint');
    const action = form.getAttribute('data-action');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.action = action;
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    // Map response to transactions row (best-effort demo mapping)
    const date = new Date().toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
    const moduleName = json.module || 'Module';
    const ref = (json.data && (json.data.reservationNo || json.data.invoiceNo || json.data.ticket || json.data.po || json.data.dr || json.data.task || json.data.order)) || (json.action || 'Action');
    const amount = (json.data && (json.data.amount !== undefined ? ('₱' + Number(json.data.amount).toFixed(2)) : '—')) || '—';
    const status = (json.data && (json.data.status || json.action)) || json.action || 'OK';
    addTxn({ date, module: moduleName, ref: String(ref), amount, status: String(status) });
  }

  document.querySelectorAll('.module-form').forEach(form => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      submitModuleForm(form).catch(console.error);
    });
  });

  // Cross-page realtime updates via BroadcastChannel
  let bc;
  try { bc = new BroadcastChannel('tx-updates'); } catch(e) { bc = null; }
  if (bc) {
    bc.onmessage = (ev) => {
      const r = ev.data;
      if (!r) return;
      const date = r.date || new Date().toLocaleDateString('en-PH', { month:'short', day:'2-digit', year:'numeric' });
      const amount = (r.amount!=null) ? (typeof r.amount==='string' && r.amount.startsWith('₱') ? r.amount : ('₱'+Number(r.amount).toFixed(2))) : '—';
      addTxn({date, module: r.module || 'Module', ref: r.ref || 'Reference', amount, status: r.status || 'OK'});
    };
  }

  // Realtime dashboard polling for new transactions
  let lastId = 0;
  async function fetchInitial() {
    const r = await fetch('transactions.php?limit=20');
    const j = await r.json();
    if (!j.rows) return;
    // oldest to newest
    j.rows.slice().reverse().forEach(row => {
      lastId = Math.max(lastId, row.id || lastId);
      addTxn({
        date: new Date(row.tx_date).toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'}),
        module: row.module,
        ref: row.reference,
        amount: row.amount != null ? ('₱'+Number(row.amount).toFixed(2)) : '—',
        status: row.status
      });
    });
  }
  async function poll() {
    try {
      const r = await fetch('transactions.php?since_id=' + (lastId || 0) + '&limit=20');
      const j = await r.json();
      if (Array.isArray(j.rows)) {
        j.rows.forEach(row => {
          lastId = Math.max(lastId, row.id || lastId);
          addTxn({
            date: new Date(row.tx_date).toLocaleDateString('en-PH',{month:'short',day:'2-digit',year:'numeric'}),
            module: row.module,
            ref: row.reference,
            amount: row.amount != null ? ('₱'+Number(row.amount).toFixed(2)) : '—',
            status: row.status
          });
        });
      }
    } catch (e) { /* ignore transient errors */ }
  }
  fetchInitial();
  setInterval(poll, 4000);
});
// Chart.js sample data
const budgetExpensesChart = new Chart(document.getElementById('budgetExpensesChart'), {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Budget',
        data: [100, 120, 130, 140, 150, 160],
        borderColor: '#fbc777',
        backgroundColor: 'rgba(251,199,119,0.2)',
        fill: true,
        tension: 0.4
      },
      {
        label: 'Expenses',
        data: [90, 110, 120, 130, 140, 150],
        borderColor: '#4caf50',
        backgroundColor: 'rgba(76,175,80,0.2)',
        fill: true,
        tension: 0.4
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { labels: { color: 'white' } } },
    scales: { x: { ticks: { color: 'white' } }, y: { ticks: { color: 'white' } } }
  }
});

const collectionsTrendChart = new Chart(document.getElementById('collectionsTrendChart'), {
  type: 'bar',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [{
      label: 'Collections',
      data: [30, 40, 35, 50, 45, 60],
      backgroundColor: '#fbc777',
      borderRadius: 5
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { labels: { color: 'white' } } },
    scales: { x: { ticks: { color: 'white' } }, y: { ticks: { color: 'white' } } }
  }
});

const expenseDistributionChart = new Chart(document.getElementById('expenseDistributionChart'), {
  type: 'doughnut',
  data: {
    labels: ['Salaries', 'Supplies', 'Utilities', 'Other'],
    datasets: [{
      label: 'Expenses',
      data: [40, 25, 20, 15],
      backgroundColor: ['#fbc777', '#4caf50', '#2196f3', '#e91e63']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { labels: { color: 'white' } } }
  }
});

const cashFlowChart = new Chart(document.getElementById('cashFlowChart'), {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [{
      label: 'Cash Flow',
      data: [20, 30, 25, 35, 40, 45],
      borderColor: '#2196f3',
      backgroundColor: 'rgba(33,150,243,0.2)',
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { labels: { color: 'white' } } },
    scales: { x: { ticks: { color: 'white' } }, y: { ticks: { color: 'white' } } }
  }
});
</script>
</html>