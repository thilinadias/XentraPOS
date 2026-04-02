<?php
// C:\xampp\htdocs\pos\api\customers\payment_add.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$customer_id = (int)($_POST['customer_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$method = $_POST['payment_method'] ?? 'Cash';

if ($customer_id <= 0 || $amount <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid customer or amount.']));
}

try {
    $pdo->beginTransaction();

    // Insert payment record
    $stmtPay = $pdo->prepare("INSERT INTO customer_payments (customer_id, user_id, amount, payment_method) VALUES (?, ?, ?, ?)");
    $stmtPay->execute([$customer_id, $_SESSION['user_id'], $amount, $method]);

    // Update customer balance (subtract payment)
    $stmtUpdate = $pdo->prepare("UPDATE customers SET balance = balance - ? WHERE id = ?");
    $stmtUpdate->execute([$amount, $customer_id]);

    log_activity('Customer Payment', "Repayment of $amount from customer ID: $customer_id via $method");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()]);
}
