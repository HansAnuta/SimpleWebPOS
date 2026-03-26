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

$product_id = $_POST['product_id'] ?? null;
$name = $_POST['name'] ?? null;
$type = $_POST['type'] ?? 'product';
$category_name = $_POST['category_name'] ?? null;

// FIX 1: Prevent Database Crash by ensuring price is strictly a number
$price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : 0; 
$product_code = $_POST['product_code'] ?? null; 
if ($product_code === '') $product_code = null;

$variants_json = $_POST['variants'] ?? '[]';
$variants = json_decode($variants_json);

$has_variants = (is_array($variants) && count($variants) > 0) ? 1 : 0;
$base_price = $has_variants ? 0 : $price; 

if (!$product_id || trim($name) === '' || trim($category_name) === '') {
    http_response_code(400);
    echo json_encode(["message" => "Missing data! ID: $product_id, Name: $name, Category: $category_name"]);
    exit;
}

try {
    $pdo->beginTransaction();
    $user_id = $_SESSION['user_id'];

    // FIX 2: Fetch the old image AND old category BEFORE we update the record
    $stmt_old = $pdo->prepare("SELECT image, category_id FROM products WHERE product_id = ? AND user_id = ?");
    $stmt_old->execute([$product_id, $user_id]);
    $old_product = $stmt_old->fetch();
    
    $old_image = $old_product ? $old_product['image'] : null;
    $old_category_id = $old_product ? $old_product['category_id'] : null;
    $image_to_delete = null;

    // 1. Get or Create NEW Category
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND user_id = ? AND type = ?");
    $stmt->execute([$category_name, $user_id, $type]);
    $cat = $stmt->fetch();

    if ($cat) {
        $new_category_id = $cat['category_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, user_id, type) VALUES (?, ?, ?)");
        $stmt->execute([$category_name, $user_id, $type]);
        $new_category_id = $pdo->lastInsertId();
    }

    // 2. Handle optional Image replacement
    $image_query = "";
    $params = [$new_category_id, $name, $product_code, $base_price, $has_variants];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_filename)) {
            $image_query = ", image = ?";
            $params[] = "uploads/" . $new_filename;
            
            // Mark the old image to be deleted since a new one was successfully uploaded
            $image_to_delete = $old_image; 
        }
    }

    // Append WHERE clause variables
    $params[] = $product_id;
    $params[] = $user_id; // Security check: Only the owner can update this

    // 3. Update the Product
    $sql = "UPDATE products SET category_id = ?, name = ?, product_code = ?, price = ?, has_variants = ? $image_query WHERE product_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 4. Reset and Re-insert Variants
    $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);
    
    if ($has_variants) {
        $sql_var = "INSERT INTO product_variants (product_id, variant_name, price) VALUES (?, ?, ?)";
        $stmt_var = $pdo->prepare($sql_var);
        foreach ($variants as $variant) {
            if(!empty($variant->name)) {
                $stmt_var->execute([$product_id, $variant->name, (float)$variant->price]);
            }
        }
    }

    // FIX 4: Clean up old category if it is now empty!
    if ($old_category_id && $old_category_id != $new_category_id) {
        // Count how many products are still using the old category
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_check->execute([$old_category_id]);
        $count = $stmt_check->fetchColumn();
        
        if ($count == 0) {
            // If zero products use it, delete the category entirely
            $stmt_del_cat = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt_del_cat->execute([$old_category_id]);
        }
    }

    // Lock in all the database changes safely
    $pdo->commit();

    // FIX 3: Actually delete the physical old image file ONLY AFTER the database saves successfully
    if ($image_to_delete && file_exists("../" . $image_to_delete)) {
        unlink("../" . $image_to_delete);
    }

    echo json_encode(["message" => "Product updated successfully"]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>