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
$product_id = $_POST['product_id'] ?? null;
$name = $_POST['name'] ?? null;
$category_name = $_POST['category_name'] ?? null;
$price = $_POST['price'] ?? 0;
$product_code = $_POST['product_code'] ?? null;
if ($product_code === '') $product_code = null;

$variants_json = $_POST['variants'] ?? '[]';
$variants = json_decode($variants_json);

if (!$product_id || !$name || !$category_name) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields"]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $pdo->beginTransaction();

    // --- STEP A: CAPTURE OLD CATEGORY (Before Update) ---
    // We need to know where the product WAS so we can clean up if it leaves.
    $stmtOld = $pdo->prepare("SELECT category_id FROM products WHERE product_id = ? AND user_id = ?");
    $stmtOld->execute([$product_id, $user_id]);
    $old_data = $stmtOld->fetch();
    $old_category_id = $old_data['category_id'] ?? null;
    // ----------------------------------------------------

    // 2. Handle Image
    $image_query_part = "";
    $params = [$name, $product_code, $price]; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../uploads/";
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $file_ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_filename)){
            $image_query_part = ", image = ?";
            $params[] = "uploads/" . $new_filename; 
        }
    }

    // 3. Handle Category (Find or Create NEW Category)
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND user_id = ?");
    $stmt->execute([$category_name, $user_id]);
    $cat = $stmt->fetch();
    if ($cat) {
        $new_category_id = $cat['category_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
        $stmt->execute([$category_name, $user_id]);
        $new_category_id = $pdo->lastInsertId();
    }

    // Add remaining params
    $params[] = $new_category_id;
    $has_variants = (count($variants) > 0) ? 1 : 0;
    $params[] = $has_variants;
    $params[] = $product_id;
    $params[] = $user_id;

    // 4. Update Product
    $sql = "UPDATE products SET name = ?, product_code = ?, price = ? $image_query_part, category_id = ?, has_variants = ? WHERE product_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 5. Update Variants
    $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);

    if ($has_variants) {
        $sql_var = "INSERT INTO product_variants (product_id, variant_name, price) VALUES (?, ?, ?)";
        $stmt_var = $pdo->prepare($sql_var);
        foreach ($variants as $variant) {
            if(!empty($variant->name)){
                $stmt_var->execute([$product_id, $variant->name, $variant->price]);
            }
        }
    }

    // --- STEP B: CHECK & DELETE OLD CATEGORY (After Update) ---
    // Only check if we actually moved the product to a different category
    if ($old_category_id && $old_category_id != $new_category_id) {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND user_id = ?");
        $stmtCheck->execute([$old_category_id, $user_id]);
        $remaining_items = $stmtCheck->fetchColumn();

        if ($remaining_items == 0) {
            // Old category is now empty, delete it
            $stmtDelCat = $pdo->prepare("DELETE FROM categories WHERE category_id = ? AND user_id = ?");
            $stmtDelCat->execute([$old_category_id, $user_id]);
        }
    }
    // ----------------------------------------------------------

    $pdo->commit();
    echo json_encode(["message" => "Product updated successfully"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
// No closing tag