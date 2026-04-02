<?php
// C:\xampp\htdocs\pos\api\users\create.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Only super_admin can create users
require_super_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->username) || empty($data->password) || empty($data->role)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username, password, and role are required.']);
    exit;
}

$username = trim($data->username);
$password = trim($data->password);
$role = trim($data->role);
$status = isset($data->status) ? trim($data->status) : 'active';

// Validate role
$allowed_roles = ['super_admin', 'agent', 'auditor', 'viewer'];
if (!in_array($role, $allowed_roles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $role, $status]);

    echo json_encode([
        'success' => true,
        'message' => 'User created successfully.',
        'userId' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate username)
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
