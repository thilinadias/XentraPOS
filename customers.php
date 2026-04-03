<?php
// C:\xampp\htdocs\pos\customers.php
$require_login = true;
require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Customer CRM & Ledger</h2>
        <p class="text-muted">Manage your clients and track outstanding debt.</p>
    </div>
    <button class="btn btn-primary fw-bold" onclick="openAddModal()">
        <i class="bi bi-person-plus me-1"></i> Add New Customer
    </button>
</div>

<div id="customerAlert"></div>

<div class="row g-4">
    <!-- Customer List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold">Active Customer Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client Name</th>
                                <th>Phone</th>
                                <th class="text-end">Current Balance</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <tr><td colspan="4" class="text-center py-4 text-muted">Loading customers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Debt Summary Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <h6 class="text-muted text-uppercase fw-bold small mb-2">Total Outstanding Debt</h6>
                <h2 class="fw-bold text-danger" id="totalDebt"><?= $currency ?>0.00</h2>
                <hr>
                <p class="small text-muted mb-0">This represents all credit sales minus customer payments.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i> Record Repayment</h5>
                <p class="small opacity-75 mb-4">When a customer pays off their debt, log it here to reduce their balance.</p>
                <form id="paymentForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Customer</label>
                        <select class="form-select form-select-sm" id="payCustomerId" required>
                            <option value="">Choose...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amount Paid (<?= $currency ?>)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="payAmount" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Method</label>
                        <select class="form-select form-select-sm" id="payMethod">
                            <option value="Cash">Cash</option>
                            <option value="Card">Bank Transfer / Card</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-light btn-sm w-100 fw-bold border-0 text-primary py-2">Confirm Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form id="customerForm">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="customerModalTitle">New Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="custId">
          <div class="mb-3">
            <label class="form-label fw-bold small">Full Name *</label>
            <input type="text" id="custName" class="form-control" placeholder="John Doe" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Phone Number *</label>
            <input type="text" id="custPhone" class="form-control" placeholder="+123456789" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Email (Optional)</label>
            <input type="email" id="custEmail" class="form-control" placeholder="john@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Address</label>
            <textarea id="custAddress" class="form-control" rows="2" placeholder="Street, City..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-bold px-4 px-3" id="saveCustBtn">Create Profile</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Ledger History Modal -->
<div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Customer Ledger History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="ledgerCustomerHeader" class="mb-3"></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody id="ledgerTableBody">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/customers.js?v=2.1.9"></script>

<?php require_once 'includes/footer.php'; ?>
