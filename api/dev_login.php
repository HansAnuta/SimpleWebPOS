<?php 
session_start();
require_once __DIR__ . '/../security_headers.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->username) || !isset($data->password)) { 
    http_response_code(400); echo json_encode(["message" => "Incomplete details."]); exit; 
}

// Strictly look for developer roles
$sql = "SELECT user_id, first_name, last_name, username, password, role FROM users WHERE username = ? AND role = 'developer'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data->username]);
$user = $stmt->fetch();

if ($user && password_verify($data->password, $user['password'])) {
    // Set secure session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    http_response_code(200);
    echo json_encode(["message" => "Authorized"]);
} else { 
    http_response_code(401); 
    echo json_encode(["message" => "Access Denied."]); 
}
?>