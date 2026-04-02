<?php
// C:\xampp\htdocs\pos\api\users\update.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Only super_admin can update users
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

// We build a dynamic update query based on provided fields
$updateFields = [];
$params = [];

if (isset($data->username) && trim($data->username) !== '') {
    $updateFields[] = "username = ?";
    $params[] = trim($data->username);
}

if (isset($data->password) && trim($data->password) !== '') {
    $updateFields[] = "password = ?";
    $params[] = password_hash(trim($data->password), PASSWORD_BCRYPT);
}

if (isset($data->role)) {
    $role = trim($data->role);
    $allowed_roles = ['super_admin', 'agent', 'auditor', 'viewer'];
    if (in_array($role, $allowed_roles)) {
        $updateFields[] = "role = ?";
        $params[] = $role;
    }
}

if (isset($data->status)) {
    $status = trim($data->status);
    $allowed_status = ['active', 'suspended'];
    if (in_array($status, $allowed_status)) {
        $updateFields[] = "status = ?";
        $params[] = $status;
    }
}

if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update.']);
    exit;
}

// Add ID parameter at the end
$params[] = $id;

$query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes made.']); // Can be successful with 0 rows affected if data is the same
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
