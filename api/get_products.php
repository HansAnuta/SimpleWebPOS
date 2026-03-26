<?php
session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    // Determine the correct Admin ID (Cashiers pull from their parent Admin's inventory)
    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();
    
    $admin_id = ($u['role'] === 'cashier' && !empty($u['parent_id'])) ? $u['parent_id'] : $_SESSION['user_id'];

    // 1. Get Products using admin_id
    $sql = "SELECT p.*, c.name as category_name, c.type as category_type 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.user_id = ? 
            ORDER BY p.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Variants using admin_id
    $sql_v = "SELECT v.* FROM product_variants v 
              JOIN products p ON v.product_id = p.product_id 
              WHERE p.user_id = ?";
    $stmt_v = $pdo->prepare($sql_v);
    $stmt_v->execute([$admin_id]);
    $all_variants = $stmt_v->fetchAll(PDO::FETCH_ASSOC);

    // 3. Merge variants into their respective products
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