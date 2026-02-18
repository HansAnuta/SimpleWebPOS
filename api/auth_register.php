<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->first_name) || !isset($data->last_name) || !isset($data->username) || !isset($data->password)) { http_response_code(400); echo json_encode(["message" => "Incomplete data."]); exit; }
$check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$check->execute([$data->username]);
if ($check->rowCount() > 0) { http_response_code(409); echo json_encode(["message" => "Username already exists."]); exit; }
$sql = "INSERT INTO users (first_name, last_name, username, password) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$password_hash = password_hash($data->password, PASSWORD_DEFAULT);
if ($stmt->execute([$data->first_name, $data->last_name, $data->username, $password_hash])) { http_response_code(201); echo json_encode(["message" => "User registered successfully."]); }
else { http_response_code(500); echo json_encode(["message" => "Unable to register user."]); }
?>