<?php
// C:\xampp\htdocs\pos\api\suppliers\update.php
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
$contact = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($id <= 0 || empty($name)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID and Name are required']));
}

try {
    $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $contact, $phone, $id]);

    log_activity('Supplier Updated', "Supplier details modified: $name");

    echo json_encode(['success' => true, 'message' => 'Supplier updated successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
