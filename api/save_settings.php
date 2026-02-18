<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"));

// Default values if frontend sends empty
$vat = $data->vat_rate ?? 12;
$name = $data->store_name ?? 'SimplePOS';
$addr = $data->store_address ?? 'Philippines';

try {
    // Check if settings exist
    $check = $pdo->prepare("SELECT setting_id FROM user_settings WHERE user_id = ?");
    $check->execute([$user_id]);
    $exists = $check->fetch();

    if ($exists) {
        $sql = "UPDATE user_settings SET vat_rate = ?, store_name = ?, store_address = ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vat, $name, $addr, $user_id]);
    } else {
        $sql = "INSERT INTO user_settings (user_id, vat_rate, store_name, store_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $vat, $name, $addr]);
    }

    echo json_encode(["message" => "Settings updated"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>