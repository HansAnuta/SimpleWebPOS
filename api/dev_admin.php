<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

// Security Gate: Strictly Developer Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403); 
    echo json_encode(["error" => "Unauthorized access."]); 
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"));

if ($action === 'get_users') {
    // Exclude developers from the list so you don't accidentally suspend yourself
    $stmt = $pdo->query("SELECT user_id, first_name, last_name, username, role, account_status, raw_password FROM users WHERE role != 'developer'");
    echo json_encode($stmt->fetchAll());
} 
elseif ($action === 'update_status') {
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
    $stmt->execute([$data->status, $data->user_id]);
    echo json_encode(["success" => true]);
}
elseif ($action === 'reset_password') {
    $hashed = password_hash($data->new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, raw_password = ? WHERE user_id = ?");
    $stmt->execute([$hashed, $data->new_password, $data->user_id]);
    echo json_encode(["success" => true]);
}
?>