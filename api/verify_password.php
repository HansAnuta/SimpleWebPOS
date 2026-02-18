<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(["success" => false, "message" => "Session expired"]); exit; }
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->password)) { http_response_code(400); echo json_encode(["success" => false, "message" => "Password required"]); exit; }
try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user && password_verify($data->password, $user['password'])) { echo json_encode(["success" => true]); } 
    else { echo json_encode(["success" => false, "message" => "Incorrect password"]); }
} catch (Exception $e) { http_response_code(500); echo json_encode(["error" => $e->getMessage()]); }
?>