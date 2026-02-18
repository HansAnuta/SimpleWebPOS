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

$data = json_decode(file_get_contents("php://input"));
if (!isset($data->product_id)) { 
    http_response_code(400); 
    echo json_encode(["message" => "ID required"]); 
    exit; 
}

try {
    $user_id = $_SESSION['user_id'];
    $product_id = $data->product_id;

    // 1. Get Product Info BEFORE deletion to grab the Category ID
    $stmt = $pdo->prepare("SELECT image, category_id FROM products WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch();

    if (!$product) throw new Exception("Product not found or access denied.");

    $category_id = $product['category_id'];

    // 2. Delete the Product Record
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // 3. Delete Image File
    if ($product['image'] && file_exists("../" . $product['image'])) {
        unlink("../" . $product['image']);
    }

    // 4. FIX: Check if category is empty FOR THIS USER ONLY
    if ($category_id) {
        // STRICT COUNT: Only count products belonging to the current user
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND user_id = ?");
        $stmtCheck->execute([$category_id, $user_id]);
        $remaining_items = $stmtCheck->fetchColumn();

        if ($remaining_items == 0) {
            // Category is empty for this user, delete it securely
            $stmtDelCat = $pdo->prepare("DELETE FROM categories WHERE category_id = ? AND user_id = ?");
            $stmtDelCat->execute([$category_id, $user_id]);
        }
    }

    echo json_encode(["message" => "Deleted successfully"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
// No closing PHP tag