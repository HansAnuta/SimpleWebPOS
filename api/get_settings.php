<?php
session_start();
// Prevent Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    // 1. Find the current user to check their role
    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();

    if (!$u) throw new Exception("User not found.");

    // 2. If Store Admin, use their own ID. If Cashier, use their parent's ID.
    $admin_id = ($u['role'] === 'store_admin') ? $_SESSION['user_id'] : $u['parent_id'];

    // 3. Fetch the settings for that Admin
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$admin_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($settings) {
        // Send the Admin's real settings
        echo json_encode([
            "vat_rate" => $settings['vat_rate'] ?? 12,
            "store_name" => $settings['store_name'] ?? "SimplePOS",
            "store_address" => $settings['store_address'] ?? "Philippines"
        ]);
    } else {
        // Fallback only if the Admin somehow has no settings
        echo json_encode([
            "vat_rate" => 12,
            "store_name" => "SimplePOS",
            "store_address" => "Philippines"
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>