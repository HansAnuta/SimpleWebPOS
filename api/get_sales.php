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
    
    // JOIN with service_types to get the name (Dine-in, etc.)
    $sql = "SELECT 
                o.order_id, 
                o.transaction_number,
                o.reference_number,
                o.created_at, 
                o.customer_name, 
                o.customer_id_type,
                o.customer_id_number,
                o.total_amount, 
                o.subtotal, 
                o.vat_amount, 
                o.discount_amount, 
                o.discount_type, 
                o.payment_method, 
                o.amount_tendered, 
                o.change_amount,
                
                st.service_name,
                
                oi.id as item_id, 
                oi.quantity, 
                oi.price_at_sale, 
                oi.variant_name,
                oi.product_name
            FROM orders o 
            LEFT JOIN service_types st ON o.service_type_id = st.service_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC, oi.id ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $orders = [];
    
    foreach ($raw_data as $row) {
        $oid = $row['order_id'];
        
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $oid,
                'transaction_number' => $row['transaction_number'], // New ID
                'reference_number' => $row['reference_number'],     // Payment Ref
                'created_at' => $row['created_at'],
                'service_type' => $row['service_name'],             // Dine-in/Take-out
                'customer_name' => $row['customer_name'],
                'customer_id_type' => $row['customer_id_type'],
                'customer_id_number' => $row['customer_id_number'],
                'total_amount' => $row['total_amount'],
                'subtotal' => $row['subtotal'],
                'vat_amount' => $row['vat_amount'],
                'discount_amount' => $row['discount_amount'],
                'discount_type' => $row['discount_type'],
                'payment_method' => $row['payment_method'],
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