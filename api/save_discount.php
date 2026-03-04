<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_admin') {
    http_response_code(403); exit;
}

$data = json_decode(file_get_contents("php://input"));

try {
    $stmt = $pdo->prepare("INSERT INTO discounts (user_id, name, type, value, min_spend, cap_amount, is_vat_exempt, requires_customer_info, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        $_SESSION['user_id'], 
        $data->name, 
        $data->type, 
        $data->value, 
        $data->min_spend, 
        $data->cap_amount, 
        $data->is_vat_exempt, 
        $data->requires_customer_info
    ]);
    
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => $e->getMessage()]);
}
?>