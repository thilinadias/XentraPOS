<?php
// C:\xampp\htdocs\pos\api\products\delete.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

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
    // Check old photo to delete it
    $stmtOld = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmtOld->execute([$id]);
    $product = $stmtOld->fetch();

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        if ($product && $product['image_path'] && file_exists('../../' . $product['image_path'])) {
            unlink('../../' . $product['image_path']);
        }
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
} catch (PDOException $e) {
    // If we have invoices attached later, this will throw constraint violation
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cannot delete product because it has been used in sales history. Consider making it inactive instead.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
}
?>
