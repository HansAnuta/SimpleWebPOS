<?php
session_start();
header("Content-Type: application/json");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_admin') {
    http_response_code(403); exit;
}

$data = json_decode(file_get_contents("php://input"));

try {
    // Because orders only save the text name of the discount, it's 100% safe to hard-delete this.
    $stmt = $pdo->prepare("DELETE FROM discounts WHERE discount_id=? AND user_id=?");
    $stmt->execute([$data->id, $_SESSION['user_id']]);
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => $e->getMessage()]);
}
?>