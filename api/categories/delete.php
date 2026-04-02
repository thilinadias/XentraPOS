<?php
// C:\xampp\htdocs\pos\api\categories\delete.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Super admin and agents can delete categories
require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID is required']));
}

$id = (int)$data->id;

try {
    // Check if category has products
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $checkStmt->execute([$id]);
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        http_response_code(409); // Conflict
        exit(json_encode(['success' => false, 'message' => 'Cannot delete category that contains products.']));
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Category not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
