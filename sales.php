<?php
// C:\xampp\htdocs\pos\sales.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent', 'auditor']);
?>

<div class="row mb-5 align-items-center bg-white p-4 rounded-4 shadow-sm mx-0">
    <div class="col-sm-6">
        <h2 class="fw-bold-700 mb-1">Sales History</h2>
        <p class="text-muted mb-0 small">Review past transactions, reprint receipts, or process refunds.</p>
    </div>
</div>

<div class="premium-card overflow-hidden mb-5">
    <div class="card-body p-0">
        <div id="alertContainer"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Customer Name</th>
                        <th class="py-3 text-center">Payment Type</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Cashier</th>
                        <th class="text-end py-3 pe-4">Total Amount</th>
                        <th class="text-center py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <tr><td colspan="7" class="text-center">Loading sales...</td></tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const tbody = document.getElementById('salesTableBody');
    try {
        const res = await apiCall('api/sales/list.php');
        tbody.innerHTML = '';
        
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No sales found.</td></tr>';
            return;
        }

        res.data.forEach(sale => {
            const date = new Date(sale.created_at).toLocaleString();
            const customer = sale.customer_name || '<em class="text-muted">Walk-in</em>';
            const cur = window.POS_SETTINGS.currency || '$';
            
            let badgeClass = 'bg-secondary';
            if (sale.payment_type === 'Cash') badgeClass = 'bg-success';
            if (sale.payment_type === 'Card') badgeClass = 'bg-info text-dark';
            if (sale.payment_type === 'Credit') badgeClass = 'bg-warning text-dark';

            let statusBadge = (sale.status === 'Refunded') ? '<span class="badge bg-danger">Refunded</span>' : '<span class="badge bg-success">Paid</span>';
            const canRefund = (sale.status !== 'Refunded' && '<?php echo $_SESSION['role']; ?>' === 'super_admin');
            
            tbody.innerHTML += `
                <tr class="${sale.status === 'Refunded' ? 'opacity-50' : ''}">
                    <td class="fw-bold text-primary">${sale.invoice_number}</td>
                    <td>${date}</td>
                    <td>${customer}</td>
                    <td><span class="badge ${badgeClass}">${sale.payment_type}</span></td>
                    <td>${statusBadge}</td>
                    <td><small>${sale.cashier}</small></td>
                    <td class="text-end fw-bold">${cur}${parseFloat(sale.grand_total).toFixed(2)}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="/pos/receipt.php?id=${sale.id}" target="_blank" class="btn btn-outline-dark" title="Print Thermal Receipt">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="/pos/invoice.php?id=${sale.id}" target="_blank" class="btn btn-outline-primary" title="Print A4 Invoice">
                                <i class="bi bi-printer"></i>
                            </a>
                            ${canRefund ? `
                                <button class="btn btn-outline-danger" title="Issue Refund" onclick="issueRefund(${sale.id})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Refund
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error: ${e.message}</td></tr>`;
    }
});

async function issueRefund(saleId) {
    // Save current saleId to the confirm button or global variable
    const confirmBtn = document.getElementById('confirmRefundBtn');
    confirmBtn.onclick = async () => {
        const reason = document.getElementById('refund_reason').value;
        const notes = document.getElementById('refund_notes').value;

        if(!reason) {
            alert('Please select a refund reason.');
            return;
        }

        const originalText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const formData = new URLSearchParams();
            formData.append('sale_id', saleId);
            formData.append('reason', reason);
            formData.append('notes', notes);
            
            const res = await fetch('/pos/api/sales/refund.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(!data.success) throw new Error(data.message);
            
            alert("Refund successful!");
            location.reload();
        } catch(err) {
            alert("Refund Failed: " + err.message);
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    };

    // Show Modal
    const modal = new bootstrap.Modal(document.getElementById('refundModal'));
    modal.show();
}
</script>

<!-- Refund Reason Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise me-2"></i> Issue Refund</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Why are you refunding this?</label>
                    <select class="form-select" id="refund_reason">
                        <option value="" selected disabled>Select a reason...</option>
                        <option value="Defective / Damaged Product">Defective / Damaged Product</option>
                        <option value="Customer Changed Mind">Customer Changed Mind</option>
                        <option value="Incorrect Item Sold">Incorrect Item Sold</option>
                        <option value="Price Mismatch">Price Mismatch</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted text-uppercase">Additional Notes (Optional)</label>
                    <textarea class="form-control" id="refund_notes" rows="3" placeholder="Describe the reason in detail..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-bold" id="confirmRefundBtn">Confirm Refund</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
