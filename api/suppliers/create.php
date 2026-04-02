<?php
// C:\xampp\htdocs\pos\api\suppliers\create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$name = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($name)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Company Name is required']));
}

try {
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $contact, $phone]);

    log_activity('Supplier Created', "New supplier added: $name");

    echo json_encode(['success' => true, 'message' => 'Supplier added successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
