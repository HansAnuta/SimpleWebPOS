// auth.js

const AUTH_ICON_MAP = {
    success: 'icons/success.png',
    error: 'icons/error.png'
};

function authIcon(path, alt = 'icon') {
    return `<img src="${path}" class="ui-icon" alt="${alt}">`;
}

// Define the notification function ONCE for all pages
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    if (!container) return; // Safety check

    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    const iconPath = type === 'success' ? AUTH_ICON_MAP.success : AUTH_ICON_MAP.error;
    notif.innerHTML = `${authIcon(iconPath, type)}<span>${message}</span>`;
    
    container.appendChild(notif);
    
    setTimeout(() => {
        notif.classList.add('hiding'); 
        notif.addEventListener('animationend', () => {
            notif.remove();
        });
    }, 3000);
}

// LOGIN LOGIC
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const btn = e.target.querySelector('button');
        const originalText = btn.innerHTML;
        
        btn.textContent = 'Logging in...';
        btn.disabled = true;

        try {
            const response = await fetch('api/auth_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (response.status === 403 && data.redirect) {
                window.location.href = data.redirect;
            } else if (response.ok) {
                window.location.href = 'index.php'; 
            } else {
                showNotification("Invalid account", "error");
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Connection error. Please try again.", "error");
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// SIGNUP LOGIC
const signupForm = document.getElementById('signup-form');
if (signupForm) {
    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        // Assuming you have firstname and lastname inputs in your signup form
        const first_name = document.getElementById('firstname').value.trim();
        const last_name = document.getElementById('lastname').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        try {
            const res = await fetch('api/auth_register.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ first_name, last_name, username, password }) 
            });
            
            const data = await res.json();
            
            if (res.ok) { 
                showNotification('Account created! Redirecting...', 'success');
                setTimeout(() => window.location.href = 'login.php', 1500);
            } else { 
                showNotification(data.message || 'Signup failed', 'error'); 
            }
        } catch (err) { 
            console.error(err); 
            showNotification('Connection error. Please try again.', 'error'); 
        }
    });
}

// DEVELOPER LOGIN LOGIC
const devLoginForm = document.getElementById('dev-login-form');
if (devLoginForm) {
    devLoginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const u = document.getElementById('dev-username').value;
        const p = document.getElementById('dev-password').value;
        const btn = e.target.querySelector('button');
        const err = document.getElementById('error-msg');
        
        const originalText = btn.innerHTML;
        btn.textContent = 'Authenticating...';
        btn.disabled = true; 
        if(err) err.style.display = 'none';

        try {
            const res = await fetch('api/dev_login.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: u, password: p })
            });
            
            if (res.ok) { 
                btn.textContent = 'Authorized';
                btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                btn.style.boxShadow = '0 10px 20px -5px rgba(16, 185, 129, 0.4)';
                setTimeout(() => { window.location.href = 'developer.php'; }, 500);
            } else { 
                if(err) {
                    err.innerText = 'Access Denied. Invalid credentials.'; 
                    err.style.display = 'block'; 
                }
                btn.innerHTML = originalText; 
                btn.disabled = false; 
            }
        } catch (error) {
            if(err) {
                err.innerText = "Connection Error. Mainframe unreachable."; 
                err.style.display = 'block';
            }
            btn.innerHTML = originalText; 
            btn.disabled = false;
        }
    });
}