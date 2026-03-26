<?php require_once __DIR__ . '/security_headers.php'; ?>
<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(["success" => false, "message" => "Session expired"]); 
    exit; 
}

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->password)) { 
    http_response_code(400); 
    echo json_encode(["success" => false, "message" => "Password required"]); 
    exit; 
}

try {
    // 1. Find the current user
    $stmt = $pdo->prepare("SELECT role, parent_id, password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $hash_to_check = '';

    // 2. Determine whose password we are checking against
    if ($user['role'] === 'store_admin' || $user['role'] === 'developer') {
        // If Admin or Dev, check against their own password
        $hash_to_check = $user['password'];
    } else if ($user['role'] === 'cashier') {
        // If Cashier, pull their Store Admin's password
        $stmt_admin = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt_admin->execute([$user['parent_id']]);
        $admin = $stmt_admin->fetch();
        if ($admin) {
            $hash_to_check = $admin['password'];
        }
    }

    // 3. Verify
    if (password_verify($data->password, $hash_to_check)) { 
        echo json_encode(["success" => true]); 
    } else { 
        echo json_encode(["success" => false, "message" => "Incorrect Admin Password"]); 
    }
} catch (Exception $e) { 
    http_response_code(500); 
    echo json_encode(["error" => $e->getMessage()]); 
}
?>