<?php
// C:\xampp\htdocs\pos\api\customers\ledger.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

$customer_id = (int)($_GET['id'] ?? 0);

if (!$customer_id) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid customer ID.']));
}

try {
    // 1. Get Sales
    $stmtSales = $pdo->prepare("SELECT id, invoice_number, grand_total as amount, created_at, 'Sale' as type FROM sales WHERE customer_id = ?");
    $stmtSales->execute([$customer_id]);
    $sales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Payments
    $stmtPayments = $pdo->prepare("SELECT id, amount, payment_method, created_at, 'Payment' as type FROM customer_payments WHERE customer_id = ?");
    $stmtPayments->execute([$customer_id]);
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

    // Merge and Sort
    $ledger = array_merge($sales, $payments);
    usort($ledger, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode(['success' => true, 'data' => $ledger]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
