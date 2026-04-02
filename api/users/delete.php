<?php
// C:\xampp\htdocs\pos\api\users\delete.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Only super_admin can delete users
require_super_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$id = (int)$data->id;

// Prevent admin from deleting themselves
if ($id === $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
    exit;
}

try {
    // We could do a soft delete or actually delete.
    // The instructions say "Deletes or suspends a user". Let's do a hard delete for simplicity here,
    // as suspending is handled in update.php
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
