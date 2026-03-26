<?php require_once __DIR__ . '/security_headers.php'; ?>
<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); 
    echo json_encode(["error" => "Unauthorized"]); 
    exit;
}

// Block Manager (store_admin) from processing transactions
if ($_SESSION['role'] === 'store_admin') {
    http_response_code(403);
    echo json_encode(["message" => "Managers are not allowed to process transactions."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->order_id) || !isset($data->status)) {
    http_response_code(400); 
    echo json_encode(["error" => "Missing data"]); 
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    $stmt_check = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ? AND (user_id = ? OR user_id IN (SELECT user_id FROM users WHERE parent_id = ?))");
    $stmt_check->execute([$data->order_id, $user_id, $user_id]);
    if (!$stmt_check->fetch()) {
        http_response_code(403);
        echo json_encode(["error" => "Order not found or access denied"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$data->status, $data->order_id]);
    
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(["error" => $e->getMessage()]);
}
?>