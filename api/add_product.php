<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

// 1. Get Data
$name = $_POST['name'] ?? null;
$category_name = $_POST['category_name'] ?? null;
$price = $_POST['price'] ?? 0;
// NEW: Get Product Code (Optional)
$product_code = $_POST['product_code'] ?? null; 
// If empty string sent, make it null
if ($product_code === '') $product_code = null;

$variants_json = $_POST['variants'] ?? '[]';
$variants = json_decode($variants_json);

if (!$name || !$category_name) {
    http_response_code(400);
    echo json_encode(["message" => "Name and Category are required."]);
    exit;
}

// 2. Handle Image Upload
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array(strtolower($file_ext), $allowed_types)) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = "uploads/" . $new_filename;
        }
    }
}

try {
    $pdo->beginTransaction();
    $user_id = $_SESSION['user_id'];

    // 3. Category Logic
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND user_id = ?");
    $stmt->execute([$category_name, $user_id]);
    $cat = $stmt->fetch();

    if ($cat) {
        $category_id = $cat['category_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
        $stmt->execute([$category_name, $user_id]);
        $category_id = $pdo->lastInsertId();
    }

    // 4. Insert Main Product
    $has_variants = (count($variants) > 0) ? 1 : 0;
    $base_price = $has_variants ? 0 : $price;
    
    // UPDATED SQL: Added product_code
    $sql = "INSERT INTO products (user_id, category_id, name, product_code, price, has_variants, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $category_id, $name, $product_code, $base_price, $has_variants, $image_path]);
    $product_id = $pdo->lastInsertId();

    // 5. Insert Variants
    if ($has_variants) {
        $sql_var = "INSERT INTO product_variants (product_id, variant_name, price) VALUES (?, ?, ?)";
        $stmt_var = $pdo->prepare($sql_var);

        foreach ($variants as $variant) {
            if(!empty($variant->name)) {
                $stmt_var->execute([
                    $product_id,
                    $variant->name,
                    $variant->price
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["message" => "Product saved successfully"]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>