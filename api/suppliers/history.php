<?php
// C:\xampp\htdocs\pos\api\suppliers\history.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

$supplier_id = (int)($_GET['id'] ?? 0);

if (!$supplier_id) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid supplier ID.']));
}

try {
    $stmt = $pdo->prepare("
        SELECT ph.id, p.name as product_name, ph.quantity, ph.unit_cost, ph.total_cost, ph.created_at
        FROM purchase_history ph
        JOIN products p ON ph.product_id = p.id
        WHERE ph.supplier_id = ?
        ORDER BY ph.created_at DESC
    ");
    $stmt->execute([$supplier_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $history]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
