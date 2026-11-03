const API_BASE = 'api';

const user = JSON.parse(sessionStorage.getItem('netpoint_user') || 'null');
if (!user) window.location.href = 'login.html';
document.getElementById('staffName').textContent = user.name;

document.getElementById('logoutBtn').addEventListener('click', () => {
  sessionStorage.removeItem('netpoint_user');
  window.location.href = 'login.html';
});

function toast(message, isError = false) {
  const el = document.getElementById('toast');
  el.textContent = message;
  el.className = 'toast show' + (isError ? ' error' : '');
  setTimeout(() => el.classList.remove('show'), 3200);
}

async function apiGet(path) {
  const res = await fetch(`${API_BASE}/${path}`);
  return res.json();
}
async function apiPost(path, body) {
  const res = await fetch(`${API_BASE}/${path}`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
  });
  return res.json();
}
async function apiPut(path, body) {
  const res = await fetch(`${API_BASE}/${path}`, {
    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
  });
  return res.json();
}
async function apiDelete(path) {
  const res = await fetch(`${API_BASE}/${path}`, { method: 'DELETE' });
  return res.json();
}

// ---------- Tab switching ----------
const tabComputers = document.getElementById('tabComputers');
const tabCustomers = document.getElementById('tabCustomers');
const panelComputers = document.getElementById('panelComputers');
const panelCustomers = document.getElementById('panelCustomers');

tabComputers.addEventListener('click', () => {
  tabComputers.classList.add('active'); tabCustomers.classList.remove('active');
  panelComputers.style.display = 'block'; panelCustomers.style.display = 'none';
});
tabCustomers.addEventListener('click', () => {
  tabCustomers.classList.add('active'); tabComputers.classList.remove('active');
  panelCustomers.style.display = 'block'; panelComputers.style.display = 'none';
});

// ===================================================
// COMPUTERS
// ===================================================
let editingComputerId = null;

async function loadComputers() {
  const res = await apiGet('computers/read.php');
  const body = document.getElementById('computersBody');
  if (!res.success || res.data.length === 0) {
    body.innerHTML = '<tr class="empty-row"><td colspan="6">No computers yet.</td></tr>';
    return;
  }
  body.innerHTML = res.data.map(pc => `
    <tr>
      <td class="mono">${pc.computer_number}</td>
      <td>${pc.computer_name || '—'}</td>
      <td>${pc.specifications || '—'}</td>
      <td class="mono">₱${Number(pc.hourly_rate).toFixed(2)}</td>
      <td><span class="tiny-status status-${pc.status}">${pc.status}</span></td>
      <td class="row-actions">
        <button class="edit-btn" data-id="${pc.id}">Edit</button>
        <button class="delete-btn" data-id="${pc.id}" data-name="${pc.computer_number}">Delete</button>
      </td>
    </tr>`).join('');

  body.querySelectorAll('.edit-btn').forEach(b => b.addEventListener('click', () => openComputerModal(res.data.find(p => p.id == b.dataset.id))));
  body.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', () => deleteComputer(b.dataset.id, b.dataset.name)));
}

const computerModal = document.getElementById('computerModal');

function openComputerModal(pc = null) {
  editingComputerId = pc ? pc.id : null;
  document.getElementById('computerModalTitle').textContent = pc ? 'Edit Computer' : 'Add Computer';
  document.getElementById('cf_number').value = pc ? pc.computer_number : '';
  document.getElementById('cf_name').value = pc ? (pc.computer_name || '') : '';
  document.getElementById('cf_specs').value = pc ? (pc.specifications || '') : '';
  document.getElementById('cf_rate').value = pc ? pc.hourly_rate : '';
  document.getElementById('cf_status').value = pc ? pc.status : 'Available';
  computerModal.hidden = false;
}

document.getElementById('addComputerBtn').addEventListener('click', () => openComputerModal());
document.getElementById('computerCancelBtn').addEventListener('click', () => computerModal.hidden = true);

document.getElementById('computerSaveBtn').addEventListener('click', async () => {
  const payload = {
    computer_number: document.getElementById('cf_number').value.trim(),
    computer_name: document.getElementById('cf_name').value.trim(),
    specifications: document.getElementById('cf_specs').value.trim(),
    hourly_rate: parseFloat(document.getElementById('cf_rate').value),
    status: document.getElementById('cf_status').value
  };

  if (!payload.computer_number || isNaN(payload.hourly_rate)) {
    toast('Computer number and hourly rate are required.', true);
    return;
  }

  const result = editingComputerId
    ? await apiPut(`computers/update.php?id=${editingComputerId}`, payload)
    : await apiPost('computers/create.php', payload);

  if (result.success) {
    computerModal.hidden = true;
    toast(editingComputerId ? 'Computer updated.' : 'Computer added.');
    loadComputers();
  } else {
    toast(result.message || 'Save failed.', true);
  }
});

