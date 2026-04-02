<?php
// C:\xampp\htdocs\pos\api\products\lookup.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_login();

// Can be GET request with ?barcode=...
$barcode = trim($_GET['barcode'] ?? '');

if (empty($barcode)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Barcode is required.']));
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.barcode = ?
    ");
    $stmt->execute([$barcode]);
    $product = $stmt->fetch();

    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
