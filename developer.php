<?php
session_start();
// Security Gate: Check if user is logged in AND is a developer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .dev-container { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .dev-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .dev-header h1 { color: var(--primary); margin: 0; }
        .raw-pass { font-family: monospace; background: #e5e7eb; padding: 4px 8px; border-radius: 4px; color: #ef4444; font-weight: bold; }
        select { padding: 6px; border-radius: 6px; border: 1px solid #ccc; font-family: inherit; }
    </style>
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<div class="dev-container">
    <div class="dev-header">
        <h1><i class="fas fa-terminal"></i> Developer Control Panel</h1>
        <div>
            <button class="btn-primary" onclick="loadUsers()"><i class="fas fa-sync"></i> Refresh</button>
            <button class="btn-danger" onclick="window.location.href='api/logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <div class="table-container">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Username</th>
                    <th>Password (Raw)</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <tr><td colspan="6" style="text-align:center;">Loading system users...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadUsers);

    async function loadUsers() {
        try {
            const res = await fetch('api/dev_admin.php?action=get_users');
            const users = await res.json();
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';
            
            users.forEach(u => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${u.first_name} ${u.last_name}</td>
                    <td><strong>${u.username}</strong></td>
                    <td><span class="raw-pass">${u.raw_password || 'encrypted'}</span></td>
                    <td><span class="variant-badge">${u.role.toUpperCase()}</span></td>
                    <td>
                        <select onchange="updateUserStatus(${u.user_id}, this.value)">
                            <option value="active" ${u.account_status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${u.account_status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            <option value="suspended" ${u.account_status === 'suspended' ? 'selected' : ''}>Suspended</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn-primary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="resetPassword(${u.user_id})">
                            <i class="fas fa-key"></i> Reset Pass
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (e) { console.error(e); }
    }

    async function updateUserStatus(id, status) {
        await fetch('api/dev_admin.php?action=update_status', {
            method: 'POST', body: JSON.stringify({ user_id: id, status: status })
        });
        showNotification("Account status updated.", "success");
    }

    async function resetPassword(id) {
        const newPass = prompt("Enter the new password for this user:");
        if(!newPass) return;
        
        await fetch('api/dev_admin.php?action=reset_password', {
            method: 'POST', body: JSON.stringify({ user_id: id, new_password: newPass })
        });
        showNotification("Password changed successfully.", "success");
        loadUsers();
    }

    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const notif = document.createElement('div');
        notif.className = `notification ${type}`;
        notif.innerHTML = `<span>${message}</span>`;
        container.appendChild(notif);
        setTimeout(() => { notif.classList.add('hiding'); setTimeout(() => notif.remove(), 400); }, 3000);
    }
</script>
</body>
</html>