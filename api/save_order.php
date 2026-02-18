<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->items) || count($data->items) === 0) {
    http_response_code(400);
    echo json_encode(["message" => "Cart is empty"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 1. Calculate Totals (Recalculate strictly for safety)
    $raw_subtotal = 0;
    foreach($data->items as $item) {
        $raw_subtotal += ($item->price * $item->qty);
    }
    
    // 2. Handle Discount & VAT Logic
    $vat_rate = 12; 
    $is_exempt = ($data->discount_type === 'Senior' || $data->discount_type === 'PWD');
    
    $final_vat_amount = 0;
    $final_discount_amount = 0;
    $final_subtotal = $raw_subtotal; 
    
    if ($is_exempt) {
        // Exempt VAT: Gross / 1.12
        $vat_exempt_sales = $raw_subtotal / (1 + ($vat_rate / 100));
        $final_vat_amount = 0; 
        
        // 20% Discount on Net
        $calculated_discount = $vat_exempt_sales * 0.20;
        
        // CAP at 50
        $final_discount_amount = ($calculated_discount > 50) ? 50.00 : $calculated_discount;
        
        $total_amount = $vat_exempt_sales - $final_discount_amount;
        
    } else {
        // Standard Transaction
        $net_sales = $raw_subtotal / (1 + ($vat_rate / 100));
        $final_vat_amount = $raw_subtotal - $net_sales;
        $final_discount_amount = 0;
        // Explicitly set Total = Subtotal
        $total_amount = $raw_subtotal;
    }

    // 3. Generate Transaction Number
    $date_str = date('Ymd');
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $stmt_count->execute();
    $daily_count = $stmt_count->fetchColumn();
    $sequence = $daily_count + 1;
    $transaction_number = "TRX-" . $date_str . "-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);

    // 4. Prepare Customer Data
    $cust_name = !empty($data->customer_name) ? $data->customer_name : null;
    $cust_id_type = !empty($data->customer_id_type) ? $data->customer_id_type : null;
    $cust_id_num = !empty($data->customer_id_number) ? $data->customer_id_number : null;

    if ($is_exempt) {
        if (!$cust_name || !$cust_id_num) {
            throw new Exception("Customer Name and ID Number are required for Senior/PWD discounts.");
        }
    } 

    // 5. Insert Order
    $sql_order = "INSERT INTO orders (
        user_id, transaction_number, reference_number, service_type_id,
        customer_name, customer_id_type, customer_id_number,
        total_amount, subtotal, vat_amount, discount_amount, discount_type,
        payment_method, amount_tendered, change_amount
    ) VALUES (
        :uid, :tx_num, :ref_num, :svc_id,
        :cust_name, :id_type, :id_num,
        :total, :sub, :vat, :disc, :dtype,
        :method, :tendered, :change
    )";
    
    $stmt = $pdo->prepare($sql_order);
    $stmt->execute([
        ':uid' => $user_id,
        ':tx_num' => $transaction_number,
        ':ref_num' => $data->reference_number ?? null,
        ':svc_id' => $data->service_type_id ?? 1,
        ':cust_name' => $cust_name,
        ':id_type' => $cust_id_type,
        ':id_num' => $cust_id_num,
        ':total' => $total_amount,
        ':sub' => $final_subtotal,
        ':vat' => $final_vat_amount,
        ':disc' => $final_discount_amount,
        ':dtype' => $data->discount_type,
        ':method' => $data->payment_method,
        ':tendered' => $data->amount_tendered,
        ':change' => $data->change_amount
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // 6. Insert Items
    $sql_item = "INSERT INTO order_items (
        order_id, product_id, product_name, variant_id, variant_name, quantity, price_at_sale
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_item = $pdo->prepare($sql_item);
    
    foreach ($data->items as $item) {
        $stmt_item->execute([
            $order_id, 
            $item->product_id, 
            $item->name, 
            $item->variant_id ?? null, 
            $item->variant_name ?? null, 
            $item->qty, 
            $item->price
        ]);
    }

    $pdo->commit();
    echo json_encode([
        "message" => "Order saved", 
        "order_id" => $order_id, 
        "transaction_number" => $transaction_number
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>