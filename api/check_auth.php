<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, role FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                "authenticated" => true,
                "user" => [
                    "id" => $user['user_id'],
                    "first_name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "role" => $user['role']
                ]
            ]);
        } else {
            session_unset();
            session_destroy();
            http_response_code(401);
            echo json_encode(["authenticated" => false]);
        }
    } catch (Exception $e) { http_response_code(500); }
} else {
    http_response_code(401);
    echo json_encode(["authenticated" => false]);
}
?>