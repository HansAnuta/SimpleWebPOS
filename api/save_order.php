<?php
session_start();
require_once __DIR__ . '/security_headers.php';
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

// Block Manager (store_admin) from processing transactions
if ($_SESSION['role'] === 'store_admin') {
    http_response_code(403);
    echo json_encode(["message" => "Managers are not allowed to process transactions."]);
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

    // =========================================================================
    // 0. IDEMPOTENCY CHECK (Prevents duplicate offline-syncs)
    // =========================================================================
    $local_ref_id = isset($data->local_ref_id) ? $data->local_ref_id : null;

    if (!empty($local_ref_id)) {
        $stmt_check = $pdo->prepare("SELECT * FROM orders WHERE local_ref_id = ?");
        $stmt_check->execute([$local_ref_id]);
        $existing_order = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_order) {
            // Order already exists from a previous delayed sync.
            // Commit to close the empty transaction cleanly, then return success.
            $pdo->commit();
            
            echo json_encode([
                "message" => "Order already synced",
                "order_id" => $existing_order['order_id'],
                "transaction_number" => $existing_order['transaction_number'],
                "subtotal" => round((float)$existing_order['subtotal'], 2),
                "vat_amount" => round((float)$existing_order['vat_amount'], 2),
                "discount_amount" => round((float)$existing_order['discount_amount'], 2),
                "total_amount" => round((float)$existing_order['total_amount'], 2),
                "amount_tendered" => round((float)$existing_order['amount_tendered'], 2),
                "change_amount" => round((float)$existing_order['change_amount'], 2)
            ]);
            exit; // Stop execution here to prevent duplicate insertion
        }
    }
    // =========================================================================

    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $auth_user = $stmt_user->fetch();
    if (!$auth_user) {
        throw new Exception("User not found");
    }
    $admin_id = ($auth_user['role'] === 'cashier' && !empty($auth_user['parent_id'])) ? (int)$auth_user['parent_id'] : (int)$user_id;

    $stmt_vat = $pdo->prepare("SELECT vat_rate FROM user_settings WHERE user_id = ? LIMIT 1");
    $stmt_vat->execute([$admin_id]);
    $vat_row = $stmt_vat->fetch();
    $vat_rate = $vat_row && $vat_row['vat_rate'] !== null ? (float)$vat_row['vat_rate'] : 12.0;

    // 1. Calculate Totals strictly from server-side product pricing
    $raw_subtotal = 0;
    $normalized_items = [];

    foreach ($data->items as $item) {
        $product_id = isset($item->product_id) ? (int)$item->product_id : 0;
        $variant_id = (isset($item->variant_id) && $item->variant_id !== '' && $item->variant_id !== null) ? (int)$item->variant_id : null;
        $qty = isset($item->qty) ? (int)$item->qty : (isset($item->quantity) ? (int)$item->quantity : 0);

        if ($product_id <= 0 || $qty <= 0) {
            throw new Exception("Invalid order item payload.");
        }

        if ($variant_id) {
            $stmt_item_data = $pdo->prepare("SELECT p.name AS product_name, v.variant_name, v.price AS unit_price FROM products p JOIN product_variants v ON v.product_id = p.product_id WHERE p.user_id = ? AND p.product_id = ? AND v.variant_id = ?");
            $stmt_item_data->execute([$admin_id, $product_id, $variant_id]);
            $db_item = $stmt_item_data->fetch();
        } else {
            $stmt_item_data = $pdo->prepare("SELECT p.name AS product_name, NULL AS variant_name, p.price AS unit_price FROM products p WHERE p.user_id = ? AND p.product_id = ?");
            $stmt_item_data->execute([$admin_id, $product_id]);
            $db_item = $stmt_item_data->fetch();
        }

        if (!$db_item) {
            throw new Exception("Item not found or unauthorized inventory access.");
        }

        $unit_price = (float)$db_item['unit_price'];
        $line_total = $unit_price * $qty;
        $raw_subtotal += $line_total;

        $normalized_items[] = [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'variant_name' => $db_item['variant_name'],
            'product_name' => $db_item['product_name'],
            'qty' => $qty,
            'unit_price' => $unit_price
        ];
    }
    
    // 2. Handle Discount & VAT Logic
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

    // Optional integrity check when legacy clients still submit computed amounts
    if (isset($data->subtotal) && abs((float)$data->subtotal - (float)$final_subtotal) > 0.01) {
        throw new Exception("Client subtotal mismatch.");
    }
    if (isset($data->vat_amount) && abs((float)$data->vat_amount - (float)$final_vat_amount) > 0.01) {
        throw new Exception("Client VAT mismatch.");
    }
    if (isset($data->total) && abs((float)$data->total - (float)$total_amount) > 0.01) {
        throw new Exception("Client total mismatch.");
    }

    $payment_method = $data->payment_method ?? 'cash';
    $amount_tendered = isset($data->amount_tendered) ? (float)$data->amount_tendered : 0.0;
    $change_amount = 0.0;
    $reference_number = $data->reference_number ?? null;

    if (strtolower($payment_method) === 'cash') {
        if ($amount_tendered < $total_amount) {
            throw new Exception("Insufficient cash tendered.");
        }
        $change_amount = $amount_tendered - $total_amount;
    } else {
        if (empty($reference_number)) {
            throw new Exception("Reference number is required for non-cash payments.");
        }
        $amount_tendered = $total_amount;
        $change_amount = 0.0;
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

    // 5. Insert Order (Updated to include local_ref_id)
    $sql_order = "INSERT INTO orders (
        user_id, local_ref_id, transaction_number, reference_number, service_type_id,
        customer_name, customer_id_type, customer_id_number,
        total_amount, subtotal, vat_amount, discount_amount, discount_type,
        payment_method, amount_tendered, change_amount, status
    ) VALUES (
        :uid, :local_ref, :tx_num, :ref_num, :svc_id,
        :cust_name, :id_type, :id_num,
        :total, :sub, :vat, :disc, :dtype,
        :method, :tendered, :change, :status
    )";
    
    $stmt = $pdo->prepare($sql_order);
    $stmt->execute([
        ':uid' => $user_id,
        ':local_ref' => $local_ref_id, // Bound the new variable here
        ':tx_num' => $transaction_number,
        ':ref_num' => $reference_number,
        ':svc_id' => $data->service_type_id ?? 1,
        ':cust_name' => $cust_name,
        ':id_type' => $cust_id_type,
        ':id_num' => $cust_id_num,
        ':total' => $total_amount,
        ':sub' => $final_subtotal,
        ':vat' => $final_vat_amount,
        ':disc' => $final_discount_amount,
        ':dtype' => $data->discount_type,
        ':method' => $payment_method,
        ':tendered' => $amount_tendered,
        ':change' => $change_amount,
        ':status' => $data->status ?? 'completed'
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // 6. Insert Items
    $sql_item = "INSERT INTO order_items (
        order_id, product_id, product_name, variant_id, variant_name, quantity, price_at_sale
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_item = $pdo->prepare($sql_item);
    
    foreach ($normalized_items as $item) {
        $stmt_item->execute([
            $order_id, 
            $item['product_id'], 
            $item['product_name'], 
            $item['variant_id'], 
            $item['variant_name'], 
            $item['qty'], 
            $item['unit_price']
        ]);
    }

    $pdo->commit();
    echo json_encode([
        "message" => "Order saved",
        "order_id" => $order_id, 
        "transaction_number" => $transaction_number,
        "subtotal" => round($final_subtotal, 2),
        "vat_amount" => round($final_vat_amount, 2),
        "discount_amount" => round($final_discount_amount, 2),
        "total_amount" => round($total_amount, 2),
        "amount_tendered" => round($amount_tendered, 2),
        "change_amount" => round($change_amount, 2)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>