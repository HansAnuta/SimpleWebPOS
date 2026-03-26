<?php
session_start(); // THIS MUST BE FIRST
require_once __DIR__ . '/security_headers.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') { 
    header("Location: dev_gate.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pebble Command Center</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="pebble.png">
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button id="sidebar-toggle" class="sidebar-toggle-btn">
            <img src="icons/menu.png" class="ui-icon" alt="menu">
        </button>
        <div class="brand" id="store-brand">
            Pebble Admin
        </div>
    </div>
    
    <ul class="nav-links">
        <li>
            <button class="nav-btn active">
                <img src="icons/username.png" class="ui-icon" alt="accounts"> <span>Accounts</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" id="dev-analytics-btn">
                <img src="icons/revenue.png" class="ui-icon" alt="analytics"> <span>Analytics</span>
            </button>
        </li>
        <li>
            <button class="nav-btn" id="dev-global-config-btn">
                <img src="icons/settings.png" class="ui-icon" alt="global config"> <span>Global Config</span>
            </button>
        </li>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <h4>Developer</h4>
            <small>Super Admin</small>
        </div>
        <button id="logout-btn">
            <img src="icons/logout.png" class="ui-icon" alt="logout">
        </button>
    </div>
</nav>

<div class="main-content">
    <div class="top-header">
        <div class="page-title">Dashboard Overview</div>
        <button class="btn-primary" style="background: var(--danger);" id="dev-terminate-btn">
            <img src="icons/restricted.png" class="ui-icon" alt="terminate"> Terminate
        </button>
    </div>

    <div class="sales-view-content" style="padding: 20px;">
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e0f2fe; color:#0ea5e9;"><img src="icons/home.png" class="ui-icon" alt="stores"></div>
                <div class="stat-info">
                    <h3><span id="stat-managers">0</span></h3>
                    <p>Total Stores</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7; color:#16a34a;"><img src="icons/success.png" class="ui-icon" alt="active"></div>
                <div class="stat-info">
                    <h3><span id="stat-active">0</span></h3>
                    <p>Active Plans</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2; color:#ef4444;"><img src="icons/error.png" class="ui-icon" alt="expired"></div>
                <div class="stat-info">
                    <h3><span id="stat-expired">0</span></h3>
                    <p>Expired</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f3e8ff; color:#a855f7;"><img src="icons/username.png" class="ui-icon" alt="cashiers"></div>
                <div class="stat-info">
                    <h3><span id="stat-cashiers">0</span></h3>
                    <p>Total Cashiers</p>
                </div>
            </div>
        </div>

        <div class="table-container" style="margin-top: 20px;">
            <div style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0; color: var(--text);">Network Deployments</h3>
                <button class="btn-primary" style="background: white; color: var(--text); border: 1px solid var(--border);" id="dev-refresh-users-btn">
                    <img src="icons/refresh.png" class="ui-icon" alt="refresh"> Refresh
                </button>
            </div>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th>Account Detail</th>
                        <th>Credentials</th>
                        <th>SaaS Subscription</th>
                        <th>Status</th>
                        <th style="text-align: right;">Management Tools</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <tr><td colspan="6" style="text-align:center; padding: 40px;">Syncing data with mainframes...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="warning-modal" class="modal">
    <div class="modal-content" style="width: 450px;">
        <div class="modal-header">
            <h2>System Warning</h2>
            <button class="close-btn" id="warning-modal-close-btn">&times;</button>
        </div>
        <div class="form-group">
            <label>Force display message on Manager login:</label>
            <input type="hidden" id="warning-user-id">
            <textarea id="warning-text" rows="4" style="width: 100%; padding: 15px; border-radius: 10px; border: 2px solid var(--border); font-family: inherit; resize: none; outline: none;"></textarea>
        </div>
        <button class="btn-primary" style="width: 100%; margin-top: 15px;" id="send-warning-btn">Deploy Warning Message</button>
    </div>
</div>

<script src="dev_admin.js"></script>
</body>
</html>