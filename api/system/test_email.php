<?php
// C:\xampp\htdocs\pos\api\system\test_email.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';
require_once '../../includes/SMTP.php';

require_role(['super_admin']);

try {
    // Load current SMTP settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    if (empty($settings['smtp_host']) || empty($settings['smtp_user']) || empty($settings['smtp_pass'])) {
        throw new Exception("SMTP settings are incomplete. Please save settings first.");
    }

    $smtp = new XentraSMTP([
        'host' => $settings['smtp_host'],
        'port' => $settings['smtp_port'],
        'user' => $settings['smtp_user'],
        'pass' => $settings['smtp_pass'],
        'encryption' => $settings['smtp_encryption'],
        'from_email' => $settings['smtp_from_email'],
        'from_name' => 'XentraPOS System'
    ]);

    $subject = "🚀 XentraPOS: Email Connection Test Successful";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
            <div style='background: #0d6efd; color: #fff; padding: 20px; text-align: center;'>
                <h1 style='margin: 0;'>XentraPOS</h1>
            </div>
            <div style='padding: 30px; line-height: 1.6; color: #333;'>
                <h2 style='color: #198754;'>Connection Successful!</h2>
                <p>Hello from <strong>XentraPOS</strong>.</p>
                <p>This is a test email to confirm that your SMTP configuration is working correctly. You can now enable automated low-stock alerts and daily summaries.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 0.8em; color: #666;'>Sent at: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    ";

    $smtp->send($settings['smtp_user'], $subject, $body);

    echo json_encode(['success' => true, 'message' => 'Test email sent successfully!']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
