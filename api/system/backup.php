<?php
// C:\xampp\htdocs\pos\api\system\backup.php
require_once '../../includes/auth_middleware.php';
require_super_admin(); // Only admins can backup

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pos_db';

// Date for filename
$date = date('Y-m-d_H-i-s');
$filename = "pos_db_backup_{$date}.sql";

// Path to mysqldump in XAMPP
$mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"{$filename}\"");

// Execute mysqldump
$command = "\"{$mysqldumpPath}\" --user={$db_user} --password=\"{$db_pass}\" --host={$db_host} {$db_name}";

// Output directly to stream
passthru($command);

log_activity('System Backup', "Database backup downloaded by admin.");
?>
