<?php
// C:\xampp\htdocs\pos\invoice.php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';
require_login();

if (empty($_GET['id'])) die("Invalid Invoice ID");
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

    if (!$sale) die("Invoice not found.");

    $stmtItems = $pdo->prepare("
        SELECT si.*, COALESCE(p.name, si.custom_name) as product_name
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmtItems->execute([$sale_id]);
    $items = $stmtItems->fetchAll();

    // Fetch Settings
    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while($row = $stmtSet->fetch(PDO::FETCH_ASSOC)){
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $currency = $settings['currency_symbol'] ?? '$';
    $footer_message = $settings['invoice_footer_message'] ?? 'Thank you for your business. Please retain this invoice for your records.';

} catch (PDOException $e) {
    die("Error fetching invoice: " . $e->getMessage());
}

$invoice_num = htmlspecialchars($sale['invoice_number'] ?? 'INV-' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= $invoice_num ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }
        .invoice-box {
            max-width: 850px;
            margin: 40px auto;
            background: #fff;
            padding: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-radius: 16px;
            position: relative;
        }
        .invoice-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 30px; margin-bottom: 40px; }
        .table { border-color: #f1f5f9; }
        .table th { background-color: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid #f1f5f9; }
        .text-primary { color: #0d6efd !important; }
        .text-muted { color: #94a3b8 !important; }
        .developer-footer { 
            margin-top: 50px; 
            padding-top: 20px; 
            border-top: 1px solid #f1f5f9; 
            font-size: 9px; 
            color: #cbd5e1; 
            text-align: center;
            letter-spacing: 0.5px;
        }
        
        @media print {
            body { background-color: #fff; }
            .invoice-box { box-shadow: none; margin: 0; padding: 0; max-width: 100%; border-radius: 0; }
            .no-print { display: none; }
            .developer-footer { position: fixed; bottom: 20px; width: 100%; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-end mt-3 no-print">
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
        <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="invoice-box">
        <div class="row invoice-header align-items-center">
            <div class="col-sm-6">
                <?php if (!empty($settings['company_logo'])): ?>
                    <img src="/pos/<?= htmlspecialchars($settings['company_logo']) ?>" alt="Company Logo" style="max-height: 80px; margin-bottom: 10px;">
                <?php endif; ?>
                <h2 class="text-primary fw-bold mb-0"><?= htmlspecialchars($settings['company_name'] ?? 'COMPANY NAME') ?></h2>
                <p class="text-muted mb-0">
                    <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
                    <?= htmlspecialchars($settings['company_email'] ?? '') ?> • <?= htmlspecialchars($settings['company_phone'] ?? '') ?>
                </p>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h1 class="text-muted">INVOICE</h1>
                <p class="mb-0 fw-bold fs-5"><?= $invoice_num ?></p>
                <p class="text-muted mb-0">Date: <?= date('F j, Y, g:i A', strtotime($sale['created_at'])) ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-sm-6">
                <h6 class="text-muted text-uppercase fw-bold">Bill To:</h6>
                <?php if (!empty($sale['customer_name'])): ?>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($sale['customer_name']) ?></h5>
                    <p class="mb-0"><?= htmlspecialchars($sale['customer_phone']) ?></p>
                <?php else: ?>
                    <h5 class="fw-bold text-muted mb-0">Walk-in Customer</h5>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h6 class="text-muted text-uppercase fw-bold">Payment Details:</h6>
                <p class="mb-0"><strong>Method:</strong> <?= htmlspecialchars($sale['payment_type']) ?></p>
                <p class="mb-0"><strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier']) ?></p>
            </div>
        </div>

        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-center" style="width: 100px;">Qty</th>
                    <th class="text-end" style="width: 120px;">Unit Price</th>
                    <th class="text-end" style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($item['product_name']) ?>
                        <?php if($item['item_discount'] > 0): ?>
                            <br><small class="text-danger">Item Discount: -$<?= number_format($item['item_discount'], 2) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= $currency ?><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-end fw-bold"><?= $currency ?><?= number_format($item['line_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row">
            <div class="col-sm-6">
                <p class="text-muted small mt-4">
                    <?= nl2br(htmlspecialchars($settings['invoice_footer_message'] ?? 'Thank you for your business.')) ?>
                </p>
            </div>
            <div class="col-sm-6">
                <table class="table table-sm table-borderless text-end">
                    <tr>
                        <td class="fw-bold text-muted">Subtotal:</td>
                        <td><?= $currency ?><?= number_format($sale['subtotal'], 2) ?></td>
                    </tr>
                    <?php if ($sale['discount_amount'] > 0): ?>
                    <tr>
                        <td class="fw-bold text-danger">Global Discount:</td>
                        <td class="text-danger">-<?= $currency ?><?= number_format($sale['discount_amount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <td class="fw-bold fs-5">TOTAL:</td>
                        <td class="fw-bold fs-5 text-success"><?= $currency ?><?= number_format($sale['grand_total'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="developer-footer">
            XENTRAPOS • DEVELOPED BY THILINA DIAS • GITHUB.COM/THILINADIAS
        </div>
    </div>
</div>

<script>
    // Automatically open print dialog when loaded
    window.onload = () => { window.print(); }
</script>
</body>
</html>
