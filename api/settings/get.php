<?php
// C:\xampp\htdocs\pos\api\settings\get.php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    // 1. Fetch current settings from DB
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // 2. Self-Healing Defaults (Fallback if DB is empty)
    $defaults = [
        'company_name' => 'XentraPOS Enterprise',
        'company_address' => 'Colombo, Sri Lanka',
        'company_email' => 'admin@xentra.pos',
        'company_phone' => '+94 77 123 4567',
        'currency_symbol' => 'Rs.',
        'low_stock_threshold' => '10',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'email_alerts_enabled' => '0',
        'email_daily_summary_enabled' => '0'
    ];

    // Merge DB values over defaults
    $finalSettings = array_merge($defaults, $settings);

    echo json_encode(['success' => true, 'data' => $finalSettings]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load settings']);
}
