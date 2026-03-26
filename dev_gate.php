<?php 
session_start();
require_once __DIR__ . '/security_headers.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gateway - Pebble POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>System Admin</h1>
            <p>Authorized personnel only.</p>
        </div>
        
        <form id="dev-login-form">
            <div class="form-group">
                <label>Admin ID</label>
                <div class="input-icon-wrapper">
                    <img src="icons/username.png" class="ui-icon" alt="admin id">
                    <input type="text" id="dev-username" required placeholder="Enter system ID" autocomplete="off">
                </div>
            </div>
            
            <div class="form-group">
                <label>Security Key</label>
                <div class="input-icon-wrapper">
                    <img src="icons/password.png" class="ui-icon" alt="security key">
                    <input type="password" id="dev-password" required placeholder="Enter security key">
                </div>
            </div>
            
            <button type="submit" class="btn-auth">
                Authenticate <img src="icons/register.png" class="ui-icon btn-icon-right" alt="authenticate">
            </button>
            
            <div id="error-msg" class="auth-error-msg"></div>
        </form>
        
    </div>
</div>

<script src="auth.js"></script>

</body>
</html>