<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->username) || !isset($data->password)) { http_response_code(400); echo json_encode(["message" => "Incomplete login details."]); exit; }
$sql = "SELECT user_id, first_name, last_name, username, password, role FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data->username]);
$user = $stmt->fetch();
if ($user && password_verify($data->password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    unset($user['password']);
    http_response_code(200);
    echo json_encode(["message" => "Login successful", "user" => $user]);
} else { http_response_code(401); echo json_encode(["message" => "Invalid username or password."]); }
?>