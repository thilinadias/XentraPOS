<?php
// C:\xampp\htdocs\pos\api\settings\update.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// ONLY Super Admin can change global company settings
require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");

    // Standard text fields
    $fields = [
        'company_name', 'company_address', 'company_email', 'company_phone', 
        'invoice_footer_message', 'currency_symbol', 'low_stock_threshold',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption', 
        'smtp_from_email', 'email_alerts_enabled', 'email_daily_summary_enabled'
    ];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = trim($_POST[$field]);
            $stmt->execute([$value, $field]);
        }
    }

    // Logo Upload Logic
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/settings/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $fileInfo = pathinfo($_FILES['company_logo']['name']);
        $ext = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid image format. Allowed: JPG, PNG, GIF.");
        }

        $new_filename = 'logo_' . time() . '.' . $ext;
        $dest_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $dest_path)) {
            $logo_url = 'uploads/settings/' . $new_filename;
            $stmt->execute([$logo_url, 'company_logo']);
        } else {
            throw new Exception("Failed to upload logo.");
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Company Profile updated successfully.']);

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
