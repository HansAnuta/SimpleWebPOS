<?php
require_once __DIR__ . '/security_headers.php';
session_start();
include_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    // Set user to offline
    $stmt = $pdo->prepare("UPDATE users SET is_logged_in = 0 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_unset();
session_destroy();
header("Location: ../login.php");
exit();
?>