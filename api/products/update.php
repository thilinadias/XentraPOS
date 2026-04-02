<?php
// C:\xampp\htdocs\pos\api\products\update.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$id = (int)($_POST['id'] ?? 0);
if (empty($id)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Product ID is required.']));
}

// Fetch old product for old image path
$stmtOld = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
$stmtOld->execute([$id]);
$oldProduct = $stmtOld->fetch();

if (!$oldProduct) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'message' => 'Product not found.']));
}

$name = trim($_POST['name'] ?? '');
$barcode = trim($_POST['barcode'] ?? '') ?: null;
$price = (float)($_POST['price'] ?? 0);
$cost_price = (float)($_POST['cost_price'] ?? 0);
$stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($price)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Name and Price are required.']));
}

// FINANCIAL INTEGRITY GUARD: Warning for negative margins
if ($cost_price >= $price && $cost_price > 0) {
    // We allow it (clearance/loss-leader), but we log a specific notification
    error_log("FINANCIAL WARNING: Product '$name' updated with cost ($cost_price) >= price ($price)");
}

$image_path = $oldProduct['image_path']; // Default to old image

// Handle Image Update
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    
    // Optional: Delete old image
    if ($image_path && file_exists('../../' . $image_path)) {
        unlink('../../' . $image_path);
    }
    
    $uploadDir = '../../uploads/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name']));
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $image_path = 'uploads/products/' . $fileName;
    } else {
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Error saving uploaded file.']));
    }
}

try {
    $stmt = $pdo->prepare("UPDATE products SET category_id=?, name=?, barcode=?, price=?, cost_price=?, stock_quantity=?, description=?, image_path=? WHERE id=?");
    $stmt->execute([$category_id, $name, $barcode, $price, $cost_price, $stock_quantity, $description, $image_path, $id]);

    log_activity('Product Updated', "Modified product ID $id: $name ($barcode)");

    echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Barcode already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
}
?>
