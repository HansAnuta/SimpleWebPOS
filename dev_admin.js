// dev_admin.js

document.addEventListener('DOMContentLoaded', initDeveloperPage);

const DEV_ICON_MAP = {
    success: 'icons/success.png',
    error: 'icons/error.png',
    warning: 'icons/restricted.png',
    clock: 'icons/refresh.png',
    expired: 'icons/restricted.png',
    reset: 'icons/password.png',
    suspend: 'icons/park-order.png',
    restore: 'icons/add-item.png',
    warningQueued: 'icons/error.png'
};

function iconImg(path, alt = 'icon', className = 'ui-icon') {
    return `<img src="${path}" class="${className}" alt="${alt}">`;
}

function initDeveloperPage() {
    loadUsers();

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = 'api/logout.php';
        });
    }

    const terminateBtn = document.getElementById('dev-terminate-btn');
    if (terminateBtn) {
        terminateBtn.addEventListener('click', () => {
            window.location.href = 'api/logout.php';
        });
    }

    const refreshBtn = document.getElementById('dev-refresh-users-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadUsers);
    }

    const analyticsBtn = document.getElementById('dev-analytics-btn');
    if (analyticsBtn) {
        analyticsBtn.addEventListener('click', () => {
            alert('System Analytics coming in next patch!');
        });
    }

    const configBtn = document.getElementById('dev-global-config-btn');
    if (configBtn) {
        configBtn.addEventListener('click', () => {
            alert('Global Settings coming in next patch!');
        });
    }

    const closeWarningBtn = document.getElementById('warning-modal-close-btn');
    if (closeWarningBtn) {
        closeWarningBtn.addEventListener('click', () => {
            document.getElementById('warning-modal').classList.remove('active');
        });
    }

    const sendWarningBtn = document.getElementById('send-warning-btn');
    if (sendWarningBtn) {
        sendWarningBtn.addEventListener('click', sendWarning);
    }

    const usersTableBody = document.getElementById('users-table-body');
    if (usersTableBody) {
        usersTableBody.addEventListener('click', (event) => {
            const actionBtn = event.target.closest('button[data-action]');
            if (!actionBtn) {
                return;
            }

            const action = actionBtn.dataset.action;
            const id = parseInt(actionBtn.dataset.id || '0', 10);

            if (action === 'toggle-cashiers') {
                const managerId = parseInt(actionBtn.dataset.managerId || '0', 10);
                toggleCashiers(managerId, actionBtn);
                return;
            }

            if (action === 'add-time-7') {
                addTime(id, 7);
                return;
            }

            if (action === 'add-time-30') {
                addTime(id, 30);
                return;
            }

            if (action === 'suspend') {
                updateStatus(id, 'suspended');
                return;
            }

            if (action === 'restore') {
                updateStatus(id, 'active');
                return;
            }

            if (action === 'warn') {
                const warning = decodeURIComponent(actionBtn.dataset.warning || '');
                openWarningModal(id, warning);
                return;
            }

            if (action === 'reset-password') {
                const username = actionBtn.dataset.username || 'account';
                const role = actionBtn.dataset.role || 'user';
                resetPassword(id, username, role);
            }
        });
    }
}