async function deleteComputer(id, name) {
  if (!confirm(`Delete ${name}? This cannot be undone.`)) return;
  const result = await apiDelete(`computers/delete.php?id=${id}`);
  if (result.success) {
    toast('Computer deleted.');
    loadComputers();
  } else {
    toast(result.message || 'Could not delete.', true);
  }
}

// ===================================================
// CUSTOMERS
// ===================================================
let editingCustomerId = null;

async function loadCustomers() {
  const res = await apiGet('customers/read.php');
  const body = document.getElementById('customersBody');
  if (!res.success || res.data.length === 0) {
    body.innerHTML = '<tr class="empty-row"><td colspan="4">No customers yet.</td></tr>';
    return;
  }
  body.innerHTML = res.data.map(c => `
    <tr>
      <td>${c.customer_name}</td>
      <td class="mono">${c.contact_number || '—'}</td>
      <td class="mono">${c.email || '—'}</td>
      <td class="mono">₱${Number(c.wallet_balance || 0).toFixed(2)}</td>
      <td class="row-actions">
        <button class="edit-btn" data-id="${c.id}">Edit</button>
        <button class="wallet-btn" data-id="${c.id}" data-name="${c.customer_name}" data-balance="${c.wallet_balance || 0}">Load</button>
        <button class="delete-btn" data-id="${c.id}" data-name="${c.customer_name}">Delete</button>
      </td>
    </tr>`).join('');

  body.querySelectorAll('.wallet-btn').forEach(b => b.addEventListener('click', () => openWalletModal(b.dataset.id, b.dataset.name, b.dataset.balance)));

  body.querySelectorAll('.edit-btn').forEach(b => b.addEventListener('click', () => openCustomerModal(res.data.find(c => c.id == b.dataset.id))));
  body.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', () => deleteCustomer(b.dataset.id, b.dataset.name)));
}

const customerModal = document.getElementById('customerModal');

function openCustomerModal(c = null) {
  editingCustomerId = c ? c.id : null;
  document.getElementById('customerModalTitle').textContent = c ? 'Edit Customer' : 'Add Customer';
  document.getElementById('cu_name').value = c ? c.customer_name : '';
  document.getElementById('cu_contact').value = c ? (c.contact_number || '') : '';
  document.getElementById('cu_email').value = c ? (c.email || '') : '';
  customerModal.hidden = false;
}

document.getElementById('addCustomerBtn').addEventListener('click', () => openCustomerModal());
document.getElementById('customerCancelBtn').addEventListener('click', () => customerModal.hidden = true);

document.getElementById('customerSaveBtn').addEventListener('click', async () => {
  const payload = {
    customer_name: document.getElementById('cu_name').value.trim(),
    contact_number: document.getElementById('cu_contact').value.trim(),
    email: document.getElementById('cu_email').value.trim()
  };

  if (!payload.customer_name) {
    toast('Customer name is required.', true);
    return;
  }

  const result = editingCustomerId
    ? await apiPut(`customers/update.php?id=${editingCustomerId}`, payload)
    : await apiPost('customers/create.php', payload);

  if (result.success) {
    customerModal.hidden = true;
    toast(editingCustomerId ? 'Customer updated.' : 'Customer added.');
    loadCustomers();
  } else {
    toast(result.message || 'Save failed.', true);
  }
});

async function deleteCustomer(id, name) {
  if (!confirm(`Delete ${name}? This cannot be undone.`)) return;
  const result = await apiDelete(`customers/delete.php?id=${id}`);
  if (result.success) {
    toast('Customer deleted.');
    loadCustomers();
  } else {
    toast(result.message || 'Could not delete.', true);
  }
}

// ---------- Wallet load ----------
const walletModal = document.getElementById('walletModal');
let walletTargetCustomerId = null;

function openWalletModal(id, name, balance) {
  walletTargetCustomerId = id;
  document.getElementById('walletModalSub').textContent = `${name} · current balance ₱${Number(balance).toFixed(2)}`;
  document.getElementById('wf_amount').value = '';
  document.getElementById('wf_note').value = '';
  walletModal.hidden = false;
}

document.getElementById('walletCancelBtn').addEventListener('click', () => walletModal.hidden = true);

document.getElementById('walletSaveBtn').addEventListener('click', async () => {
  const amount = parseFloat(document.getElementById('wf_amount').value);
  const note = document.getElementById('wf_note').value.trim();

  if (isNaN(amount) || amount <= 0) {
    toast('Enter a valid amount.', true);
    return;
  }

  const result = await apiPost('wallet/load.php', {
    customer_id: walletTargetCustomerId,
    amount: amount,
    user_id: user.id,
    note: note
  });

  if (result.success) {
    walletModal.hidden = true;
    toast(`Wallet loaded. New balance: ₱${result.data.new_balance.toFixed(2)}`);
    loadCustomers();
  } else {
    toast(result.message || 'Could not load wallet.', true);
  }
});

// ---------- Init ----------
loadComputers();
loadCustomers();
