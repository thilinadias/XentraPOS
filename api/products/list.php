<?php
// C:\xampp\htdocs\pos\api\products\list.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_login();

try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
