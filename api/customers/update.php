<?php
// C:\xampp\htdocs\pos\api\customers\update.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($id <= 0 || empty($name) || empty($phone)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID, Name, and Phone are required']));
}

try {
    $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $email, $address, $id]);

    log_activity('Customer Updated', "Customer details modified: $name");

    echo json_encode(['success' => true, 'message' => 'Customer updated successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
