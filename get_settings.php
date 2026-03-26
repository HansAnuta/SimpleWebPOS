<?php require_once __DIR__ . '/security_headers.php'; ?>
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

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // Create default row if it doesn't exist
        $insert = $pdo->prepare("INSERT INTO user_settings (user_id, vat_rate, store_name, store_address) VALUES (?, 12, 'SimplePOS', 'Philippines')");
        $insert->execute([$user_id]);
        
        echo json_encode([
            "vat_rate" => 12,
            "store_name" => "SimplePOS",
            "store_address" => "Philippines"
        ]);
    } else {
        // Ensure no nulls are sent to frontend
        $response = [
            "vat_rate" => $settings['vat_rate'] ?? 12,
            "store_name" => $settings['store_name'] ?? "SimplePOS",
            "store_address" => $settings['store_address'] ?? "Philippines"
        ];
        echo json_encode($response);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>