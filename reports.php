<?php
// C:\xampp\htdocs\pos\reports.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'auditor']);

$start_default = date('Y-m-d', strtotime('-30 days'));
$end_default = date('Y-m-d');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Business Intelligence Center</h2>
        <p class="text-muted">Comprehensive analytics for your business.</p>
    </div>
</div>

<div id="alertContainer"></div>

<!-- Nav Tabs -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="reportTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold px-4" id="sales-tab" data-bs-toggle="pill" data-bs-target="#salesContent" type="button" role="tab">
        <i class="bi bi-graph-up me-2"></i>Sales Performance
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold px-4" id="stock-tab" data-bs-toggle="pill" data-bs-target="#stockContent" type="button" role="tab">
        <i class="bi bi-box-seam me-2"></i>Stock & Valuation
    </button>
  </li>
  <?php if ($_SESSION['role'] === 'super_admin'): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold px-4" id="log-tab" data-bs-toggle="pill" data-bs-target="#logContent" type="button" role="tab">
        <i class="bi bi-shield-check me-2"></i>Activity Audit
    </button>
  </li>
  <?php endif; ?>
</ul>

<div class="tab-content" id="reportTabsContent">
  
  <!-- SALES TAB -->
  <div class="tab-pane fade show active" id="salesContent" role="tabpanel">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Filter Sales Period</h5>
            <div class="d-flex gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">From</span>
                    <input type="date" id="startDate" class="form-control" value="<?= $start_default ?>">
                    <span class="input-group-text">To</span>
                    <input type="date" id="endDate" class="form-control" value="<?= $end_default ?>">
                    <button class="btn btn-primary" onclick="loadSalesReports()"><i class="bi bi-filter"></i> Apply</button>
                </div>
                <button class="btn btn-success btn-sm fw-bold" onclick="exportSalesCSV()"><i class="bi bi-download"></i> Export Sales</button>
            </div>
        </div>
    </div>

    <!-- Sales KPI Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <h6 class="text-muted fw-bold text-uppercase small mb-2">Net Revenue</h6>
                <h3 class="fw-bold mb-0 text-primary" id="kpiRevenue"><?= $currency ?>0.00</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <h6 class="text-muted fw-bold text-uppercase small mb-2">Net Profit</h6>
                <h3 class="fw-bold mb-0 text-success" id="kpiProfit"><?= $currency ?>0.00</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm text-center p-3">
                <h6 class="text-muted fw-bold text-uppercase small mb-2">Total Orders</h6>
                <h3 class="fw-bold mb-0" id="kpiOrders">0</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm text-center p-3">
                <h6 class="text-muted fw-bold text-uppercase small mb-2">Avg. Order</h6>
                <h3 class="fw-bold mb-0 text-info" id="kpiAvg"><?= $currency ?>0.00</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm text-center p-3">
                <h6 class="text-primary fw-bold text-uppercase small mb-2">Total Refunded</h6>
                <h3 class="fw-bold mb-0 text-danger" id="kpiRefunds"><?= $currency ?>0.00</h3>
                <small class="text-muted" id="kpiRefundCount">0 cases</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Revenue vs Profit Trend</h5></div>
                <div class="card-body"><canvas id="trendChart" height="300"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Category Sales</h5></div>
                <div class="card-body"><canvas id="categorySalesChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Top Products</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Name</th><th class="text-center">Qty</th><th class="text-end">Revenue</th></tr></thead><tbody id="topProductsTbody"></tbody></table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Cashier Ranking</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Name</th><th class="text-center">Orders</th><th class="text-end">Revenue</th></tr></thead><tbody id="cashierTbody"></tbody></table>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Refund Reason Analysis</h5>
                    <span class="badge bg-danger-subtle text-danger">Audit Critical</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30%">Refund Reason</th>
                                    <th width="45%">Products Involved</th>
                                    <th class="text-end" width="25%">Capital Reversed</th>
                                </tr>
                            </thead>
                            <tbody id="refundReasonTbody">
                                <tr><td colspan="3" class="text-center py-3 text-muted">No refunds in this period.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- STOCK TAB -->
  <div class="tab-pane fade" id="stockContent" role="tabpanel">
    <div class="row g-4 mb-4" id="stockKpiRow">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 bg-primary text-white">
                <h6 class="text-white text-uppercase fw-bold small mb-2 opacity-75">Inventory Value (Cost)</h6>
                <h3 class="fw-bold mb-0" id="stockValCost"><?= $currency ?>0.00</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 bg-dark text-white">
                <h6 class="text-white text-uppercase fw-bold small mb-2 opacity-75">Retail Value</h6>
                <h3 class="fw-bold mb-0" id="stockValRetail"><?= $currency ?>0.00</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3 bg-success text-white">
                <h6 class="text-white text-uppercase fw-bold small mb-2 opacity-75">Potential Future Profit</h6>
                <h3 class="fw-bold mb-0" id="stockValProfit"><?= $currency ?>0.00</h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Investment by Category</h5></div>
                <div class="card-body"><canvas id="investmentBarChart" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold">Inventory Health</h5></div>
                <div class="card-body"><canvas id="healthDonutChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Critical Low-Stock Inventory</h5>
                    <button class="btn btn-success btn-sm fw-bold" onclick="exportStockCSV()"><i class="bi bi-download"></i> Full Stock CSV</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Product Name</th><th class="text-center">Current Stock</th><th class="text-end">Unit Price</th><th class="text-end">Status</th></tr></thead>
                            <tbody id="criticalStockTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- AUDIT LOG TAB -->
  <div class="tab-pane fade" id="logContent" role="tabpanel">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Recent System Actions</h5>
            <span class="badge bg-light text-muted fw-normal">Showing last 200 actions</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">User</th>
                            <th width="20%">Action</th>
                            <th width="45%">Details</th>
                            <th width="20%">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <tr><td colspan="4" class="text-center py-4">Loading logs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart instances
