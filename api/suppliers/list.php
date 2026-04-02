<?php
// C:\xampp\htdocs\pos\api\suppliers\list.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_login();

try {
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $suppliers]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
