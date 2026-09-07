<?php
declare(strict_types=1);


$host = 'mysql-18d0be72-samota4209211-e4d4.f.aivencloud.com';
$dbname = 'laxmifreshmart';
$username = 'avnadmin';
$password = getenv('DB_PASSWORD');

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            ]
            );
            } catch (PDOException $e) {
                error_log($e->getMessage());
                
                http_response_code(500);
                exit('Database connection failed.');
                }
                // Report all PHP errors
                error_reporting(E_ALL);
                
                // Force errors to be displayed on the screen
                ini_set('display_startup_errors', 1);
                ini_set('display_errors', 1);