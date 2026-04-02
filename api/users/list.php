<?php
// C:\xampp\htdocs\pos\api\users\list.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Only super_admin can list all users
require_super_admin();

try {
    $stmt = $pdo->prepare("SELECT id, username, role, status, created_at, updated_at FROM users ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
