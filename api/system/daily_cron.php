<?php
// C:\xampp\htdocs\pos\api\system\daily_cron.php
/**
 * XentraPOS Daily Summary Task
 * This script generates the end-of-day sales report and stock digest.
 * Setup: Trigger this URL nightly via a Cron Job or manual button.
 */
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/SMTP.php';

try {
    // 1. Check if Daily Summary is enabled
    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('email_daily_summary_enabled', 'smtp_host', 'smtp_user', 'smtp_pass', 'smtp_port', 'smtp_encryption', 'smtp_from_email', 'low_stock_threshold', 'currency_symbol')");
    $sets = [];
    while ($r = $stmtSet->fetch(PDO::FETCH_ASSOC)) $sets[$r['setting_key']] = $r['setting_value'];

    if (($sets['email_daily_summary_enabled'] ?? '0') !== '1') {
        exit(json_encode(['success' => false, 'message' => 'Daily summary emails are disabled.']));
    }

    $cur = $sets['currency_symbol'] ?? '$';
    
    // 2. Determine Report Date (Default to today)
    $report_date = $_GET['date'] ?? date('Y-m-d');
    $display_date = date('l, F j, Y', strtotime($report_date));
    
    // 3. Gather Stats for that date
    $stmtSales = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as sales_count,
            SUM(CASE WHEN status = 'Paid' THEN grand_total ELSE 0 END) as net_revenue,
            SUM(CASE WHEN status = 'Refunded' THEN 1 ELSE 0 END) as refund_count,
            SUM(CASE WHEN status = 'Refunded' THEN grand_total ELSE 0 END) as total_refunded
        FROM sales 
        WHERE DATE(created_at) = ?
    ");
    $stmtSales->execute([$report_date]);
    $stats = $stmtSales->fetch();

    $revenue = (float)($stats['net_revenue'] ?? 0);
    $sales_count = (int)($stats['sales_count'] ?? 0);
    $refund_revenue = (float)($stats['total_refunded'] ?? 0);
    $refund_count = (int)($stats['refund_count'] ?? 0);

    // 4. Gather Low Stock Items (Global status as it is now)
    $threshold = (int)($sets['low_stock_threshold'] ?? 10);
    $stmtLow = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE stock_quantity <= ? ORDER BY stock_quantity ASC");
    $stmtLow->execute([$threshold]);
    $low_items = $stmtLow->fetchAll();

    // 5. Construct Email
    $smtp = new XentraSMTP([
        'host' => $sets['smtp_host'],
        'port' => $sets['smtp_port'],
        'user' => $sets['smtp_user'],
        'pass' => $sets['smtp_pass'],
        'encryption' => $sets['smtp_encryption'],
        'from_email' => $sets['smtp_from_email'],
        'from_name' => 'XentraPOS Daily Report'
    ]);

    $subject = "📅 Daily Business Summary - " . date('M d, Y', strtotime($report_date));
    
    $lowStockHtml = "";
    if (!empty($low_items)) {
        $lowStockHtml = "<h3>⚠️ Low Stock Digest</h3><table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; border-color: #eee;'>
            <thead style='background: #f8f9fa;'><tr><th>Product</th><th>Status</th></tr></thead><tbody>";
        foreach ($low_items as $li) {
            $lowStockHtml .= "<tr><td>{$li['name']}</td><td><b style='color: #dc3545;'>{$li['stock_quantity']} remaining</b></td></tr>";
        }
        $lowStockHtml .= "</tbody></table>";
    } else {
        $lowStockHtml = "<p style='color: #198754;'>✅ All stock levels are healthy.</p>";
    }

    $refundHtml = "";
    if ($refund_count > 0) {
        $refundHtml = "
        <div style='background: #fff5f5; border-left: 4px solid #f56565; padding: 15px; margin: 20px 0; border-radius: 4px;'>
            <h3 style='margin: 0; color: #c53030; font-size: 14px; text-transform: uppercase;'>🚨 Daily Refunds Dispatch</h3>
            <p style='margin: 5px 0 0; color: #742a2a; font-size: 14px;'>
                <b>{$refund_count}</b> transaction(s) were reversed for a total of <b>{$cur}" . number_format($refund_revenue, 2) . "</b>. 
                Inventory has been updated accordingly.
            </p>
        </div>";
    }

    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; overflow: hidden;'>
            <div style='background: #1e40af; color: #fff; padding: 25px; text-align: center;'>
                <h1 style='margin: 0;'>XentraPOS Daily Summary</h1>
                <p style='margin: 5px 0 0; opacity: 0.8;'>{$display_date}</p>
            </div>
            <div style='padding: 30px;'>
                <div style='display: flex; gap: 20px; margin-bottom: 30px;'>
                    <div style='flex: 1; text-align: center; background: #eff6ff; padding: 20px; border-radius: 8px;'>
                        <div style='color: #64748b; font-size: 0.8em; font-weight: bold; text-transform: uppercase;'>Total Revenue</div>
                        <div style='font-size: 1.8em; font-weight: bold; color: #1e40af;'>{$cur}" . number_format($revenue, 2) . "</div>
                    </div>
                    <div style='flex: 1; text-align: center; background: #f0fdf4; padding: 20px; border-radius: 8px;'>
                        <div style='color: #64748b; font-size: 0.8em; font-weight: bold; text-transform: uppercase;'>Total Sales</div>
                        <div style='font-size: 1.8em; font-weight: bold; color: #15803d;'>{$sales_count}</div>
                    </div>
                </div>

                {$refundHtml}

                {$lowStockHtml}

                <div style='margin-top: 40px; text-align: center;'>
                    <a href='" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/pos/reports.php' 
                       style='background: #0d6efd; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                       Open Full Reports
                    </a>
                </div>
            </div>
            <div style='background: #f8f9fa; padding: 15px; text-align: center; color: #94a3b8; font-size: 0.8em;'>
                &copy; " . date('Y') . " XentraPOS Premium Environment
            </div>
        </div>
    ";

    $smtp->send($sets['smtp_user'], $subject, $body);

    // 📩 New: Update the tracker in settings
    $stmtTrack = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_daily_summary_date'");
    $stmtTrack->execute([$report_date]);

    echo json_encode(['success' => true, 'message' => "Daily summary for {$report_date} sent successfully!"]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Failed to send daily summary: ' . $e->getMessage()]);
}
