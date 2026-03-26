<?php
$host = 'localhost';
$db   = 'u346699795_pebblepos';
$user = 'root';      
$pass = '';      
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+08:00';");
    
} catch (\PDOException $e) {
    if (!headers_sent()) { header('Content-Type: application/json'); }
    http_response_code(500);
    // Note for future iteration: We will sanitize this error output later.
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>