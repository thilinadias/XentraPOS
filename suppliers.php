<?php
// C:\xampp\htdocs\pos\suppliers.php
$require_login = true;
require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Supplier Hub</h2>
        <p class="text-muted">Manage your vendors and receive new inventory stock.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary fw-bold" onclick="openAddSupplierModal()">
            <i class="bi bi-patch-plus me-1"></i> Add Supplier
        </button>
        <button class="btn btn-dark fw-bold" onclick="openStockInModal()">
            <i class="bi bi-box-seam me-1"></i> Quick Stock-In
        </button>
    </div>
</div>

<div id="supplierAlert"></div>

<div class="row g-4">
    <!-- Supplier List -->
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold">Active Suppliers</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTableBody">
                            <tr><td colspan="5" class="text-center py-4 text-muted">Loading suppliers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form id="supplierForm">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="supplierModalTitle">New Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="supId">
          <div class="mb-3">
            <label class="form-label fw-bold small">Company Name *</label>
            <input type="text" id="supName" class="form-control" required placeholder="e.g. Acme Phones">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Contact Person</label>
            <input type="text" id="supContact" class="form-control" placeholder="John Doe">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Phone Number</label>
            <input type="text" id="supPhone" class="form-control" placeholder="+123456789">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-bold" id="saveSupBtn">Add Vendor</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Stock-In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form id="stockInForm">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Quick Stock-In (Inventory)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="small text-muted mb-4">Use this to record new stock arrival and update the base cost price.</p>
          <div class="mb-3">
            <label class="form-label fw-bold small">Select Product</label>
            <select id="stockInProdId" class="form-select" required>
                <option value="">Choose product...</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Supplier</label>
            <select id="stockInSupId" class="form-select">
                <option value="">Choose supplier...</option>
            </select>
          </div>
          <div class="row g-3">
            <div class="col-6">
                <label class="form-label fw-bold small">Quantity Received</label>
                <input type="number" id="stockInQty" class="form-control" required min="1" placeholder="0">
            </div>
            <div class="col-6">
                <label class="form-label fw-bold small">Unit Cost Price (<?= $currency ?>)</label>
                <input type="number" step="0.01" id="stockInCost" class="form-control" required placeholder="0.00">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark fw-bold" id="saveStockBtn">Process Stock-In</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Purchase History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Supplier Purchase History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="historySupplierHeader" class="mb-3"></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Product Received</th>
                        <th>Qty</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/suppliers.js?v=2.1.9"></script>

<?php require_once 'includes/footer.php'; ?>
