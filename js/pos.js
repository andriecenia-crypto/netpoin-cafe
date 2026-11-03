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

function formatMoney(n) { return '₱' + Number(n).toFixed(2); }

// ---------- Tab switching ----------
const tabs = {
  Sell: [document.getElementById('tabSell'), document.getElementById('panelSell')],
  Products: [document.getElementById('tabProducts'), document.getElementById('panelProducts')],
  SalesHistory: [document.getElementById('tabSalesHistory'), document.getElementById('panelSalesHistory')],
};
Object.entries(tabs).forEach(([key, [btn, panel]]) => {
  btn.addEventListener('click', () => {
    Object.values(tabs).forEach(([b, p]) => { b.classList.remove('active'); p.style.display = 'none'; });
    btn.classList.add('active');
    panel.style.display = 'block';
    if (key === 'Products') loadProducts();
    if (key === 'SalesHistory') loadSalesHistory();
  });
});

// ===================================================
// SELL PANEL
// ===================================================
let products = [];
let activePaymentMethod = 'Cash';

async function loadSellFormData() {
  const [productsRes, customersRes] = await Promise.all([
    apiGet('products/read.php'),
    apiGet('customers/read.php')
  ]);

  if (productsRes.success) {
    products = productsRes.data;
    document.getElementById('sell_product').innerHTML = products.map(p =>
      `<option value="${p.id}">${p.product_name} — ${formatMoney(p.price)} (${p.stock_quantity} in stock)</option>`
    ).join('');
  }

  if (customersRes.success) {
    const select = document.getElementById('sell_customer');
    select.innerHTML = '<option value="">Walk-in / no account</option>' +
      customersRes.data.map(c => `<option value="${c.id}">${c.customer_name} · ${formatMoney(c.wallet_balance || 0)}</option>`).join('');
  }
}

document.getElementById('payCashBtn').addEventListener('click', () => setPaymentMethod('Cash'));
document.getElementById('payWalletBtn').addEventListener('click', () => setPaymentMethod('Wallet'));

function setPaymentMethod(method) {
  activePaymentMethod = method;
  document.getElementById('payCashBtn').classList.toggle('active', method === 'Cash');
  document.getElementById('payWalletBtn').classList.toggle('active', method === 'Wallet');
}

document.getElementById('sellConfirmBtn').addEventListener('click', async () => {
  const productId = document.getElementById('sell_product').value;
  const quantity = parseInt(document.getElementById('sell_qty').value, 10);
  const customerId = document.getElementById('sell_customer').value;

  if (!productId || !quantity || quantity < 1) {
    toast('Pick a product and a valid quantity.', true);
    return;
  }
  if (activePaymentMethod === 'Wallet' && !customerId) {
    toast('A customer must be selected to pay by Wallet.', true);
    return;
  }

  const btn = document.getElementById('sellConfirmBtn');
  btn.disabled = true;
  btn.textContent = 'Processing…';

  const result = await apiPost('sales/create.php', {
    product_id: productId,
    quantity: quantity,
    customer_id: customerId || null,
    user_id: user.id,
    payment_method: activePaymentMethod
  });

  btn.disabled = false;
  btn.textContent = 'Complete Sale';

  if (result.success) {
    const receipt = document.getElementById('sellReceipt');
    receipt.style.display = 'block';
    receipt.innerHTML = `<div class="row total"><span>Total</span><span>${formatMoney(result.data.subtotal)}</span></div>`;
    toast('Sale recorded.');
    document.getElementById('sell_qty').value = 1;
    loadSellFormData(); // refresh stock counts + wallet balances shown
  } else {
    toast(result.message || 'Sale failed.', true);
  }
});

// ===================================================
// PRODUCTS PANEL
// ===================================================
let editingProductId = null;

