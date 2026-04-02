<?php
// C:\xampp\htdocs\pos\api\auth\logout.php
require_once '../../config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    try {
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], 'Logout', 'User logged out of the system']);
    } catch (Exception $e) { /* Non-critical error */ }
}

session_unset();
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
