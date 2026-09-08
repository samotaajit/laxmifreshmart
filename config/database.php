<?php
declare(strict_types=1);

// 1. Fetch values safely from Render Environment Variables
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT'); // Make sure to add DB_PORT to Render Env!
$dbname   = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    // 2. Build DSN explicitly adding the custom port parameter
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // 3. Force SSL Mode requirement for remote hosting validation
            PDO::MYSQL_ATTR_SSL_CAPATH   => '/etc/ssl/certs', 
        ]
    );
} catch (PDOException $e) {
    // This logs the real issue to your Render console log timeline
    error_log("Database Connection Error: " . $e->getMessage());
    
    http_response_code(500);
    exit('Database connection failed.');
}

// Debugging rules (Keep enabled during staging setup)
error_reporting(E_ALL);
ini_set('display_startup_errors', '1');
ini_set('display_errors', '1');
