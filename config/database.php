<?php
declare(strict_types=1);

// Force PHP to bypass php.ini rules and show absolutely everything
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Quick visual anchor to ensure this updated script is running
echo "<h2>🛠️ Diagnostic Mode Active</h2>";

$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT');
$dbname   = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

// Print basic connectivity check to see what Render is feeding into your code
echo "<b>Attempting Connection with parameters:</b><br>";
echo "Host: " . ($host ?: 'NOT SET (⚠️)') . "<br>";
echo "Port: " . ($port ?: 'NOT SET (⚠️)') . "<br>";
echo "User: " . ($username ?: 'NOT SET (⚠️)') . "<br>";
echo "DB: " . ($dbname ?: 'NOT SET (⚠️)') . "<br><br>";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
        $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Force SSL, but disable strict verification inside the container
        PDO::MYSQL_ATTR_SSL_CA       => '',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);

    
    echo "<span style='color:green; font-weight:bold;'>🎉 Success! Connected to Aiven MySQL securely.</span>";

} catch (\Throwable $e) {
    echo "<div style='background:#fee; border:1px solid #fcc; padding:15px; color:#900;'>";
    echo "<h3>🚨 Raw Connection Error Caught:</h3>";
    echo "<b>Message:</b> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "</div>";
    exit;
}
