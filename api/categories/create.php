<?php
// C:\xampp\htdocs\pos\api\categories\create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Super admin and agents can create categories
require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->name)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Category name is required']));
}

$name = trim($data->name);
$description = isset($data->description) ? trim($data->description) : null;

try {
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);

    echo json_encode(['success' => true, 'message' => 'Category created successfully.', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
