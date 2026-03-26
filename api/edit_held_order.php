<?php
session_start();
require_once __DIR__ . '/security_headers.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }

// Block Manager (store_admin) from processing transactions
if ($_SESSION['role'] === 'store_admin') {
    http_response_code(403);
    echo json_encode(["message" => "Managers are not allowed to process transactions."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    if (!isset($data->order_id) || !isset($data->items) || !is_array($data->items) || count($data->items) === 0) {
        throw new Exception("Invalid order payload.");
    }

    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $auth_user = $stmt_user->fetch();
    if (!$auth_user) {
        throw new Exception("User not found.");
    }

    $admin_id = ($auth_user['role'] === 'cashier' && !empty($auth_user['parent_id'])) ? (int)$auth_user['parent_id'] : (int)$user_id;

    $stmt_vat = $pdo->prepare("SELECT vat_rate FROM user_settings WHERE user_id = ? LIMIT 1");
    $stmt_vat->execute([$admin_id]);
    $vat_row = $stmt_vat->fetch();
    $vat_rate = $vat_row && $vat_row['vat_rate'] !== null ? (float)$vat_row['vat_rate'] : 12.0;

    $stmt_existing = $pdo->prepare("SELECT order_id, total_amount, original_total FROM orders WHERE order_id = ? AND (user_id = ? OR user_id IN (SELECT user_id FROM users WHERE parent_id = ?))");
    $stmt_existing->execute([$data->order_id, $user_id, $user_id]);
    $existing_order = $stmt_existing->fetch();
    if (!$existing_order) {
        throw new Exception("Order not found or access denied.");
    }

    // 1. Recalculate using authoritative server-side prices
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
    
    $is_exempt = ($data->discount_type === 'Senior' || $data->discount_type === 'PWD');
    $final_vat_amount = 0; $final_discount_amount = 0; $final_subtotal = $raw_subtotal; 
    
    if ($is_exempt) {
        $vat_exempt_sales = $raw_subtotal / (1 + ($vat_rate / 100));
        $calculated_discount = $vat_exempt_sales * 0.20;
        $final_discount_amount = ($calculated_discount > 50) ? 50.00 : $calculated_discount;
    } else {
        $net_sales = $raw_subtotal / (1 + ($vat_rate / 100));
        $final_vat_amount = $raw_subtotal - $net_sales;
    }

    $computed_total = $final_subtotal - $final_discount_amount;
    if ($computed_total < 0) {
        $computed_total = 0;
    }

    $original_total_locked = (float)$existing_order['original_total'];
    if ($original_total_locked <= 0) {
        $original_total_locked = (float)$existing_order['total_amount'];
    }

    $payment_method = $data->payment_method ?? 'cash';
    $amount_tendered_input = isset($data->amount_tendered) ? (float)$data->amount_tendered : 0.0;

    $final_total = 0.0;
    $store_credit = 0.0;
    $final_amount_tendered = 0.0;
    $final_change = 0.0;

    if ($computed_total <= $original_total_locked) {
        // Preserve locked total and retain difference as store credit
        $final_total = $original_total_locked;
        $store_credit = $original_total_locked - $computed_total;
        $final_amount_tendered = $original_total_locked;
        $final_change = 0.0;
    } else {
        // Additional payment required beyond original locked amount
        $additional_due = $computed_total - $original_total_locked;
        $final_total = $computed_total;

        if (strtolower($payment_method) === 'cash') {
            if ($amount_tendered_input < $additional_due) {
                throw new Exception("Insufficient amount for additional due.");
            }
            $final_amount_tendered = $original_total_locked + $amount_tendered_input;
            $final_change = $amount_tendered_input - $additional_due;
        } else {
            $final_amount_tendered = $computed_total;
            $final_change = 0.0;
        }
    }

    // Optional integrity checks for legacy clients still sending computed values
    if (isset($data->subtotal) && abs((float)$data->subtotal - (float)$final_subtotal) > 0.01) {
        throw new Exception("Client subtotal mismatch.");
    }
    if (isset($data->vat_amount) && abs((float)$data->vat_amount - (float)$final_vat_amount) > 0.01) {
        throw new Exception("Client VAT mismatch.");
    }
    if (isset($data->total) && abs((float)$data->total - (float)$final_total) > 0.01) {
        throw new Exception("Client total mismatch.");
    }

    // 2. Update the existing Order (Notice :uid1 and :uid2 at the end)
    $sql_order = "UPDATE orders SET 
        service_type_id = :svc_id, customer_name = :cust_name, customer_id_type = :id_type, customer_id_number = :id_num,
        total_amount = :total, original_total = :orig_total, store_credit = :store_credit, 
        subtotal = :sub, vat_amount = :vat, discount_amount = :disc, discount_type = :dtype,
        payment_method = :method, amount_tendered = :tendered, change_amount = :change, status = :status
        WHERE order_id = :oid AND (user_id = :uid1 OR user_id IN (SELECT user_id FROM users WHERE parent_id = :uid2))";
    
    $stmt = $pdo->prepare($sql_order);
    
    // Bind all 18 unique parameters explicitly
    $stmt->execute([
        ':svc_id' => $data->service_type_id ?? 1,
        ':cust_name' => $data->customer_name ?? null,
        ':id_type' => $data->customer_id_type ?? null,
        ':id_num' => $data->customer_id_number ?? null,
        ':total' => $final_total,
        ':orig_total' => $original_total_locked,
        ':store_credit' => $store_credit,
        ':sub' => $final_subtotal,
        ':vat' => $final_vat_amount,
        ':disc' => $final_discount_amount,
        ':dtype' => $data->discount_type,
        ':method' => $payment_method,
        ':tendered' => $final_amount_tendered,
        ':change' => $final_change,
        ':status' => $data->status ?? 'completed',
        ':oid' => $data->order_id,
        ':uid1' => $user_id, // First usage mapped
        ':uid2' => $user_id  // Second usage mapped
    ]);

    // 3. Delete existing items
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$data->order_id]);

    // 4. Insert new items
    $sql_item = "INSERT INTO order_items (order_id, product_id, product_name, variant_id, variant_name, quantity, price_at_sale) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);
    foreach ($normalized_items as $item) {
        $stmt_item->execute([$data->order_id, $item['product_id'], $item['product_name'], $item['variant_id'], $item['variant_name'], $item['qty'], $item['unit_price']]);
    }

    $pdo->commit();
    echo json_encode([
        "success" => true,
        "message" => "Order updated successfully",
        "subtotal" => round($final_subtotal, 2),
        "vat_amount" => round($final_vat_amount, 2),
        "discount_amount" => round($final_discount_amount, 2),
        "total_amount" => round($final_total, 2),
        "amount_tendered" => round($final_amount_tendered, 2),
        "change_amount" => round($final_change, 2),
        "store_credit" => round($store_credit, 2)
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); 
    echo json_encode(["error" => $e->getMessage()]);
}
?>