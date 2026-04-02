<?php
// C:\xampp\htdocs\pos\api\reports\full_report.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Restricted to Super Admin and Auditor
require_role(['super_admin', 'auditor']);

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    $stats = [];

    // 1. KPI Summary
    // Note: total_profit = Total Revenue (After discounts) - Total Cost of items
    $stmtSummary = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status IN ('Completed', 'Paid', '') OR status IS NULL THEN 1 ELSE 0 END) as sales_count,
            SUM(CASE WHEN status IN ('Completed', 'Paid', '') OR status IS NULL THEN grand_total ELSE 0 END) as net_revenue,
            SUM(CASE WHEN status = 'Refunded' THEN grand_total ELSE 0 END) as total_refunded,
            SUM(CASE WHEN status = 'Refunded' THEN 1 ELSE 0 END) as refund_count,
            SUM(discount_amount) as total_discounts
        FROM sales 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmtSummary->execute([$start_date, $end_date]);
    $summary = $stmtSummary->fetch();

    // Calculate cost only for Paid items
    $stmtCost = $pdo->prepare("
        SELECT SUM(si.quantity * si.buy_price) as total_cost 
        FROM sale_items si 
        JOIN sales s ON si.sale_id = s.id 
        WHERE DATE(s.created_at) BETWEEN ? AND ? AND (s.status IN ('Completed', 'Paid', '') OR s.status IS NULL)
    ");
    $stmtCost->execute([$start_date, $end_date]);
    $cost_data = $stmtCost->fetch();
    $total_cost = (float)($cost_data['total_cost'] ?? 0);
    
    $stats['summary'] = [
        'sales_count' => (int)($summary['sales_count'] ?? 0),
        'total_revenue' => (float)($summary['net_revenue'] ?? 0),
        'total_refunded' => (float)($summary['total_refunded'] ?? 0),
        'refund_count' => (int)($summary['refund_count'] ?? 0),
        'total_profit' => (float)($summary['net_revenue'] ?? 0) - $total_cost,
        'total_discounts' => (float)($summary['total_discounts'] ?? 0),
        'avg_order' => $summary['sales_count'] > 0 ? (float)$summary['net_revenue'] / (int)$summary['sales_count'] : 0
    ];

    // 1.1 Refund Reasons Breakdown (With involved products)
    $stmtReasons = $pdo->prepare("
        SELECT 
            s.id,
            s.refund_reason, 
            s.grand_total as amount,
            GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN products p ON si.product_id = p.id
        WHERE DATE(s.created_at) BETWEEN ? AND ? AND s.status = 'Refunded'
        GROUP BY s.id
        ORDER BY s.id DESC
    ");
    $stmtReasons->execute([$start_date, $end_date]);
    $stats['refund_reasons'] = $stmtReasons->fetchAll(PDO::FETCH_ASSOC);

    // 2. Top Selling Products (Top 10)
    $stmtTopProducts = $pdo->prepare("
        SELECT si.product_id, p.name, SUM(si.quantity) as total_qty, SUM(si.line_total) as total_revenue
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN products p ON si.product_id = p.id
        WHERE DATE(s.created_at) BETWEEN ? AND ? AND (s.status IN ('Completed', 'Paid', '') OR s.status IS NULL)
        GROUP BY si.product_id
        ORDER BY total_qty DESC
        LIMIT 10
    ");
    $stmtTopProducts->execute([$start_date, $end_date]);
    $stats['top_products'] = $stmtTopProducts->fetchAll(PDO::FETCH_ASSOC);

    // 3. Category Performance
    $stmtCategories = $pdo->prepare("
        SELECT c.name as category_name, SUM(si.line_total) as revenue
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN products p ON si.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE DATE(s.created_at) BETWEEN ? AND ? AND (s.status IN ('Completed', 'Paid', '') OR s.status IS NULL)
        GROUP BY c.id
        ORDER BY revenue DESC
    ");
    $stmtCategories->execute([$start_date, $end_date]);
    $stats['category_performance'] = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

    // 4. Daily Sales Trend
    $stmtTrend = $pdo->prepare("
        SELECT 
            DATE(s.created_at) as date, 
            SUM(s.grand_total) as revenue, 
            SUM(s.grand_total) - SUM(si.quantity * si.buy_price) as profit
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE DATE(s.created_at) BETWEEN ? AND ? 
          AND (s.status IN ('Completed', 'Paid', '') OR s.status IS NULL)
        GROUP BY DATE(s.created_at)
        ORDER BY date ASC
    ");
    // Simplified Profit trend query for large datasets might need optimization, but works for now.
    $stmtTrend->execute([$start_date, $end_date]);
    $stats['daily_trend'] = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 5. Cashier Performance
    $stmtCashiers = $pdo->prepare("
        SELECT u.username, COUNT(s.id) as sales_count, SUM(s.grand_total) as total_revenue
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE DATE(s.created_at) BETWEEN ? AND ? AND (s.status IN ('Completed', 'Paid', '') OR s.status IS NULL)
        GROUP BY s.user_id
        ORDER BY total_revenue DESC
    ");
    $stmtCashiers->execute([$start_date, $end_date]);
    $stats['cashier_performance'] = $stmtCashiers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Reporting error: ' . $e->getMessage()]);
}