async function loadProducts() {
  const res = await apiGet('products/read.php');
  const body = document.getElementById('productsBody');
  if (!res.success || res.data.length === 0) {
    body.innerHTML = '<tr class="empty-row"><td colspan="5">No products yet.</td></tr>';
    return;
  }
  body.innerHTML = res.data.map(p => `
    <tr>
      <td>${p.product_name}</td>
      <td>${p.category || '—'}</td>
      <td class="mono">${formatMoney(p.price)}</td>
      <td class="mono ${p.stock_quantity <= 5 ? 'low-stock' : ''}">${p.stock_quantity}</td>
      <td class="row-actions">
        <button class="edit-btn" data-id="${p.id}">Edit</button>
        <button class="delete-btn" data-id="${p.id}" data-name="${p.product_name}">Delete</button>
      </td>
    </tr>`).join('');

  body.querySelectorAll('.edit-btn').forEach(b => b.addEventListener('click', () => openProductModal(res.data.find(p => p.id == b.dataset.id))));
  body.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', () => deleteProduct(b.dataset.id, b.dataset.name)));
}

const productModal = document.getElementById('productModal');

function openProductModal(p = null) {
  editingProductId = p ? p.id : null;
  document.getElementById('productModalTitle').textContent = p ? 'Edit Product' : 'Add Product';
  document.getElementById('pf_name').value = p ? p.product_name : '';
  document.getElementById('pf_category').value = p ? (p.category || '') : '';
  document.getElementById('pf_price').value = p ? p.price : '';
  document.getElementById('pf_stock').value = p ? p.stock_quantity : '';
  productModal.hidden = false;
}

document.getElementById('addProductBtn').addEventListener('click', () => openProductModal());
document.getElementById('productCancelBtn').addEventListener('click', () => productModal.hidden = true);

document.getElementById('productSaveBtn').addEventListener('click', async () => {
  const payload = {
    product_name: document.getElementById('pf_name').value.trim(),
    category: document.getElementById('pf_category').value.trim(),
    price: parseFloat(document.getElementById('pf_price').value),
    stock_quantity: parseInt(document.getElementById('pf_stock').value, 10) || 0
  };

  if (!payload.product_name || isNaN(payload.price)) {
    toast('Product name and price are required.', true);
    return;
  }

  const result = editingProductId
    ? await apiPut(`products/update.php?id=${editingProductId}`, payload)
    : await apiPost('products/create.php', payload);

  if (result.success) {
    productModal.hidden = true;
    toast(editingProductId ? 'Product updated.' : 'Product added.');
    loadProducts();
  } else {
    toast(result.message || 'Save failed.', true);
  }
});

async function deleteProduct(id, name) {
  if (!confirm(`Delete ${name}? This cannot be undone.`)) return;
  const result = await apiDelete(`products/delete.php?id=${id}`);
  if (result.success) {
    toast('Product deleted.');
    loadProducts();
  } else {
    toast(result.message || 'Could not delete.', true);
  }
}

// ===================================================
// SALES HISTORY PANEL
// ===================================================
async function loadSalesHistory() {
  const res = await apiGet('sales/read.php');
  const body = document.getElementById('salesBody');

  if (!res.success || res.data.length === 0) {
    body.innerHTML = '<tr class="empty-row"><td colspan="5">No sales yet.</td></tr>';
    document.getElementById('salesCount').textContent = '';
    return;
  }

  document.getElementById('salesCount').textContent = `${res.data.length} total`;

  body.innerHTML = res.data.slice(0, 25).map(s => {
    const product = products.find(p => p.id == s.product_id);
    const dt = new Date(s.created_at.replace(' ', 'T'));
    return `
      <tr>
        <td>${dt.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
        <td>${product ? product.product_name : '#' + s.product_id}</td>
        <td class="mono">${s.quantity}</td>
        <td class="mono">${formatMoney(s.subtotal)}</td>
        <td>${s.payment_method}</td>
      </tr>`;
  }).join('');
}

// ---------- Init ----------
loadSellFormData();
