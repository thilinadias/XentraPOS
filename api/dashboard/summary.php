<?php
// C:\xampp\htdocs\pos\api\dashboard\summary.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Restricted to roles allowed to see stats
require_role(['super_admin', 'auditor', 'agent', 'viewer']);

try {
    // 0. Fetch Settings for Threshold and Currency
    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('low_stock_threshold', 'currency_symbol')");
    $settings = [];
    while ($row = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $threshold = (int)($settings['low_stock_threshold'] ?? 10);
    $currency = $settings['currency_symbol'] ?? '$';

    $stats = ['currency_symbol' => $currency];

    // 1. Today's Stats
    $stmtToday = $pdo->query("SELECT SUM(grand_total) as revenue, COUNT(*) as count FROM sales WHERE DATE(created_at) = CURDATE() AND (status = 'Completed' OR status = '' OR status IS NULL)");
    $today = $stmtToday->fetch();
    $stats['today_revenue'] = (float)($today['revenue'] ?? 0);
    $stats['today_count'] = (int)($today['count'] ?? 0);

    // 2. Monthly Revenue
    $stmtMonth = $pdo->query("SELECT SUM(grand_total) as revenue FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND (status = 'Completed' OR status = '' OR status IS NULL)");
    $month = $stmtMonth->fetch();
    $stats['monthly_revenue'] = (float)($month['revenue'] ?? 0);

    // 3. Low Stock Count
    $stmtLowStockCount = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= ?");
    $stmtLowStockCount->execute([$threshold]);
    $lowStockCount = $stmtLowStockCount->fetch();
    $stats['low_stock_count'] = (int)($lowStockCount['count'] ?? 0);

    // 4. Chart Data (Last 7 Days)
    $stmtChart = $pdo->query("
        SELECT date_table.date, COALESCE(SUM(s.grand_total), 0) as total
        FROM (
            SELECT CURDATE() - INTERVAL 6 DAY AS date UNION SELECT CURDATE() - INTERVAL 5 DAY UNION 
            SELECT CURDATE() - INTERVAL 4 DAY UNION SELECT CURDATE() - INTERVAL 3 DAY UNION 
            SELECT CURDATE() - INTERVAL 2 DAY UNION SELECT CURDATE() - INTERVAL 1 DAY UNION SELECT CURDATE()
        ) AS date_table
        LEFT JOIN sales s ON DATE(s.created_at) = date_table.date AND (s.status = 'Completed' OR s.status = '' OR s.status IS NULL)
        GROUP BY date_table.date
        ORDER BY date_table.date ASC
    ");
    $stats['chart_data'] = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

    // 5. Recent Sales (Last 5)
    $stmtRecent = $pdo->query("SELECT id, invoice_number, customer_name, grand_total, status, created_at FROM sales ORDER BY id DESC LIMIT 5");
    $stats['recent_sales'] = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // 6. Low Stock Items (Top 10)
    $stmtLowStockItems = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE stock_quantity <= ? ORDER BY stock_quantity ASC LIMIT 10");
    $stmtLowStockItems->execute([$threshold]);
    $stats['low_stock_items'] = $stmtLowStockItems->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load dashboard statistics: ' . $e->getMessage()]);
}
