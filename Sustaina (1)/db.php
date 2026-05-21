<?php
/**
 * Food Saver - Database Connection Configuration
 * Auto-creates the database and imports the mock schema if it doesn't exist.
 */

// Set default timezone for PHP to match local time
date_default_timezone_set('Asia/Manila');

$host = 'sql300.ezyro.com';
$db   = 'ezyro_41972952_db';
$user = 'ezyro_41972952';
$pass = '48bae3555b'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Force the database session to use the correct timezone
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'" 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    
    // Only attempt auto-setup if the error is "Unknown database" (Code 1049)
    if ($e->getCode() == 1049) {
        try {
            // Connect to mysql server directly without the dbname
            $setup_dsn = "mysql:host=$host;charset=$charset";
            $setup_pdo = new PDO($setup_dsn, $user, $pass, $options);
            
            // Create database
            $setup_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Reconnect to the new database
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            // Import schema.sql
            $schema_path = __DIR__ . '/schema.sql';
            if (file_exists($schema_path)) {
                $sql = file_get_contents($schema_path);
                
                // Remove comments and split by semi-colons
                $sql_clean = preg_replace('/--.*\n/', '', $sql);
                $statements = array_filter(array_map('trim', explode(';', $sql_clean)));
                
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        $pdo->exec($stmt);
                    }
                }
            }
        } catch (\PDOException $setup_err) {
            die("Food Saver Database Setup Error: Could not initialize database. Details: " . $setup_err->getMessage());
        }
    } else {
        // If the error is anything else (e.g., wrong password, server down), halt.
        die("Food Saver Connection Error: " . $e->getMessage());
    }
}
?>