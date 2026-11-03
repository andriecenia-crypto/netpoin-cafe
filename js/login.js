const API_BASE = 'api';

const form = document.getElementById('loginForm');
const errorBox = document.getElementById('loginError');
const loginBtn = document.getElementById('loginBtn');

// If already logged in, skip straight to the dashboard
if (sessionStorage.getItem('netpoint_user')) {
  window.location.href = 'dashboard.html';
}

// Show the "account created" notice coming from the signup page
const notice = sessionStorage.getItem('netpoint_notice');
if (notice) {
  sessionStorage.removeItem('netpoint_notice');
  errorBox.textContent = notice;
  errorBox.style.background = 'var(--status-available-bg)';
  errorBox.style.color = 'var(--status-available)';
  errorBox.classList.add('show');
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorBox.classList.remove('show');

  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;

  loginBtn.disabled = true;
  loginBtn.textContent = 'Checking...';

  try {
    const res = await fetch(`${API_BASE}/auth/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const result = await res.json();

    if (result.success) {
      // Session only - clears when the browser tab closes, matches "staff terminal" use
      sessionStorage.setItem('netpoint_user', JSON.stringify(result.data));
      window.location.href = 'dashboard.html';
    } else {
      errorBox.textContent = result.message || 'Login failed.';
      errorBox.classList.add('show');
    }
  } catch (err) {
    errorBox.textContent = 'Could not reach the server. Please try again.';
    errorBox.classList.add('show');
  } finally {
    loginBtn.disabled = false;
    loginBtn.textContent = 'Log in';
  }
});