// Support for the mobile sidebar toggle (copied from global app.js)
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
if(sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

async function loadUsers() {
    try {
        const res = await fetch('api/dev_admin.php?action=get_users');
        const users = await res.json();
        const tbody = document.getElementById('users-table-body');
        tbody.innerHTML = '';
        
        const managers = users.filter(u => u.role === 'store_admin');
        const cashiers = users.filter(u => u.role === 'cashier');

        // Update Statistics
        let activeCount = 0; let expiredCount = 0;
        const now = new Date();
        managers.forEach(m => {
            if(m.account_status === 'suspended') { expiredCount++; }
            else if (m.subscription_end_date && new Date(m.subscription_end_date) > now) { activeCount++; }
            else { expiredCount++; }
        });

        document.getElementById('stat-managers').innerText = managers.length;
        document.getElementById('stat-cashiers').innerText = cashiers.length;
        document.getElementById('stat-active').innerText = activeCount;
        document.getElementById('stat-expired').innerText = expiredCount;

        managers.forEach(m => {
            const myCashiers = cashiers.filter(c => c.parent_id == m.user_id);
            
            // Subscription Logic
            let subText = "No Active Plan"; let subColor = "var(--danger)"; 
            if (m.subscription_end_date) {
                const endDate = new Date(m.subscription_end_date);
                if (endDate > now) {
                    const diffDays = Math.ceil(Math.abs(endDate - now) / (1000 * 60 * 60 * 24));
                    subText = `${iconImg(DEV_ICON_MAP.clock, 'remaining')} ${diffDays} Days Remaining`;
                    subColor = diffDays <= 7 ? "#f59e0b" : "var(--success)"; 
                } else {
                    subText = `${iconImg(DEV_ICON_MAP.expired, 'expired')} Subscription Expired`;
                }
            }

            const mRow = document.createElement('tr');
            const isSuspended = m.account_status === 'suspended';
            const hasWarning = m.warning_message ? `<span style="margin-left:8px;" title="Warning Queued">${iconImg(DEV_ICON_MAP.warningQueued, 'warning')}</span>` : '';
            const encodedWarning = encodeURIComponent(m.warning_message || '');

            mRow.innerHTML = `
                <td style="text-align:center;">
                    ${myCashiers.length > 0 ? `<button class="dev-toggle-btn" data-action="toggle-cashiers" data-manager-id="${m.user_id}">▸</button>` : ''}
                </td>
                <td data-label="Account">
                    <div style="font-weight: 600; color: var(--text);">${m.first_name} ${m.last_name} ${hasWarning}</div>
                    <small style="color: var(--primary); font-weight: bold;">MANAGER</small>
                </td>
                <td data-label="Credentials">
                    <div style="color: var(--gray); font-size: 0.85rem;">@${m.username}</div>
                </td>
                <td data-label="Subscription">
                    <div style="font-weight: 600; color: ${subColor}; font-size: 0.9rem; margin-bottom: 5px;">${subText}</div>
                    <div style="display:flex; gap:5px;">
                        <button class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem;" data-action="add-time-7" data-id="${m.user_id}">+7D</button>
                        <button class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem;" data-action="add-time-30" data-id="${m.user_id}">+30D</button>
                    </div>
                </td>
                <td data-label="Status">
                    <span style="padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; background: ${isSuspended ? '#fee2e2' : '#dcfce7'}; color: ${isSuspended ? 'var(--danger)' : 'var(--success)'};">${m.account_status.toUpperCase()}</span>
                </td>
                <td data-label="Tools" style="text-align: right;">
                    <button class="btn-primary" style="background: #6b7280; padding: 8px;" title="Reset Password" data-action="reset-password" data-id="${m.user_id}" data-username="${m.username}" data-role="manager">${iconImg(DEV_ICON_MAP.reset, 'reset')}</button>
                    <button class="btn-primary" style="background: #f59e0b; padding: 8px;" title="Send Warning" data-action="warn" data-id="${m.user_id}" data-warning="${encodedWarning}">${iconImg(DEV_ICON_MAP.warning, 'warn')}</button>
                    ${isSuspended 
                        ? `<button class="btn-primary" style="background: var(--success); padding: 8px;" title="Restore Access" data-action="restore" data-id="${m.user_id}">${iconImg(DEV_ICON_MAP.restore, 'restore')}</button>`
                        : `<button class="btn-danger" style="padding: 8px;" title="Suspend Store" data-action="suspend" data-id="${m.user_id}">${iconImg(DEV_ICON_MAP.suspend, 'suspend')}</button>`
                    }
                </td>
            `;
            tbody.appendChild(mRow);

            myCashiers.forEach(c => {
                const cRow = document.createElement('tr');
                cRow.className = `cashier-row child-of-${m.user_id}`;
                const cSuspended = c.account_status === 'suspended';

                cRow.innerHTML = `
                    <td></td>
                    <td data-label="Account">
                        <div style="font-weight: 500; color: var(--text); font-size: 0.9rem;"><span style="color:var(--border); margin-right:5px;">↳</span> ${c.first_name} ${c.last_name}</div>
                    </td>
                    <td data-label="Credentials">
                        <div style="color: var(--gray); font-size: 0.8rem;">@${c.username}</div>
                    </td>
                    <td data-label="Subscription"><span style="color: var(--border);">—</span></td>
                    <td data-label="Status"><span style="font-size: 0.8rem; color: ${cSuspended ? 'var(--danger)' : 'var(--gray)'};">${c.account_status.toUpperCase()}</span></td>
                    <td data-label="Tools" style="text-align: right;">
                        <button class="btn-primary" style="background: var(--gray); padding: 6px 10px;" title="Reset Password" data-action="reset-password" data-id="${c.user_id}" data-username="${c.username}" data-role="cashier">${iconImg(DEV_ICON_MAP.reset, 'reset')}</button>
                    </td>
                `;
                tbody.appendChild(cRow);
            });
        });
    } catch (e) { console.error(e); }
}

function toggleCashiers(managerId, btn) {
    const rows = document.querySelectorAll(`.child-of-${managerId}`);
    if (!rows.length) {
        return;
    }

    const isHidden = !rows[0].classList.contains('show-cashier');
    
    rows.forEach(r => {
        if (isHidden) r.classList.add('show-cashier');
        else r.classList.remove('show-cashier');
    });
    
    btn.textContent = isHidden ? '▾' : '▸';
}

async function updateStatus(id, status) {
    if (status === 'suspended' && !confirm("Suspend this store? All associated cashiers will be locked out instantly.")) return;
    await fetch('api/dev_admin.php?action=update_status', { method: 'POST', body: JSON.stringify({ user_id: id, status: status }) });
    showNotification(status === 'active' ? "Store restored." : "Store suspended.", "success"); loadUsers();
}

async function addTime(id, days) {
    if(!confirm(`Extend subscription by ${days} days?`)) return;
    await fetch('api/dev_admin.php?action=update_subscription', { method: 'POST', body: JSON.stringify({ user_id: id, add_days: days }) });
    showNotification(`Subscription extended by ${days} days!`, "success"); loadUsers();
}

function openWarningModal(id, currentMsg) {
    document.getElementById('warning-user-id').value = id;
    document.getElementById('warning-text').value = currentMsg || '';
    document.getElementById('warning-modal').classList.add('active');
}

async function sendWarning() {
    const id = document.getElementById('warning-user-id').value;
    const msg = document.getElementById('warning-text').value;
    await fetch('api/dev_admin.php?action=send_warning', { method: 'POST', body: JSON.stringify({ user_id: id, message: msg }) });
    showNotification("Warning deployed to store.", "success");
    document.getElementById('warning-modal').classList.remove('active'); loadUsers();
}

async function resetPassword(id, username, role) {
    const roleLabel = role === 'manager' ? 'manager' : 'cashier';
    if (!confirm(`Reset password for ${roleLabel} @${username}?`)) {
        return;
    }

    const res = await fetch('api/dev_admin.php?action=reset_password', { method: 'POST', body: JSON.stringify({ user_id: id }) });
    const result = await res.json();
    if (!res.ok || !result.success) {
        showNotification(result.error || 'Reset failed', 'error');
        return;
    }
    alert(`Temporary password for @${username}: ${result.temporary_password}`);
    showNotification(`Password reset for @${username}.`, "success");
    loadUsers();
}

function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    if(!container) return;
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    const iconPath = type === 'success' ? DEV_ICON_MAP.success : DEV_ICON_MAP.error;
    notif.innerHTML = `${iconImg(iconPath, type)}<span>${message}</span>`;
    container.appendChild(notif);
    setTimeout(() => { notif.classList.add('hiding'); setTimeout(() => notif.remove(), 400); }, 3000);
}