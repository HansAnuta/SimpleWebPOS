<?php require_once __DIR__ . '/security_headers.php'; ?>
<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

// Security check: Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access. Please log in again."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$user_id = $_SESSION['user_id'];

// Make sure we received the data payload
if (!isset($data->store_name) || !isset($data->store_address) || !isset($data->vat_rate)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete settings data received."]);
    exit;
}

try {
    // 1. Check if settings already exist for this user
    $stmt = $pdo->prepare("SELECT setting_id FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_setting = $stmt->fetch();

    if ($existing_setting) {
        // 2a. UPDATE existing settings
        $updateStmt = $pdo->prepare("UPDATE user_settings SET store_name = ?, store_address = ?, vat_rate = ? WHERE user_id = ?");
        $updateStmt->execute([
            $data->store_name, 
            $data->store_address, 
            $data->vat_rate, 
            $user_id
        ]);
    } else {
        // 2b. INSERT new settings if they don't exist yet
        $insertStmt = $pdo->prepare("INSERT INTO user_settings (user_id, store_name, store_address, vat_rate) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([
            $user_id, 
            $data->store_name, 
            $data->store_address, 
            $data->vat_rate
        ]);
    }

    // Success! Send the green light back to app.js
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Settings saved successfully!"]);

} catch (Exception $e) {
    // Catch database errors to prevent the "500 Internal Server Error" crash
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>