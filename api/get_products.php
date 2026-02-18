<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // 1. Get Products
    // UPDATED SQL: Fetch product_code explicitly (or p.* covers it, but nice to be sure)
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.user_id = ? 
            ORDER BY p.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll();

    // 2. Get Variants
    $sql_v = "SELECT v.* FROM product_variants v 
              JOIN products p ON v.product_id = p.product_id 
              WHERE p.user_id = ?";
    $stmt_v = $pdo->prepare($sql_v);
    $stmt_v->execute([$user_id]);
    $all_variants = $stmt_v->fetchAll();

    // 3. Merge variants
    foreach ($products as &$prod) {
        $prod['variants'] = [];
        if ($prod['has_variants'] == 1) {
            foreach ($all_variants as $v) {
                if ($v['product_id'] == $prod['product_id']) {
                    $prod['variants'][] = $v;
                }
            }
        }
    }

    echo json_encode($products ?: []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>