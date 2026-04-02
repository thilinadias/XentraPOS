<?php
// C:\xampp\htdocs\pos\api\settings\get.php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'data' => $settings]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load settings']);
}
