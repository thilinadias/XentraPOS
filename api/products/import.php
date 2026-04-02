<?php
// C:\xampp\htdocs\pos\api\products\import.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'No file uploaded.']));
}

$file = $_FILES['csv_file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === FALSE) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Failed to open file.']));
}

try {
    $pdo->beginTransaction();

    $header = fgetcsv($handle); // Skip header row
    $success_count = 0;
    $skip_count = 0;

    // Helper to get or create category
    function getCategoryId($pdo, $name) {
        if (empty($name)) return null;
        $name = trim($name);
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        if ($row) return $row['id'];

        // Create new category
        $stmtInsert = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmtInsert->execute([$name]);
        return $pdo->lastInsertId();
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        if (empty($row[0])) { $skip_count++; continue; } // Name required

        $name = trim($row[0]);
        $category_name = trim($row[1] ?? 'Uncategorized');
        $barcode = trim($row[2] ?? '');
        $cost_price = (float)($row[3] ?? 0);
        $price = (float)($row[4] ?? 0);
        $stock = (int)($row[5] ?? 0);
        $description = trim($row[6] ?? '');

        // Check for duplicate barcode
        if (!empty($barcode)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
            $stmtCheck->execute([$barcode]);
            if ($stmtCheck->fetch()) {
                $skip_count++;
                continue; // Skip duplicates
            }
        }

        $category_id = getCategoryId($pdo, $category_name);

        $stmt = $pdo->prepare("INSERT INTO products (name, category_id, barcode, cost_price, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category_id, $barcode, $cost_price, $price, $stock, $description]);
        
        $success_count++;
    }

    fclose($handle);
    $pdo->commit();
    log_activity('Bulk Import', "Successfully imported $success_count products (Skips: $skip_count)");

    echo json_encode([
        'success' => true, 
        'message' => "Import Complete! Products added: $success_count. Skips/Duplicates: $skip_count."
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Import Error: ' . $e->getMessage()]);
}
?>
