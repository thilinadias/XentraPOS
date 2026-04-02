<?php
// C:\xampp\htdocs\pos\api\logs\list.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_super_admin(); // Only super admin can see logs

error_reporting(0);
ini_set('display_errors', 0);

try {
    // SELF-HEALING: Bootstrap table immediately
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        action VARCHAR(100) NOT NULL, 
        description TEXT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $stmt = $pdo->query("
        SELECT l.*, u.username
        FROM activity_log l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 200
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $logs]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Audit log currently unavailable.']);
}
