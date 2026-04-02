<?php
// C:\xampp\htdocs\pos\api\reports\export.php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . ($_GET['type'] ?? 'sales') . '_report_' . date('Y-m-d') . '.csv"');

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Restricted to Super Admin and Auditor
require_role(['super_admin', 'auditor']);

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    $fp = fopen('php://output', 'w');
    $type = $_GET['type'] ?? 'sales';

    if ($type === 'stock') {
        // Stock Report Headers
        fputcsv($fp, ['ID', 'Product Name', 'Category', 'Barcode', 'Quantity', 'Cost Price', 'Retail Price', 'Total Cost Value', 'Retail Value']);
        
        $stmt = $pdo->query("
            SELECT p.*, c.name as cat_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.stock_quantity ASC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, [
                $row['id'],
                $row['name'],
                $row['cat_name'] ?: 'N/A',
                $row['barcode'],
                $row['stock_quantity'],
                number_format($row['cost_price'], 2),
                number_format($row['price'], 2),
                number_format($row['stock_quantity'] * $row['cost_price'], 2),
                number_format($row['stock_quantity'] * $row['price'], 2)
            ]);
        }
    } else {
        // Sales Report Headers
        fputcsv($fp, ['ID', 'Date', 'Invoice #', 'Cashier', 'Customer', 'Subtotal', 'Discount', 'Tax', 'Grand Total', 'Payment Type']);

        $stmt = $pdo->prepare("
            SELECT s.id, s.created_at, s.invoice_number, u.username, s.customer_name, s.subtotal, s.discount_amount, s.tax_amount, s.grand_total, s.payment_type
            FROM sales s
            JOIN users u ON s.user_id = u.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.id DESC
        ");
        $stmt->execute([$start_date, $end_date]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, [
                $row['id'],
                $row['created_at'],
                $row['invoice_number'],
                $row['username'],
                $row['customer_name'] ?: 'Walk-in',
                number_format($row['subtotal'], 2),
                number_format($row['discount_amount'], 2),
                number_format($row['tax_amount'], 2),
                number_format($row['grand_total'], 2),
                $row['payment_type']
            ]);
        }
    }

    fclose($fp);

} catch (Exception $e) {
    echo "Error generating CSV.";
}
