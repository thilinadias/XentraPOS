<?php
// C:\xampp\htdocs\pos\api\system\monthly_cron.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SMTP.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

// Security: Use a system secret or allow if called from server (Localhost)
$is_local = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');
if (!$is_local && !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Forbidden']));
}

try {
    // 1. Fetch SMTP & Notification Settings
    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $sets = [];
    while ($row = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
        $sets[$row['setting_key']] = $row['setting_value'];
    }

    if (($sets['email_daily_summary_enabled'] ?? '0') !== '1') {
        exit(json_encode(['success' => false, 'message' => 'Notifications are disabled in settings.']));
    }

    $cur = $sets['currency_symbol'] ?? '$';
    
    // 2. Determine Report Month (Default to last month)
    // E.g. If it's April 2nd, we report for March.
    $report_month = $_GET['month'] ?? date('Y-m', strtotime('first day of last month'));
    $display_month = date('F Y', strtotime($report_month . "-01"));
    
    $start_date = $report_month . "-01 00:00:00";
    $end_date = date('Y-m-t', strtotime($report_month . "-01")) . " 23:59:59";

    // 3. Gather KPIs (Paid Sales)
    $stmtKpis = $pdo->prepare("
        SELECT 
            COUNT(*) as sales_count,
            SUM(grand_total) as revenue,
            SUM(discount_amount) as total_discounts
        FROM sales 
        WHERE created_at BETWEEN ? AND ? AND status = 'Paid'
    ");
    $stmtKpis->execute([$start_date, $end_date]);
    $kpis = $stmtKpis->fetch();

    $revenue = (float)($kpis['revenue'] ?? 0);
    $sales_count = (int)($kpis['sales_count'] ?? 0);

    // 4. Calculate Net Profit for the month
    $stmtCost = $pdo->prepare("
        SELECT SUM(si.quantity * si.buy_price) as total_cost 
        FROM sale_items si 
        JOIN sales s ON si.sale_id = s.id 
        WHERE s.created_at BETWEEN ? AND ? AND s.status = 'Paid'
    ");
    $stmtCost->execute([$start_date, $end_date]);
    $cost_data = $stmtCost->fetch();
    $net_profit = $revenue - (float)($cost_data['total_cost'] ?? 0);

    // 5. Gather Refund Stats
    $stmtRefunds = $pdo->prepare("
        SELECT COUNT(*) as count, SUM(grand_total) as amount
        FROM sales
        WHERE created_at BETWEEN ? AND ? AND status = 'Refunded'
    ");
    $stmtRefunds->execute([$start_date, $end_date]);
    $refunds = $stmtRefunds->fetch();
    $refund_revenue = (float)($refunds['amount'] ?? 0);
    $refund_count = (int)($refunds['count'] ?? 0);

    // 6. Top Sales Category
    $stmtCat = $pdo->prepare("
        SELECT c.name, SUM(si.line_total) as rev
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN products p ON si.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE s.created_at BETWEEN ? AND ? AND s.status = 'Paid'
        GROUP BY c.id ORDER BY rev DESC LIMIT 1
    ");
    $stmtCat->execute([$start_date, $end_date]);
    $top_cat = $stmtCat->fetch();

    // 7. Best Cashier
    $stmtCashier = $pdo->prepare("
        SELECT u.username, SUM(s.grand_total) as rev
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.created_at BETWEEN ? AND ? AND s.status = 'Paid'
        GROUP BY s.user_id ORDER BY rev DESC LIMIT 1
    ");
    $stmtCashier->execute([$start_date, $end_date]);
    $best_cashier = $stmtCashier->fetch();

    // 8. Construct Email
    $smtp = new XentraSMTP([
        'host' => $sets['smtp_host'],
        'port' => $sets['smtp_port'],
        'user' => $sets['smtp_user'],
        'pass' => $sets['smtp_pass'],
        'encryption' => $sets['smtp_encryption'],
        'from_email' => $sets['smtp_from_email'],
        'from_name' => 'XentraPOS Monthly Digest'
    ]);

    $subject = "🏆 Monthly Business Summary - {$display_month}";
    
    $body = "
        <div style='font-family: Inter, Arial, sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background-color: #f8fafc;'>
            <div style='background: #0f172a; color: #fff; padding: 40px 25px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>🏆 XentraPOS Monthly Digest</h1>
                <p style='margin: 8px 0 0; opacity: 0.7; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;'>{$display_month}</p>
            </div>
            
            <div style='padding: 35px;'>
                <div style='display: flex; gap: 20px; margin-bottom: 35px;'>
                    <div style='flex: 1; text-align: center; background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <div style='color: #64748b; font-size: 0.75em; font-weight: 800; text-transform: uppercase; margin-bottom: 10px;'>Monthly Revenue</div>
                        <div style='font-size: 2em; font-weight: 800; color: #0d6efd;'>{$cur}" . number_format($revenue, 2) . "</div>
                    </div>
                </div>

                <table style='width: 100%; border-collapse: collapse; margin-bottom: 35px;'>
                    <tr>
                        <td style='padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px 0 0 12px;'>
                            <div style='color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase;'>Monthly Net Profit</div>
                            <div style='font-size: 18px; font-weight: 800; color: #10b981;'>{$cur}" . number_format($net_profit, 2) . "</div>
                        </td>
                        <td style='padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 0 12px 12px 0;'>
                            <div style='color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase;'>Total Orders</div>
                            <div style='font-size: 18px; font-weight: 800; color: #1e293b;'>{$sales_count}</div>
                        </td>
                    </tr>
                </table>

                <?php if ($refund_count > 0): ?>
                <div style='background: #fff; border-left: 5px solid #ef4444; border-radius: 8px; padding: 20px; margin-bottom: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
                    <h3 style='margin: 0; color: #b91c1c; font-size: 15px; text-transform: uppercase;'>🚨 Reversals Summary</h3>
                    <p style='margin: 10px 0 0; color: #4b5563; font-size: 14px;'>
                        During {$display_month}, <b>" . $refund_count . "</b> transactions were refunded, totaling <b>" . $cur . number_format($refund_revenue, 2) . "</b> in reversed capital.
                    </p>
                </div>
                <?php endif; ?>

                <div style='background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0;'>
                    <h3 style='margin: 0 0 20px; color: #1e293b; font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;'>🚀 Performance Leaders</h3>
                    <table style='width: 100%; font-size: 14px;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b;'>🏆 Top Sales Category:</td>
                            <td style='padding: 8px 0; font-weight: bold; text-align: right;'>" . ($top_cat['name'] ?? 'N/A') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b;'>⭐ Star Cashier:</td>
                            <td style='padding: 8px 0; font-weight: bold; text-align: right;'>" . ($best_cashier['username'] ?? 'N/A') . "</td>
                        </tr>
                    </table>
                </div>

                <div style='margin-top: 45px; text-align: center;'>
                    <a href='" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/pos/reports.php' 
                       style='background: #0f172a; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 12px; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>
                       Explore Monthly Analytics
                    </a>
                </div>
            </div>
            
            <div style='background: #f1f5f9; padding: 20px; text-align: center; color: #94a3b8; font-size: 11px;'>
                &copy; " . date('Y') . " XentraPOS Premium Environment • All Financial Data Audit-Logged
            </div>
        </div>
    ";
    
    // Final check for the refund_count variable placement errors
    $body = str_replace(['<?php if ($refund_count > 0): ?>','<?php endif; ?>'], '', $body);
    if ($refund_count <= 0) {
        $body = preg_replace('/<div style=\'background: #fff; border-left: 5px solid #ef4444;.*?<\/div>/s', '', $body);
    }

    $smtp->send($sets['smtp_user'], $subject, $body);

    // 📩 Update the tracker in settings
    $stmtTrack = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_monthly_summary_month'");
    $stmtTrack->execute([$report_month]);

    echo json_encode(['success' => true, 'message' => "Monthly summary for {$display_month} sent successfully!"]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Failed to send monthly summary: ' . $e->getMessage()]);
}
