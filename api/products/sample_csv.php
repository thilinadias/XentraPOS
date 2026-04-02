<?php
// C:\xampp\htdocs\pos\api\products\sample_csv.php
require_once '../../includes/auth_middleware.php';
require_role(['super_admin', 'agent']);

$filename = "pos_product_template.csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, ['Name', 'Category', 'Barcode', 'Cost_Price', 'Sale_Price', 'Stock_Quantity', 'Description']);

// Sample Row
fputcsv($output, ['Example Product', 'Electronics', '123456789', '50.00', '100.00', '10', 'Example product description']);

fclose($output);
exit();
?>
