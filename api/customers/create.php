<?php
// C:\xampp\htdocs\pos\api\customers\create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($name) || empty($phone)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Name and Phone are required']));
}

try {
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $address]);

    log_activity('Customer Created', "Created customer: $name ($phone)");

    echo json_encode(['success' => true, 'message' => 'Customer created successfully.', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'A customer with this phone number already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
}
