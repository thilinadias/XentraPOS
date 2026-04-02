<?php
// C:\xampp\htdocs\pos\receipt.php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';
require_login();

if (empty($_GET['id'])) die("Invalid Sale ID");
$sale_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username as cashier
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) die("Sale not found.");

    $stmtItems = $pdo->prepare("
        SELECT si.*, COALESCE(p.name, si.custom_name) as product_name
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmtItems->execute([$sale_id]);
    $items = $stmtItems->fetchAll();

    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while($row = $stmtSet->fetch(PDO::FETCH_ASSOC)){
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $currency = $settings['currency_symbol'] ?? '$';
    $footer_message = $settings['invoice_footer_message'] ?? 'Thank you for your business. Please retain this invoice for your records.';

} catch (PDOException $e) {
    die("Error fetching receipt: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $sale['id'] ?></title>
    <style>
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            line-height: 1.4;
        }
        .receipt-container {
            width: 300px;
            margin: 20px auto;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 10px; }
        .mt-4 { margin-top: 20px; }
        .divider { border-bottom: 1px dashed #e2e8f0; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 0; }
        th { color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .td-right { text-align: right; }
        .total-row { font-size: 16px; font-weight: 800; border-top: 2px solid #1e293b; }
        .dev-attribution { 
            font-size: 8px; 
            color: #cbd5e1; 
            text-align: center; 
            margin-top: 30px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .receipt-container { width: 300px; border: none; margin: 0; padding: 10px; }
            @page { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="receipt-container">
    <div class="text-center mb-1">
        <?php if (!empty($settings['company_logo'])): ?>
            <img src="/pos/<?= htmlspecialchars($settings['company_logo']) ?>" alt="Logo" style="max-height: 50px; margin-bottom: 5px;">
        <?php endif; ?>
        <h2 class="fw-bold" style="margin: 0; font-size: 18px;"><?= htmlspecialchars($settings['company_name'] ?? 'POS SYSTEM') ?></h2>
        <p style="margin: 5px 0;">
            <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
            <?= htmlspecialchars($settings['company_phone'] ?? '') ?>
        </p>
    </div>
    
    <div class="divider"></div>
    
    <p style="margin: 0;"><strong>Invoice #:</strong> <?= htmlspecialchars($sale['invoice_number'] ?? str_pad($sale['id'], 6, '0', STR_PAD_LEFT)) ?></p>
    <p style="margin: 0;"><strong>Date:</strong> <?= date('m/d/Y H:i A', strtotime($sale['created_at'])) ?></p>
    <p style="margin: 0;"><strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier']) ?></p>
    <?php if (!empty($sale['customer_name'])): ?>
    <p style="margin: 0;"><strong>Customer:</strong> <?= htmlspecialchars($sale['customer_name']) ?></p>
    <?php endif; ?>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="td-right">Qty</th>
                <th class="td-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td style="word-wrap: break-word; max-width: 150px;"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="td-right"><?= $item['quantity'] ?></td>
                <td class="td-right"><?= $currency ?><?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table style="font-size: 15px;">
        <tr>
            <td>Subtotal:</td>
            <td class="td-right"><?= $currency ?><?= number_format($sale['subtotal'], 2) ?></td>
        </tr>
        <?php if ($sale['discount_amount'] > 0): ?>
        <tr>
            <td>Discount:</td>
            <td class="td-right">-<?= $currency ?><?= number_format($sale['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td>TOTAL (NET):</td>
            <td class="td-right"><?= $currency ?><?= number_format($sale['grand_total'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Payment (<?= htmlspecialchars($sale['payment_type']) ?>):</td>
            <td class="td-right"><?= $currency ?><?= number_format($sale['amount_tendered'], 2) ?></td>
        </tr>
        <?php if ($sale['payment_type'] === 'Cash'): ?>
        <tr>
            <td>Change Due:</td>
            <td class="td-right"><?= $currency ?><?= number_format($sale['change_due'], 2) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="text-center mt-2">
        <p style="margin-top: 20px; font-weight: bold;">
            <?= nl2br(htmlspecialchars($settings['invoice_footer_message'] ?? 'THANK YOU FOR YOUR BUSINESS!')) ?>
        </p>
    </div>
    <div class="dev-attribution">
        XentraPOS • Developed by Thilina Dias
    </div>
    
</div>

</body>
</html>
