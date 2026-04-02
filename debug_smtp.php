<?php
// /tmp/debug_smtp.php
require_once 'C:/xampp/htdocs/pos/config/database.php';
require_once 'C:/xampp/htdocs/pos/includes/SMTP.php';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo "Attempting to connect to: " . $settings['smtp_host'] . ":" . $settings['smtp_port'] . "\n";
    echo "User: " . $settings['smtp_user'] . "\n";

    $smtp = new XentraSMTP([
        'host' => $settings['smtp_host'],
        'port' => $settings['smtp_port'],
        'user' => $settings['smtp_user'],
        'pass' => $settings['smtp_pass'],
        'encryption' => $settings['smtp_encryption'],
        'from_email' => $settings['smtp_from_email'],
        'from_name' => 'XentraPOS Debug'
    ]);

    $smtp->send($settings['smtp_user'], "XentraPOS Debug Test", "If you receive this, the SMTP client is working.");
    echo "SUCCESS: Email sent.\n";

} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
