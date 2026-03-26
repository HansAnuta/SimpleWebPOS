<?php require_once __DIR__ . '/security_headers.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pebble POS | Smart Retail Management</title>
    <link rel="icon" type="image/png" href="pebble.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/public_pages.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#800000">
    <link rel="apple-touch-icon" href="pebble.png">
</head>
<body class="public-landing">

    <div id="expired-banner">
        <img src="icons/error.png" class="ui-icon" alt="warning"> Your store's subscription is inactive or has expired. Please contact support to restore access.
    </div>

    <nav>
        <div class="logo">
            <img src="icons/home.png" class="ui-icon" alt="pebble"> Pebble
        </div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="signup.php">Sign Up</a>
            <a href="login.php" class="btn-login">Login</a>
        </div>
    </nav>

    <div class="hero">
        <h1>Run your retail business smoother than ever.</h1>
        <p>Pebble POS is the ultimate cloud-based point of sale system designed specifically for modern retail stores and coffee shops. Manage sales, track inventory, and grow your business.</p>
        <div class="hero-btns">
            <a href="signup.php" class="btn-primary">Start Your Free Trial</a>
            <a href="#contact" class="btn-secondary">Contact Sales</a>
        </div>
    </div>

    <div id="features" class="features">
        <div class="feature-card">
            <img src="icons/register.png" class="ui-icon" alt="checkout">
            <h3>Lightning Fast Checkout</h3>
            <p>Process walk-ins, take-outs, and deliveries in seconds. Apply custom discounts and split payments with ease.</p>
        </div>
        <div class="feature-card">
            <img src="icons/revenue.png" class="ui-icon" alt="analytics">
            <h3>Real-Time Analytics</h3>
            <p>Access your sales history, calculate VAT, and track your revenue instantly from any device, anywhere.</p>
        </div>
        <div class="feature-card">
            <img src="icons/settings.png" class="ui-icon" alt="staff management">
            <h3>Staff Management</h3>
            <p>Create dedicated cashier accounts, lock down sensitive actions, and track who handled which transaction.</p>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Pebble POS System. All rights reserved.</p>
    </footer>

    <script src="assets/js/landing.js"></script>
</body>
</html>