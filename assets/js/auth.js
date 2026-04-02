// C:\xampp\htdocs\pos\assets\js\auth.js

document.addEventListener('DOMContentLoaded', () => {
    // Handling Login form submission (only on index.php)
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const usernameInput = document.getElementById('username').value;
            const passwordInput = document.getElementById('password').value;
            const submitBtn = document.getElementById('loginBtn');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';

            try {
                const response = await apiCall('api/auth/login.php', 'POST', {
                    username: usernameInput,
                    password: passwordInput
                });

                if (response.success) {
                    window.location.href = '/pos/dashboard.php';
                }
            } catch (error) {
                showAlert(error.message, 'danger', 'loginAlertContainer');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Login';
            }
        });
    }

    // Handling Logout logic from the header (everywhere when logged in)
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await apiCall('api/auth/logout.php', 'POST');
                window.location.href = '/pos/index.php';
            } catch (error) {
                alert('Logout failed: ' + error.message);
            }
        });
    }
});
