<?php
// C:\xampp\htdocs\pos\dashboard.php
$require_login = true;
require_once 'includes/header.php';
?>

<div class="row mb-5 align-items-center bg-white p-4 rounded-4 shadow-sm mx-0">
    <div class="col-md-6">
        <h2 class="fw-bold-700 fs-1 mb-1 text-dark">Store Overview</h2>
        <p class="text-muted fs-5 mb-0">Welcome back, <span class="text-primary fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>. Monitoring your sales and stock levels.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <?php if (in_array($_SESSION['role'], ['super_admin', 'agent'])): ?>
                <a href="/pos/pos.php" class="btn btn-primary px-4 py-2 fw-bold"><i class="bi bi-display me-2"></i> Open POS Terminal</a>
                <a href="/pos/manual_invoice.php" class="btn btn-outline-primary px-4 py-2 fw-bold"><i class="bi bi-file-earmark-plus me-2"></i> New Manual Invoice</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI Cards Summary -->
<div class="row g-4 mb-5" id="statsCards">
    <div class="col-md-3">
        <div class="premium-card h-100 text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #1e40af 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-white text-uppercase fw-bold mb-2 opacity-75 small">Today's Revenue</h6>
                        <h2 class="fw-bold-700 mb-0" id="cardTodayRevenue">...</h2>
                    </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3"><i class="bi bi-currency-exchange fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="premium-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-2 small">Today's Sales</h6>
                        <h2 class="fw-bold-700 mb-0" id="cardTodaySales">0</h2>
                    </div>
                <div class="bg-primary bg-opacity-10 rounded-circle p-3"><i class="bi bi-cart-check text-primary fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="premium-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-2 small">Monthly Revenue</h6>
                        <h2 class="fw-bold-700 mb-0" id="cardMonthlyRevenue">$0.00</h2>
                    </div>
                <div class="bg-success bg-opacity-10 rounded-circle p-3"><i class="bi bi-calendar3 text-success fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="premium-card h-100 text-danger" style="background: #fffafa; border: 1px solid #fee2e2;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase opacity-75 mb-2 small">Low Stock Alerts</h6>
                        <h2 class="fw-bold-700 mb-0" id="cardLowStock">0</h2>
                    </div>
                <div class="bg-danger bg-opacity-10 rounded-circle p-3"><i class="bi bi-exclamation-triangle-fill fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Chart Section -->
    <div class="col-lg-8">
        <div class="premium-card h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Recent Sales Trend (Last 7 Days)</h5>
                <i class="bi bi-info-circle text-muted" title="Daily revenue from transactions"></i>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="col-lg-4">
        <div class="premium-card h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold">Urgent Restocking</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3">Product</th>
                                <th class="text-end py-3">Current Stock</th>
                            </tr>
                        </thead>
                    <tbody id="lowStockTbody">
                        <tr><td colspan="2" class="text-center py-4">Checking inventory...</td></tr>
                    </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-center border-0 py-3">
                <a href="/pos/products.php" class="text-decoration-none fw-bold small">Go to Products Master <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- Recent Sales List -->
    <div class="col-lg-8 mt-4 mb-5">
        <div class="premium-card">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold">Latest Transactions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4">Ref #</th>
                                <th class="py-3">Timestamp</th>
                                <th class="py-3">Customer</th>
                                <th class="py-3">Status</th>
                                <th class="text-end py-3">Total Amount</th>
                                <th class="text-center py-3">Action</th>
                              </tr>
                        </thead>
                    <tbody id="recentSalesTbody">
                        <tr><td colspan="5" class="text-center py-4">Loading sales data...</td></tr>
                    </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-center border-0 py-3">
                <a href="/pos/sales.php" class="text-decoration-none fw-bold small">View All Sales History <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- Reports & Analytics Quick Link -->
    <?php if (in_array($_SESSION['role'], ['super_admin', 'auditor'])): ?>
    <div class="col-lg-4 mt-4 mb-5">
        <div class="card border-0 shadow-sm h-100 bg-dark text-white overflow-hidden">
            <div class="card-body p-4 d-flex flex-column justify-content-center text-center position-relative" style="z-index: 1;">
                <i class="bi bi-graph-up-arrow mb-3 display-4 text-info"></i>
                <h4 class="fw-bold">Business Intelligence</h4>
                <p class="opacity-75 small">Deep-dive into your revenue, profit, and cashier performance analytics.</p>
                <a href="/pos/reports.php" class="btn btn-info fw-bold mt-2">Open Reports</a>
            </div>
            <!-- Decorative Icon -->
            <i class="bi bi-pie-chart position-absolute opacity-10" style="font-size: 10rem; bottom: -20px; right: -20px;"></i>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('/pos/api/dashboard/summary.php');
        const data = await res.json();
        
        if (!data.success) {
            console.error('Failed to load dashboard data');
            return;
        }

        const stats = data.data;
        const cur = window.POS_SETTINGS.currency || '$';

        // Card Updates
        document.getElementById('cardTodayRevenue').textContent = `${cur}${stats.today_revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        document.getElementById('cardTodaySales').textContent = stats.today_count;
        document.getElementById('cardMonthlyRevenue').textContent = `${cur}${stats.monthly_revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        document.getElementById('cardLowStock').textContent = stats.low_stock_count;

        // Low Stock Table
        const lowStockTbody = document.getElementById('lowStockTbody');
        if (stats.low_stock_items.length > 0) {
            lowStockTbody.innerHTML = '';
            stats.low_stock_items.forEach(item => {
                lowStockTbody.innerHTML += `
                    <tr>
                        <td>${item.name}</td>
                        <td class="text-end fw-bold text-danger">${item.stock_quantity}</td>
                    </tr>
                `;
            });
        } else {
            lowStockTbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-success"><i class="bi bi-check-circle me-1"></i> Stock levels healthy.</td></tr>';
        }

        // Recent Sales Table
        const recentTbody = document.getElementById('recentSalesTbody');
        if (stats.recent_sales.length > 0) {
            recentTbody.innerHTML = '';
            stats.recent_sales.forEach(sale => {
                let statusBadge = '';
                if(sale.status === 'Completed') statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2">Sale</span>';
                else if(sale.status === 'Refunded') statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">Refund</span>';
                else statusBadge = `<span class="badge bg-light text-muted border px-2">${sale.status}</span>`;

                recentTbody.innerHTML += `
                    <tr>
                        <td class="fw-bold px-4">${sale.invoice_number}</td>
                        <td>${new Date(sale.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} • ${new Date(sale.created_at).toLocaleDateString()}</td>
                        <td>${sale.customer_name || '<span class="text-muted">Walk-in</span>'}</td>
                        <td>${statusBadge}</td>
                        <td class="text-end fw-bold">${cur}${parseFloat(sale.grand_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-center">
                            <a href="/pos/invoice.php?id=${sale.id}" target="_blank" class="btn btn-sm btn-outline-primary px-3">Invoice</a>
                        </td>
                    </tr>
                `;
            });
        } else {
            recentTbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No transactions found.</td></tr>';
        }

        // Sales Line Chart
        const chartCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: stats.chart_data.map(d => new Date(d.date).toLocaleDateString('en-US', {weekday: 'short'})),
                datasets: [{
                    label: 'Revenue',
                    data: stats.chart_data.map(d => d.total),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#2563eb',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { display: false },
                        ticks: { callback: v => cur + v }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

    } catch (err) {
        console.error('Error fetching dashboard summary:', err);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
