<?php
session_start();
// --- FIX: FORCE NO CACHE ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// ---------------------------
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) { echo json_encode([]); exit; }

try {
    // Determine the correct Admin ID to pull categories from
    $stmt_user = $pdo->prepare("SELECT role, parent_id FROM users WHERE user_id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();
    
    $admin_id = ($u['role'] === 'cashier' && !empty($u['parent_id'])) ? $u['parent_id'] : $_SESSION['user_id'];

    $sql = "SELECT category_id, name, type FROM categories WHERE user_id = ? ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id]);
    $categories = $stmt->fetchAll();
    
    echo json_encode($categories ?: []);
} catch (Exception $e) { 
    http_response_code(500); 
    echo json_encode(["error" => $e->getMessage()]); 
}