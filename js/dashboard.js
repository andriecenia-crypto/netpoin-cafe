const API_BASE = 'api';

const user = JSON.parse(sessionStorage.getItem('netpoint_user') || 'null');
if (!user) {
  window.location.href = 'login.html';
}

document.getElementById('staffName').textContent = user.name;

document.getElementById('logoutBtn').addEventListener('click', () => {
  sessionStorage.removeItem('netpoint_user');
  window.location.href = 'login.html';
});

// ---------- Toast ----------
function toast(message, isError = false) {
  const el = document.getElementById('toast');
  el.textContent = message;
  el.className = 'toast show' + (isError ? ' error' : '');
  setTimeout(() => el.classList.remove('show'), 3200);
}

// ---------- API helpers ----------
async function apiGet(path) {
  const res = await fetch(`${API_BASE}/${path}`);
  return res.json();
}
async function apiPost(path, body) {
  const res = await fetch(`${API_BASE}/${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });
  return res.json();
}
async function apiPut(path) {
  const res = await fetch(`${API_BASE}/${path}`, { method: 'PUT' });
  return res.json();
}

// ---------- State ----------
let computers = [];
let ongoingRentals = [];
let customers = [];
let activeComputer = null;      // computer currently targeted by the rent modal
let activeRentalId = null;      // rental currently targeted by the checkout modal

function formatMoney(n) {
  return '₱' + Number(n).toFixed(2);
}
function formatTime(dt) {
  if (!dt) return '—';
  const d = new Date(dt.replace(' ', 'T'));
  return d.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function minutesSince(dt) {
  const start = new Date(dt.replace(' ', 'T'));
  return Math.max(0, Math.floor((Date.now() - start.getTime()) / 60000));
}

// ---------- Load + render stations ----------
async function loadStations() {
  const [computersRes, ongoingRes] = await Promise.all([
    apiGet('computers/read.php'),
    apiGet('rentals/read.php?status=Ongoing')
  ]);

  if (!computersRes.success) {
    document.getElementById('stationGrid').innerHTML = '<p class="state-msg">Could not load stations.</p>';
    return;
  }

  computers = computersRes.data;
  ongoingRentals = ongoingRes.success ? ongoingRes.data : [];

  renderStations();
}

function renderStations() {
  const grid = document.getElementById('stationGrid');
  document.getElementById('stationCount').textContent = `${computers.length} total`;

  if (computers.length === 0) {
    grid.innerHTML = '<p class="state-msg">No computers set up yet.</p>';
    return;
  }

  grid.innerHTML = computers.map(pc => {
    const statusClass = 'status-' + pc.status.toLowerCase();
    const rental = ongoingRentals.find(r => r.computer_id == pc.id);

    let actionHtml, extraHtml = '';
    if (pc.status === 'Available') {
      actionHtml = `<button class="card-action" data-action="rent" data-id="${pc.id}">Start Rental</button>`;
    } else if (pc.status === 'Occupied' && rental) {
      const mins = minutesSince(rental.time_in);
      extraHtml = `<div class="elapsed">since ${formatTime(rental.time_in)} · ${mins} min</div>`;
      actionHtml = `<button class="card-action" data-action="checkout" data-rental="${rental.id}">End Rental</button>`;
    } else if (pc.status === 'Occupied') {
      actionHtml = `<button class="card-action" disabled>Occupied</button>`;
    } else {
      actionHtml = `<button class="card-action" disabled>Unavailable</button>`;
    }

    return `
      <div class="terminal-card ${statusClass}">
        <div class="led"></div>
        <div class="pc-number">${pc.computer_number}</div>
        <div class="pc-name">${pc.computer_name || pc.computer_number}</div>
        <div class="pc-specs">${pc.specifications || ''}</div>
        <div class="pc-rate">${formatMoney(pc.hourly_rate)}/hr</div>
        <span class="status-badge">${pc.status}</span>
        ${extraHtml}
        ${actionHtml}
      </div>`;
  }).join('');

  grid.querySelectorAll('[data-action="rent"]').forEach(btn => {
    btn.addEventListener('click', () => openRentModal(btn.dataset.id));
  });
  grid.querySelectorAll('[data-action="checkout"]').forEach(btn => {
    btn.addEventListener('click', () => openCheckoutModal(btn.dataset.rental));
  });
}

// ---------- Customers (for the rent modal dropdown) ----------
async function loadCustomers() {
  const res = await apiGet('customers/read.php');
  if (res.success) {
    customers = res.data;
    const select = document.getElementById('customerSelect');
    select.innerHTML = '<option value="">Walk-in / no account</option>' +
      customers.map(c => `<option value="${c.id}">${c.customer_name}</option>`).join('');
  }
}

// ---------- Rent modal ----------
const rentModal = document.getElementById('rentModal');

function openRentModal(computerId) {
  activeComputer = computers.find(c => c.id == computerId);
  document.getElementById('rentModalSub').textContent =
    `${activeComputer.computer_number} · ${formatMoney(activeComputer.hourly_rate)}/hr`;
  document.getElementById('customerSelect').value = '';
  rentModal.hidden = false;
}

document.getElementById('rentCancelBtn').addEventListener('click', () => rentModal.hidden = true);

document.getElementById('rentConfirmBtn').addEventListener('click', async () => {
  const customerId = document.getElementById('customerSelect').value;
  const btn = document.getElementById('rentConfirmBtn');
  btn.disabled = true;
  btn.textContent = 'Starting…';

  const result = await apiPost('rentals/create.php', {
    computer_id: activeComputer.id,
    customer_id: customerId || null,
    user_id: user.id
  });

  btn.disabled = false;
  btn.textContent = 'Start Rental';

  if (result.success) {
    rentModal.hidden = true;
    toast(`Rental started · ${result.data.rental_code}`);
    loadStations();
  } else {
    toast(result.message || 'Could not start rental.', true);
  }
});

// ---------- Checkout modal ----------
const checkoutModal = document.getElementById('checkoutModal');

function openCheckoutModal(rentalId) {
  activeRentalId = rentalId;
  document.getElementById('checkoutReceipt').style.display = 'none';
  document.getElementById('checkoutModalSub').textContent = 'Confirm to check out and calculate the bill.';
  document.getElementById('checkoutConfirmBtn').style.display = 'block';
  checkoutModal.hidden = false;
}

document.getElementById('checkoutCancelBtn').addEventListener('click', () => {
  checkoutModal.hidden = true;
  loadStations(); // refresh in case they end up leaving it open a while
});

document.getElementById('checkoutConfirmBtn').addEventListener('click', async () => {
  const btn = document.getElementById('checkoutConfirmBtn');
  btn.disabled = true;
  btn.textContent = 'Ending…';

  const result = await apiPut(`rentals/checkout.php?id=${activeRentalId}`);

  btn.disabled = false;
  btn.textContent = 'End Rental';

  if (result.success) {
    const r = result.data;
    document.getElementById('checkoutModalSub').textContent = 'Rental completed.';
    const receipt = document.getElementById('checkoutReceipt');
    receipt.style.display = 'block';
    receipt.innerHTML = `
      <div class="row"><span>Code</span><span>${r.rental_code}</span></div>
      <div class="row"><span>Duration</span><span>${r.duration_minutes} min</span></div>
      <div class="row total"><span>Total</span><span>${formatMoney(r.total_amount)}</span></div>
    `;
    btn.style.display = 'none';
    toast('Rental checked out.');
    loadStations();
    loadHistory();
  } else {
    toast(result.message || 'Could not check out rental.', true);
  }
});

// ---------- History ----------
async function loadHistory() {
  const res = await apiGet('rentals/read.php');
  const body = document.getElementById('historyBody');

  if (!res.success || res.data.length === 0) {
    body.innerHTML = '<tr class="empty-row"><td colspan="7">No rentals yet.</td></tr>';
    document.getElementById('historyCount').textContent = '';
    return;
  }

  const rows = res.data.slice(0, 15); // most recent 15
  document.getElementById('historyCount').textContent = `${res.data.length} total`;

  body.innerHTML = rows.map(r => {
    const pc = computers.find(c => c.id == r.computer_id);
    return `
      <tr>
        <td class="mono">${r.rental_code}</td>
        <td>${pc ? pc.computer_number : '#' + r.computer_id}</td>
        <td>${formatTime(r.time_in)}</td>
        <td>${formatTime(r.time_out)}</td>
        <td>${r.duration_minutes !== null ? r.duration_minutes + ' min' : '—'}</td>
        <td class="mono">${r.total_amount !== null ? formatMoney(r.total_amount) : '—'}</td>
        <td>${r.status}</td>
      </tr>`;
  }).join('');
}

// ---------- Init ----------
(async function init() {
  await loadStations();
  await loadCustomers();
  await loadHistory();
})();
