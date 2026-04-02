<?php
// C:\xampp\htdocs\pos\api\sales\list.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent', 'auditor']);

try {
    $stmt = $pdo->query("
        SELECT s.id, s.invoice_number, s.customer_name, s.grand_total, s.payment_type, s.status, s.created_at, u.username as cashier
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.id DESC
    ");
    $sales = $stmt->fetchAll();
    
    // In case invoice_number was null for legacy
    foreach($sales as &$s) {
        if(empty($s['invoice_number'])) {
            $s['invoice_number'] = 'INV-' . str_pad($s['id'], 6, '0', STR_PAD_LEFT);
        }
    }

    echo json_encode(['success' => true, 'data' => $sales]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve sales: ' . $e->getMessage()]);
}
?>
