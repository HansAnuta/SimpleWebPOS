<?php
session_start();
require_once __DIR__ . '/security_headers.php'; 
include_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403); 
    echo json_encode(["error" => "Unauthorized access."]); 
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"));

if ($action === 'get_users') {
    // Fetch all users including the new subscription and warning columns
    $stmt = $pdo->query("SELECT user_id, first_name, last_name, username, role, account_status, parent_id, subscription_end_date, warning_message FROM users WHERE role != 'developer' ORDER BY role DESC, first_name ASC");
    echo json_encode($stmt->fetchAll());
} 
elseif ($action === 'update_status') {
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
    $stmt->execute([$data->status, $data->user_id]);
    echo json_encode(["success" => true]);
}
elseif ($action === 'reset_password') {
    if (!isset($data->user_id)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Missing user id"]);
        exit;
    }

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $temporary_password = '';
    for ($i = 0; $i < 12; $i++) {
        $temporary_password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    $hashed = password_hash($temporary_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashed, $data->user_id]);
    echo json_encode(["success" => true, "temporary_password" => $temporary_password]);
}
elseif ($action === 'update_subscription') {
    // Add time to the subscription
    $stmt = $pdo->prepare("SELECT subscription_end_date FROM users WHERE user_id = ?");
    $stmt->execute([$data->user_id]);
    $user = $stmt->fetch();
    
    $current_end = $user['subscription_end_date'];
    $now = new DateTime();
    
    // If they have no sub, or it's already expired, start adding time from TODAY
    if (!$current_end || new DateTime($current_end) < $now) {
        $base_date = $now;
    } else {
        // If they still have active time, add it to their existing expiration date
        $base_date = new DateTime($current_end);
    }
    
    $base_date->modify("+" . (int)$data->add_days . " days");
    $new_date_str = $base_date->format('Y-m-d H:i:s');
    
    $update = $pdo->prepare("UPDATE users SET subscription_end_date = ? WHERE user_id = ?");
    $update->execute([$new_date_str, $data->user_id]);
    
    echo json_encode(["success" => true, "new_date" => $new_date_str]);
}
elseif ($action === 'send_warning') {
    // Save the warning message for the popup
    $stmt = $pdo->prepare("UPDATE users SET warning_message = ? WHERE user_id = ?");
    $stmt->execute([$data->message, $data->user_id]);
    echo json_encode(["success" => true]);
}
?>