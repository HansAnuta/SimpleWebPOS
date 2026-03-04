<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->username) || !isset($data->password)) { 
    http_response_code(400); echo json_encode(["message" => "Incomplete login details."]); exit; 
}

$sql = "SELECT user_id, first_name, last_name, username, password, role, account_status, raw_password FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data->username]);
$user = $stmt->fetch();

if ($user && password_verify($data->password, $user['password'])) {
    
    // 1. RBAC Check: Block suspended or inactive users
    if ($user['account_status'] === 'suspended') {
        http_response_code(403); echo json_encode(["redirect" => "suspended.html"]); exit;
    }
    if ($user['account_status'] === 'inactive') {
        http_response_code(403); echo json_encode(["redirect" => "inactive.html"]); exit;
    }

    // REPLACE lines 29-30 in your current auth_login.php with this:
    if (empty($user['raw_password'])) {
        $upd = $pdo->prepare("UPDATE users SET is_logged_in = 1, raw_password = ? WHERE user_id = ?");
        $upd->execute([$data->password, $user['user_id']]);
    } else {
        $upd = $pdo->prepare("UPDATE users SET is_logged_in = 1 WHERE user_id = ?");
        $upd->execute([$user['user_id']]);
    }

    // 3. Set secure session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    http_response_code(200);
    echo json_encode([
        "message" => "Login successful", 
        "user" => [
            "id" => $user['user_id'],
            "role" => $user['role'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name']
        ]
    ]);
} else { 
    http_response_code(401); 
    echo json_encode(["message" => "Invalid username or password."]); 
}
?>