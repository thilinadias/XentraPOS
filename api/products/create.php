<?php
// C:\xampp\htdocs\pos\api\products\create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Super admin and agents can add products
require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$name = trim($_POST['name'] ?? '');
$barcode = trim($_POST['barcode'] ?? '') ?: null; // Nullable
$price = (float)($_POST['price'] ?? 0);
$cost_price = (float)($_POST['cost_price'] ?? 0);
$stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($price)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Name and Price are required']));
}

// FINANCIAL INTEGRITY GUARD: Warning for unusual margins
if ($cost_price >= $price && $cost_price > 0) {
    error_log("FINANCIAL WARNING: New product '$name' created with cost ($cost_price) >= price ($price)");
}

$image_path = null;

// Handle File Upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/products/';
    
    // Create dir if doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name']));
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // Save relative path to DB
        $image_path = 'uploads/products/' . $fileName;
    } else {
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Error saving uploaded file.']));
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, barcode, price, cost_price, stock_quantity, description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$category_id, $name, $barcode, $price, $cost_price, $stock_quantity, $description, $image_path]);

    echo json_encode(['success' => true, 'message' => 'Product added successfully.', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // DUPLICATE KEY
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Barcode already exists in the system.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
}
?>
