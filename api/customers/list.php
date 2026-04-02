<?php
// C:\xampp\htdocs\pos\api\customers\list.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_login();

try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $customers]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
