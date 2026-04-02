<?php
// C:\xampp\htdocs\pos\api\sales\create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Super admin and agents can process sales
require_role(['super_admin', 'agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->items) || !is_array($data->items)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Cart is empty.']));
}

$user_id = $_SESSION['user_id'];
$customer_id = isset($data->customer_id) ? (int)$data->customer_id : null;
$customer_name = isset($data->customer_name) ? trim($data->customer_name) : null;
$customer_phone = isset($data->customer_phone) ? trim($data->customer_phone) : null;
$payment_type = $data->payment_type ?? 'Cash';
$amount_tendered = (float)($data->amount_tendered ?? 0);
$discount_amount = (float)($data->discount_amount ?? 0);
$tax_amount = (float)($data->tax_amount ?? 0);

try {
    // Begin Transaction
    $pdo->beginTransaction();

    $calculated_subtotal = 0;
    
    // Validate Items and Calculate Subtotal Server-side exactly
    $stmtProd = $pdo->prepare("SELECT price, cost_price, stock_quantity FROM products WHERE id = ? FOR UPDATE");
    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

    // We will build the sale_items array to insert later
    $sale_items_data = [];

    foreach ($data->items as $item) {
        $qty = (int)$item->quantity;
        $item_discount = (float)($item->discount ?? 0);
        $is_custom = !empty($item->is_custom) && $item->is_custom === true;

        if ($qty <= 0) continue;

        if (!$is_custom) {
            $prod_id = (int)$item->id;
            $stmtProd->execute([$prod_id]);
            $dbProd = $stmtProd->fetch();

            if (!$dbProd) {
                throw new Exception("Product ID {$prod_id} not found.");
            }
            $actual_price = (float)$dbProd['price'];
            $actual_cost = (float)$dbProd['cost_price'];
            $line_subtotal = ($actual_price * $qty) - $item_discount;
            $calculated_subtotal += $line_subtotal;

            $sale_items_data[] = [
                'product_id' => $prod_id,
                'custom_name' => null,
                'quantity' => $qty,
                'unit_price' => $actual_price,
                'buy_price' => $actual_cost,
                'item_discount' => $item_discount,
                'line_total' => $line_subtotal
            ];
            // Deduct Stock
            $stmtUpdateStock->execute([$qty, $prod_id]);
        } else {
            // It's a custom line item
            $actual_price = (float)($item->price ?? 0);
            $custom_name = trim($item->custom_name ?? 'Custom Item');
            
            $line_subtotal = ($actual_price * $qty) - $item_discount;
            $calculated_subtotal += $line_subtotal;

            $sale_items_data[] = [
                'product_id' => null,
                'custom_name' => $custom_name,
                'quantity' => $qty,
                'unit_price' => $actual_price,
                'buy_price' => 0, // Custom items don't have a cost tracking unless we add an input for it
                'item_discount' => $item_discount,
                'line_total' => $line_subtotal
            ];
        }
    }

    $grand_total = $calculated_subtotal - $discount_amount + $tax_amount;
    
    // Change logic
    $change_due = 0;
    if ($payment_type === 'Cash') {
        if ($amount_tendered < $grand_total - 0.01) { // 0.01 tolerance
            throw new Exception("Amount tendered is less than Grand Total.");
        }
        $change_due = $amount_tendered - $grand_total;
    } else {
        $amount_tendered = $grand_total; // Cards/Credit exact amount
    }

    // 2. Handle Customer Credit Balance
    if ($payment_type === 'Credit' && $customer_id) {
        $stmtBalance = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
        $stmtBalance->execute([$grand_total, $customer_id]);
    }

    // Insert Sale
    $stmtSale = $pdo->prepare("INSERT INTO sales (user_id, customer_id, customer_name, customer_phone, subtotal, discount_amount, tax_amount, grand_total, payment_type, amount_tendered, change_due) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtSale->execute([$user_id, $customer_id, $customer_name, $customer_phone, $calculated_subtotal, $discount_amount, $tax_amount, $grand_total, $payment_type, $amount_tendered, $change_due]);
    
    $sale_id = $pdo->lastInsertId();
    $invoice_number = 'INV-' . str_pad($sale_id, 6, '0', STR_PAD_LEFT);
    $pdo->query("UPDATE sales SET invoice_number = '{$invoice_number}' WHERE id = {$sale_id}");

    // Insert Sale Items
    $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, custom_name, quantity, unit_price, buy_price, item_discount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sale_items_data as $i) {
        $stmtItem->execute([$sale_id, $i['product_id'], $i['custom_name'], $i['quantity'], $i['unit_price'], $i['buy_price'], $i['item_discount'], $i['line_total']]);
    }

    // Commit Transaction
    $pdo->commit();

    // 📩 New Phase: Check for Low Stock Email Alerts
    try {
        $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('email_alerts_enabled', 'low_stock_threshold', 'smtp_host', 'smtp_user', 'smtp_pass', 'smtp_port', 'smtp_encryption', 'smtp_from_email')");
        $sets = [];
        while ($r = $stmtSet->fetch(PDO::FETCH_ASSOC)) $sets[$r['setting_key']] = $r['setting_value'];

        if (($sets['email_alerts_enabled'] ?? '0') === '1') {
            $low_threshold = (int)($sets['low_stock_threshold'] ?? 10);
            $low_items = [];

            // Re-check items in this sale
            $stmtCheck = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
            foreach ($sale_items_data as $i) {
                if ($i['product_id']) {
                    $stmtCheck->execute([$i['product_id']]);
                    $p = $stmtCheck->fetch();
                    if ($p && $p['stock_quantity'] <= $low_threshold) {
                        $low_items[] = $p;
                    }
                }
            }

            if (!empty($low_items)) {
                require_once '../../includes/SMTP.php';
                $smtp = new XentraSMTP([
                    'host' => $sets['smtp_host'],
                    'port' => $sets['smtp_port'],
                    'user' => $sets['smtp_user'],
                    'pass' => $sets['smtp_pass'],
                    'encryption' => $sets['smtp_encryption'],
                    'from_email' => $sets['smtp_from_email'],
                    'from_name' => 'XentraPOS Alerts'
                ]);

                $subject = "⚠️ Low Stock Alert: Items need attention";
                $listHtml = "<table border='0' cellpadding='10' cellspacing='0' style='width: 100%; border-radius: 8px; overflow: hidden;'>";
                foreach($low_items as $li) {
                    $listHtml .= "
                    <tr style='background: #fff5f5; border-bottom: 1px solid #fee2e2;'>
                        <td style='padding: 15px; border-bottom: 1px solid #fee2e2;'>
                            <strong style='color: #1e293b; font-size: 1.1em;'>{$li['name']}</strong><br>
                            <span style='color: #dc3545; font-size: 0.9em; font-weight: bold;'>Remaining: {$li['stock_quantity']} units</span>
                        </td>
                    </tr>";
                }
                $listHtml .= "</table>";

                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; overflow: hidden;'>
                        <div style='background: #dc3545; color: #fff; padding: 25px; text-align: center;'>
                            <h1 style='margin: 0; font-size: 1.5em;'>🚨 Inventory Alert</h1>
                            <p style='margin: 5px 0 0; opacity: 0.9;'>XentraPOS Low Stock Notification</p>
                        </div>
                        <div style='padding: 30px;'>
                            <p style='color: #64748b; margin-bottom: 20px;'>The following items have dropped below your safety threshold (<b>{$low_threshold}</b>) and require restocking:</p>
                            
                            {$listHtml}

                            <div style='margin-top: 35px; text-align: center;'>
                                <a href='" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/pos/products.php' 
                                   style='background: #0d6efd; color: #fff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                                   Go to Inventory Master
                                </a>
                            </div>
                        </div>
                        <div style='background: #f8f9fa; padding: 15px; text-align: center; color: #94a3b8; font-size: 0.8em; border-top: 1px solid #eee;'>
                            &copy; " . date('Y') . " XentraPOS Premium Notifications
                        </div>
                    </div>
                ";
                $smtp->send($sets['smtp_user'], $subject, $body);
            }
        }
    } catch (Exception $mailErr) {
        // Silently fail email so the sale still completes for the user
        error_log("Email Alert Failed: " . $mailErr->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Sale processed successfully!', 'sale_id' => $sale_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Bad request or logic error
    echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
}
?>
