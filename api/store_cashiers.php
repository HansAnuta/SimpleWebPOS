<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

// Security: Only Store Admins can manage cashiers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_admin') {
    http_response_code(403); echo json_encode(["error" => "Unauthorized"]); exit;
}

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"));

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, raw_password, account_status, is_logged_in FROM users WHERE parent_id = ? AND account_status != 'deleted'");
    $stmt->execute([$admin_id]);
    echo json_encode($stmt->fetchAll());
} 
elseif ($action === 'add') {
    // Check if username exists
    $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->execute([$data->username]);
    if ($check->rowCount() > 0) { http_response_code(409); echo json_encode(["success"=>false, "message" => "Username taken."]); exit; }

    $hashed = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, password, raw_password, role, parent_id, account_status) VALUES (?, ?, ?, ?, ?, 'cashier', ?, 'active')");
    $stmt->execute([$data->first_name, $data->last_name, $data->username, $hashed, $data->password, $admin_id]);
    echo json_encode(["success" => true]);
}
elseif ($action === 'edit') {
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ? AND parent_id = ?");
    $stmt->execute([$data->first_name, $data->last_name, $data->user_id, $admin_id]);
    echo json_encode(["success" => true]);
}
elseif ($action === 'reset_password') {
    $hashed = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, raw_password = ? WHERE user_id = ? AND parent_id = ?");
    $stmt->execute([$hashed, $data->password, $data->user_id, $admin_id]);
    echo json_encode(["success" => true]);
}
elseif ($action === 'update_status') {
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ? AND parent_id = ?");
    $stmt->execute([$data->status, $data->user_id, $admin_id]);
    echo json_encode(["success" => true]);
}
?>