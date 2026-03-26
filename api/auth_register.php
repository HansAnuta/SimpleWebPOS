<?php require_once __DIR__ . '/security_headers.php'; ?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
include_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->first_name) || !isset($data->last_name) || !isset($data->username) || !isset($data->password)) { 
    http_response_code(400); 
    echo json_encode(["message" => "Incomplete data."]); 
    exit; 
}

// Check if username exists
$check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$check->execute([$data->username]);
if ($check->rowCount() > 0) { 
    http_response_code(409); 
    echo json_encode(["message" => "Username already exists."]); 
    exit; 
}

$password_hash = password_hash($data->password, PASSWORD_DEFAULT);

try {
    // 1 & 2. Insert new user explicitly as 'store_admin' using hashed password only
    $sql = "INSERT INTO users (first_name, last_name, username, password, role, account_status) VALUES (?, ?, ?, ?, 'store_admin', 'active')";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$data->first_name, $data->last_name, $data->username, $password_hash])) { 
        
        // 3. Get the new User ID and create default store settings to prevent crashes
        $new_user_id = $pdo->lastInsertId();
        $setting_stmt = $pdo->prepare("INSERT INTO user_settings (user_id, vat_rate, store_name, store_address) VALUES (?, 12.00, 'My New Store', 'Pending Address')");
        $setting_stmt->execute([$new_user_id]);

        http_response_code(201); 
        echo json_encode(["message" => "Store Admin registered successfully."]); 
    } else { 
        http_response_code(500); 
        echo json_encode(["message" => "Unable to register user."]); 
    }
} catch (PDOException $e) {
    error_log('Registration Error: ' . $e->getMessage());
    http_response_code(500); 
    echo json_encode(["message" => "Database error during registration."]);
}
?>