<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    // 1. Find the current user and their Admin ID
    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();

    if (!$u) throw new Exception("User not found.");

    // If Store Admin, use their own ID. If Cashier, use their parent's ID.
    $admin_id = ($u['role'] === 'store_admin') ? $_SESSION['user_id'] : $u['parent_id'];

    // 2. Fetch discounts created by the Admin
    $sql = "SELECT * FROM discounts WHERE user_id = ? ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id]);
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($discounts ?: []);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>