let trendChart = null;
let categorySalesChart = null;
let investmentBarChart = null;
let healthDonutChart = null;

async function loadSalesReports() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    const cur = window.POS_SETTINGS.currency || '$';

    try {
        const res = await fetch(`/pos/api/reports/full_report.php?start_date=${start}&end_date=${end}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        const s = data.data;

        // KPI Highlights
        document.getElementById('kpiRevenue').textContent = `${cur}${s.summary.total_revenue.toLocaleString()}`;
        document.getElementById('kpiProfit').textContent = `${cur}${s.summary.total_profit.toLocaleString()}`;
        document.getElementById('kpiOrders').textContent = s.summary.sales_count;
        document.getElementById('kpiAvg').textContent = `${cur}${s.summary.avg_order.toFixed(2)}`;
        
        // New Refund KPIs
        document.getElementById('kpiRefunds').textContent = `${cur}${s.summary.total_refunded.toLocaleString()}`;
        document.getElementById('kpiRefundCount').textContent = `${s.summary.refund_count} cases`;

        // Sales Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        if(trendChart) trendChart.destroy();
        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: s.daily_trend.map(d => d.date),
                datasets: [
                    { label: 'Revenue', data: s.daily_trend.map(d => d.revenue), borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.3 },
                    { label: 'Profit', data: s.daily_trend.map(d => d.profit), borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.3 }
                ]
            }
        });

        // Category Sales (Doughnut)
        const catCtx = document.getElementById('categorySalesChart').getContext('2d');
        if(categorySalesChart) categorySalesChart.destroy();
        categorySalesChart = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: s.category_performance.map(c => c.category_name),
                datasets: [{ data: s.category_performance.map(c => c.revenue), backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545'] }]
            }
        });

        renderTable('topProductsTbody', s.top_products, p => `<tr><td class="fw-bold">${p.name}</td><td class="text-center">${p.total_qty}</td><td class="text-end fw-bold">${cur}${parseFloat(p.total_revenue).toFixed(2)}</td></tr>`);
        renderTable('cashierTbody', s.cashier_performance, u => `<tr><td>${u.username}</td><td class="text-center">${u.sales_count}</td><td class="text-end fw-bold">${cur}${parseFloat(u.total_revenue).toFixed(2)}</td></tr>`);
        
        // New Refund Reason Table (Now shows products)
        renderTable('refundReasonTbody', s.refund_reasons, r => `
            <tr>
                <td class="fw-bold text-danger">${r.refund_reason || '<span class="text-muted italic">Not specified</span>'}</td>
                <td class="text-muted small">${r.product_names || 'Custom / Manual Item'}</td>
                <td class="text-end fw-bold text-dark">${cur}${parseFloat(r.amount).toFixed(2)}</td>
            </tr>
        `);

    } catch (err) { showAlert(err.message, 'danger', 'alertContainer'); }
}

async function loadStockReports() {
    const cur = window.POS_SETTINGS.currency || '$';
    try {
        const res = await fetch('/pos/api/reports/stock_metrics.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        const st = data.data;

        // Stock Valuation KPIs
        document.getElementById('stockValCost').textContent = `${cur}${st.valuation.total_cost.toLocaleString()}`;
        document.getElementById('stockValRetail').textContent = `${cur}${st.valuation.total_retail.toLocaleString()}`;
        document.getElementById('stockValProfit').textContent = `${cur}${st.valuation.potential_profit.toLocaleString()}`;

        // Investment Chart (Vertical Bar)
        const barCtx = document.getElementById('investmentBarChart').getContext('2d');
        if(investmentBarChart) investmentBarChart.destroy();
        investmentBarChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: st.category_investment.map(c => c.category_name),
                datasets: [{ label: 'Investment Value', data: st.category_investment.map(c => c.investment), backgroundColor: '#0d6efd' }]
            }
        });

        // Health Donut
        const donutCtx = document.getElementById('healthDonutChart').getContext('2d');
        if(healthDonutChart) healthDonutChart.destroy();
        healthDonutChart = new Chart(donutCtx, {
            type: 'pie',
            data: {
                labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                datasets: [{ data: [st.health.healthy, st.health.low_stock, st.health.out_of_stock], backgroundColor: ['#198754', '#ffc107', '#dc3545'] }]
            }
        });

        // Critical Table
        renderTable('criticalStockTbody', st.critical_list, p => {
            const badge = p.stock_quantity == 0 ? '<span class="badge bg-danger">OUT OF STOCK</span>' : '<span class="badge bg-warning text-dark">CRITICAL</span>';
            return `<tr><td class="fw-bold">${p.name}</td><td class="text-center">${p.stock_quantity}</td><td class="text-end">${cur}${parseFloat(p.price).toFixed(2)}</td><td class="text-end">${badge}</td></tr>`;
        });

    } catch (err) { showAlert(err.message, 'danger', 'alertContainer'); }
}

async function loadActivityLogs() {
    const tbody = document.getElementById('logTableBody');
    try {
        const res = await fetch('api/system/audit_logs.php', {
            headers: { 'Accept': 'application/json' }
        });
        
        if (res.status === 401) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-warning">
                <i class="bi bi-person-lock fs-2"></i><br>
                <b>Session Expired.</b><br>
                <a href="/pos/" class="btn btn-sm btn-outline-warning mt-2">Login Again</a>
            </td></tr>`;
            return;
        }

        const contentType = res.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const errorBody = await res.text();
            console.error("Non-JSON detected:", errorBody);
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">
                <i class="bi bi-exclamation-octagon fs-2"></i><br>
                <b>Server communication error.</b><br>
                <small class="text-muted">Status: ${res.status}. Please check your login session.</small>
            </td></tr>`;
            return;
        }

        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        tbody.innerHTML = '';
        if (data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No activity logs found.</td></tr>';
            return;
        }

        data.data.forEach(l => {
            const date = new Date(l.created_at).toLocaleString();
            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold text-primary">${l.username}</td>
                    <td><span class="badge bg-light text-dark border">${l.action}</span></td>
                    <td class="small text-wrap">${l.description || '-'}</td>
                    <td class="text-muted small">${date}</td>
                </tr>
            `;
        });
    } catch (err) { 
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger"><b>Error:</b> ${err.message}</td></tr>`;
    }
}

function renderTable(id, items, templateFn) {
    const tbody = document.getElementById(id);
    tbody.innerHTML = items.length ? '' : '<tr><td colspan="4" class="text-center py-4">No records.</td></tr>';
    items.forEach(i => { tbody.innerHTML += templateFn(i); });
}

function exportSalesCSV() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    window.location.href = `/pos/api/reports/export.php?type=sales&start_date=${start}&end_date=${end}`;
}

function exportStockCSV() {
    window.location.href = `/pos/api/reports/export.php?type=stock`;
}

// Tab Switching logic
if(document.getElementById('stock-tab')) document.getElementById('stock-tab').addEventListener('click', loadStockReports);
if(document.getElementById('sales-tab')) document.getElementById('sales-tab').addEventListener('click', loadSalesReports);
if(document.getElementById('log-tab')) document.getElementById('log-tab').addEventListener('click', loadActivityLogs);

document.addEventListener('DOMContentLoaded', loadSalesReports);
</script>

<?php require_once 'includes/footer.php'; ?>
