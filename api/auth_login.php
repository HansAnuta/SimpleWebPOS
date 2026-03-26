<?php
session_start();
require_once __DIR__ . '/../security_headers.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->username) || !isset($data->password)) { 
    http_response_code(400); echo json_encode(["message" => "Incomplete login details."]); exit; 
}

// Fetch only hashed credentials and account metadata
$sql = "SELECT user_id, first_name, last_name, username, password, role, account_status, parent_id, subscription_end_date FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data->username]);
$user = $stmt->fetch();

if ($user && password_verify($data->password, $user['password'])) {
    
    // Prevent developers from logging in via the public POS portal
    if ($user['role'] === 'developer') {
        http_response_code(403);
        echo json_encode(["message" => "System Administrators must use the designated access gate."]);
        exit;
    }

    // 1. RBAC Check: Block suspended or inactive users
    if ($user['account_status'] === 'suspended') {
        http_response_code(403); echo json_encode(["redirect" => "suspended.php"]); exit;
    }
    if ($user['account_status'] === 'inactive') {
        http_response_code(403); echo json_encode(["redirect" => "inactive.php"]); exit;
    }

    // 2. SaaS Subscription Check
    $is_expired = false;
    $now = new DateTime();

    if ($user['role'] === 'store_admin') {
        $end_date = $user['subscription_end_date'];
        // If they have no date, or the date has passed, they are expired
        if (!$end_date || new DateTime($end_date) < $now) {
            $is_expired = true;
        }
    } else if ($user['role'] === 'cashier') {
        // If it's a cashier, check their Manager's subscription
        $stmt_parent = $pdo->prepare("SELECT subscription_end_date FROM users WHERE user_id = ?");
        $stmt_parent->execute([$user['parent_id']]);
        $parent = $stmt_parent->fetch();
        $end_date = $parent ? $parent['subscription_end_date'] : null;
        
        if (!$end_date || new DateTime($end_date) < $now) {
            $is_expired = true;
        }
    }

    // Redirect to the landing page with a URL parameter if expired
    if ($is_expired) {
        http_response_code(403); 
        echo json_encode(["redirect" => "landing.php?expired=true"]); 
        exit;
    }

    // 3. Mark User as Online
    $upd = $pdo->prepare("UPDATE users SET is_logged_in = 1 WHERE user_id = ?");
    $upd->execute([$user['user_id']]);

    // 4. Set secure session
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