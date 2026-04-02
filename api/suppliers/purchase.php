<?php
// C:\xampp\htdocs\pos\api\suppliers\purchase.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$product_id = (int)($_POST['product_id'] ?? 0);
$supplier_id = (int)($_POST['supplier_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$cost_price = (float)($_POST['cost_price'] ?? 0);

if (!$product_id || $quantity <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid product or quantity.']));
}

try {
    $pdo->beginTransaction();

    // Update product stock and cost
    $stmtUpdate = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, cost_price = ? WHERE id = ?");
    $stmtUpdate->execute([$quantity, $cost_price, $product_id]);

    // Record in purchase_history
    $stmtPH = $pdo->prepare("INSERT INTO purchase_history (product_id, supplier_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
    $stmtPH->execute([$product_id, $supplier_id, $quantity, $cost_price, ($quantity * $cost_price)]);

    log_activity('Stock-In Recorded', "Received $quantity units for product ID $product_id (Supplier: $supplier_id) at $cost_price cost.");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Stock updated successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error recording stock-in: ' . $e->getMessage()]);
}
