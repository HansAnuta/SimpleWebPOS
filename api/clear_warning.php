<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) { exit; }

// Clear the warning message for the logged-in user
$stmt = $pdo->prepare("UPDATE users SET warning_message = NULL WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

echo json_encode(["success" => true]);
?>