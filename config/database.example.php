<?php
// config/database.php
// RENAME OR COPY THIS FILE TO database.php ON YOUR UBUNTU SERVER

$host = 'localhost';
$db   = 'pos_db';
$user = 'root'; // Use your MariaDB user
$pass = '';     // Enter your MariaDB password here
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If JSON header was set, it's safer for the API
     echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
     exit;
}
?>
