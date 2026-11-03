const API_BASE = 'api';

const form = document.getElementById('signupForm');
const errorBox = document.getElementById('signupError');
const signupBtn = document.getElementById('signupBtn');

function showError(msg) {
  errorBox.textContent = msg;
  errorBox.classList.add('show');
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorBox.classList.remove('show');

  const name = document.getElementById('name').value.trim();
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const confirm = document.getElementById('confirm').value;
  const signup_code = document.getElementById('signup_code').value;

  if (password !== confirm) {
    showError('Passwords do not match.');
    return;
  }

  signupBtn.disabled = true;
  signupBtn.textContent = 'Creating...';

  try {
    const res = await fetch(`${API_BASE}/auth/signup.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, username, password, signup_code })
    });
    const result = await res.json();

    if (result.success) {
      sessionStorage.setItem('netpoint_notice', 'Account created. Please log in.');
      window.location.href = 'login.html';
    } else {
      showError(result.message || 'Signup failed.');
    }
  } catch (err) {
    showError('Could not reach the server. Please try again.');
  } finally {
    signupBtn.disabled = false;
    signupBtn.textContent = 'Create account';
  }
});
