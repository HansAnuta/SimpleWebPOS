<?php require_once __DIR__ . '/security_headers.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="pebble.png">
</head>
<body>

<div id="notification-container" class="notification-container"></div>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Point of Sale</h1>
            <p>Please login to continue.</p>
        </div>
        
        <form id="login-form">
            <div class="form-group">
                <label>Username</label>
                <div class="input-icon-wrapper">
                    <img src="icons/username.png" class="ui-icon" alt="username">
                    <input type="text" id="username" required placeholder="Enter your username">
                </div>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon-wrapper">
                    <img src="icons/password.png" class="ui-icon" alt="password">
                    <input type="password" id="password" required placeholder="Enter your password">
                </div>
            </div>
            
            <button type="submit" class="btn-auth">
                Login <span class="btn-icon-right">&rarr;</span>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
    </div>
</div>

<script src="auth.js"></script>

</body>
</html>