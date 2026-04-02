<?php
// C:\xampp\htdocs\pos\includes\header.php
require_once 'auth_middleware.php';

// If require_login_flag is set, ensure logged in
if (isset($require_login) && $require_login) {
    require_login();
}

// Global Settings fetching
require_once __DIR__ . '/../config/database.php';
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$currency = $settings['currency_symbol'] ?? '$';
$low_stock = (int)($settings['low_stock_threshold'] ?? 10);

// 🕵️ Automated Summary Triggers (Passive Cron)
$trigger_daily = false;
$trigger_monthly = false;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last_month = date('Y-m', strtotime('first day of last month'));

if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    if (($settings['email_daily_summary_enabled'] ?? '0') === '1') {
        // Daily Check
        if (($settings['last_daily_summary_date'] ?? '') !== $yesterday) {
            $trigger_daily = true;
        }
        // Monthly Check
        if (($settings['last_monthly_summary_month'] ?? '') !== $last_month) {
            $trigger_monthly = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XentraPOS</title>
    <!-- Use Bootstrap latest stable from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/pos/assets/css/style.css">
    <script>
      window.POS_SETTINGS = {
        currency: '<?php echo $currency; ?>',
        low_stock: <?php echo $low_stock; ?>
      };

      <?php if ($trigger_daily): ?>
      fetch('/pos/api/system/daily_cron.php?date=<?php echo $yesterday; ?>').then(r => r.json()).catch(err => console.error('Daily Trigger Failed'));
      <?php endif; ?>

      <?php if ($trigger_monthly): ?>
      fetch('/pos/api/system/monthly_cron.php?month=<?php echo $last_month; ?>').then(r => r.json()).catch(err => console.error('Monthly Trigger Failed'));
      <?php endif; ?>
    </script>
</head>
<body class="bg-light">

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/pos/dashboard.php">
        <i class="bi bi-cart3"></i> XentraPOS
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/pos/dashboard.php">Dashboard</a>
        </li>
        <?php if (in_array($_SESSION['role'], ['super_admin', 'agent'])): ?>
        <li class="nav-item">
          <a class="nav-link text-warning fw-bold" href="/pos/pos.php"><i class="bi bi-display"></i> POS Terminal</a>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="salesDropdown" role="button" data-bs-toggle="dropdown">
            Sales
          </a>
          <ul class="dropdown-menu shadow border-0">
            <li><a class="dropdown-item" href="/pos/sales.php"><i class="bi bi-clock-history me-2 text-muted"></i>Sales History</a></li>
            <?php if (in_array($_SESSION['role'], ['super_admin', 'agent'])): ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pos/manual_invoice.php"><i class="bi bi-file-earmark-plus me-2 text-muted"></i>Create Manual Invoice</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pos/customers.php"><i class="bi bi-people me-2 text-muted"></i>Customer CRM</a></li>
            <?php endif; ?>
            <?php if (in_array($_SESSION['role'], ['super_admin', 'auditor'])): ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pos/reports.php"><i class="bi bi-graph-up me-2 text-muted"></i>Advanced Reports</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown">
            Inventory
          </a>
          <ul class="dropdown-menu shadow border-0">
            <li><a class="dropdown-item" href="/pos/categories.php"><i class="bi bi-tags me-2 text-muted"></i>Categories</a></li>
            <li><a class="dropdown-item" href="/pos/products.php"><i class="bi bi-box-seam me-2 text-muted"></i>Products Master</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pos/suppliers.php"><i class="bi bi-truck me-2 text-muted"></i>Suppliers & Stock-In</a></li>
            <?php if (in_array($_SESSION['role'], ['super_admin', 'agent'])): ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pos/mobile_add.php"><i class="bi bi-phone me-2 text-muted"></i>Mobile App View</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
        <li class="nav-item">
          <a class="nav-link" href="/pos/users.php">Manage Users</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/pos/settings.php"><i class="bi bi-gear"></i> Settings</a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center text-white">
        <span class="me-3">
            <i class="bi bi-person-circle"></i> 
            <?php echo htmlspecialchars($_SESSION['username']); ?> 
            <span class="badge bg-secondary ms-1"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></span>
        </span>
        <button id="logoutBtn" class="btn btn-sm btn-outline-light">Logout</button>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<!-- Main Content Container (Flex-grow area) -->
<main class="container-fluid main-scrollable">
