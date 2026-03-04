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
    // 1. Find the Admin ID
    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();

    if (!$u) throw new Exception("User not found.");

    // If Store Admin, use their own ID. If Cashier, use their parent's ID.
    $admin_id = ($u['role'] === 'store_admin') ? $_SESSION['user_id'] : $u['parent_id'];

    // 2. Fetch sales where user is Admin OR user's parent is the Admin
    $sql = "SELECT 
                o.order_id, o.transaction_number, o.reference_number,
                o.created_at, o.customer_name, o.customer_id_type,
                o.customer_id_number, o.total_amount, o.subtotal, 
                o.vat_amount, o.discount_amount, o.discount_type, 
                o.payment_method, o.amount_tendered, o.change_amount,
                o.user_id as cashier_id,
                
                st.service_name,
                
                oi.id as item_id, oi.quantity, oi.price_at_sale, 
                oi.variant_name, oi.product_name,

                u.first_name, u.last_name
            FROM orders o 
            LEFT JOIN service_types st ON o.service_type_id = st.service_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id 
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE u.user_id = ? OR u.parent_id = ? 
            ORDER BY o.created_at DESC, oi.id ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id, $admin_id]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $orders = [];
    
    foreach ($raw_data as $row) {
        $oid = $row['order_id'];
        
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $oid,
                'transaction_number' => $row['transaction_number'],
                'reference_number' => $row['reference_number'],     
                'created_at' => $row['created_at'],
                'service_type' => $row['service_name'],             
                'customer_name' => $row['customer_name'],
                'customer_id_type' => $row['customer_id_type'],
                'customer_id_number' => $row['customer_id_number'],
                'total_amount' => $row['total_amount'],
                'subtotal' => $row['subtotal'],
                'vat_amount' => $row['vat_amount'],
                'discount_amount' => $row['discount_amount'],
                'discount_type' => $row['discount_type'],
                'payment_method' => $row['payment_method'],
                'cashier_id' => $row['cashier_id'],
                'cashier_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'items' => [] 
            ];
        }
        
        if ($row['item_id']) {
            $orders[$oid]['items'][] = [
                'name' => $row['product_name'], 
                'variant' => $row['variant_name'],
                'qty' => $row['quantity'],
                'price' => $row['price_at_sale'],
                'total' => $row['quantity'] * $row['price_at_sale']
            ];
        }
    }
    
    echo json_encode(array_values($orders));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>