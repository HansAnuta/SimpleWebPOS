<?php require_once __DIR__ . '/security_headers.php'; ?>
<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch current user status
$sql = "SELECT account_status, warning_message, parent_id, subscription_end_date FROM users WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$response = [
    "status" => $user['account_status'],
    "warning" => $user['warning_message'],
    "expired" => false
];

$now = new DateTime();

// SaaS Expiration & Parent Suspension Logic
if ($role === 'store_admin') {
    if (!$user['subscription_end_date'] || new DateTime($user['subscription_end_date']) < $now) {
        $response['expired'] = true;
    }
} else if ($role === 'cashier') {
    // If it's a cashier, check their Manager's status as well!
    $stmt_p = $pdo->prepare("SELECT account_status, subscription_end_date FROM users WHERE user_id = ?");
    $stmt_p->execute([$user['parent_id']]);
    $parent = $stmt_p->fetch(PDO::FETCH_ASSOC);
    
    if ($parent) {
        if ($parent['account_status'] === 'suspended') {
            $response['status'] = 'suspended'; // Force suspend cashier
        }
        if (!$parent['subscription_end_date'] || new DateTime($parent['subscription_end_date']) < $now) {
            $response['expired'] = true;
        }
    }
}

echo json_encode($response);
?>