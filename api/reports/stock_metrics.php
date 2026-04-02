<?php
// C:\xampp\htdocs\pos\api\reports\stock_metrics.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'auditor']);

try {
    // 0. Fetch Settings for Threshold
    $stmtSet = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'low_stock_threshold'");
    $threshold = (int)($stmtSet->fetchColumn() ?: 10);

    $stats = [];

    // 1. Overall Valuation
    $stmtValuation = $pdo->query("
        SELECT 
            SUM(stock_quantity * cost_price) as total_cost_value,
            SUM(stock_quantity * price) as total_retail_value,
            SUM(stock_quantity) as total_items_count
        FROM products
    ");
    $valuation = $stmtValuation->fetch();
    
    $stats['valuation'] = [
        'total_cost' => (float)($valuation['total_cost_value'] ?? 0),
        'total_retail' => (float)($valuation['total_retail_value'] ?? 0),
        'total_items' => (int)($valuation['total_items_count'] ?? 0),
        'potential_profit' => (float)$valuation['total_retail_value'] - (float)$valuation['total_cost_value']
    ];

    // 2. Stock Health Breakdown (Donut Chart)
    $stmtHealth = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= ? THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock_quantity > ? THEN 1 ELSE 0 END) as healthy
        FROM products
    ");
    $stmtHealth->execute([$threshold, $threshold]);
    $stats['health'] = $stmtHealth->fetch(PDO::FETCH_ASSOC);

    // 3. Investment by Category (Bar Chart)
    $stmtCategoryInvestment = $pdo->query("
        SELECT c.name as category_name, SUM(p.stock_quantity * p.cost_price) as investment
        FROM products p
        JOIN categories c ON p.category_id = c.id
        GROUP BY c.id
        ORDER BY investment DESC
    ");
    $stats['category_investment'] = $stmtCategoryInvestment->fetchAll(PDO::FETCH_ASSOC);

    // 4. Critical Stock List (Top 10 most urgent)
    $stmtCritical = $pdo->prepare("
        SELECT name, stock_quantity, price
        FROM products 
        WHERE stock_quantity <= ? 
        ORDER BY stock_quantity ASC 
        LIMIT 10
    ");
    $stmtCritical->execute([$threshold]);
    $stats['critical_list'] = $stmtCritical->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Stock reporting error: ' . $e->getMessage()]);
}
