<?php
// C:\xampp\htdocs\pos\api\sales\refund.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin']); // Only admins can refund

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$sale_id = (int)($_POST['sale_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($sale_id <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid sale ID.']));
}

if (empty($reason)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'A refund reason must be selected.']));
}

try {
    $pdo->beginTransaction();

    // 1. Get Sale Data
    $stmtSale = $pdo->prepare("SELECT * FROM sales WHERE id = ? FOR UPDATE");
    $stmtSale->execute([$sale_id]);
    $sale = $stmtSale->fetch();

    if (!$sale) throw new Exception("Sale not found.");
    if ($sale['status'] === 'Refunded') throw new Exception("Sale is already refunded.");

    // 2. Mark Sale as Refunded
    $stmtUpdateSale = $pdo->prepare("UPDATE sales SET status = 'Refunded', refund_reason = ?, refund_notes = ? WHERE id = ?");
    $stmtUpdateSale->execute([$reason, $notes, $sale_id]);

    // 3. Restore Stock
    $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
    $stmtItems->execute([$sale_id]);
    $items = $stmtItems->fetchAll();

    foreach ($items as $item) {
        if ($item['product_id']) {
            $stmtRestoreStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmtRestoreStock->execute([$item['quantity'], $item['product_id']]);
        }
    }

    // 4. Reverse Customer Debt if Credit sale
    if ($sale['payment_type'] === 'Credit' && $sale['customer_id']) {
        $stmtBalance = $pdo->prepare("UPDATE customers SET balance = balance - ? WHERE id = ?");
        $stmtBalance->execute([$sale['grand_total'], $sale['customer_id']]);
    }

    $logMsg = "Refunded invoice: {$sale['invoice_number']} (Total reversed: {$sale['grand_total']}) | Reason: {$reason}";
    if($notes) $logMsg .= " | Notes: {$notes}";
    
    log_activity('Sale Refunded', $logMsg);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Sale refunded and stock restored successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
