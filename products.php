<?php
// C:\xampp\htdocs\pos\products.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent', 'viewer']);
$can_edit = in_array($_SESSION['role'], ['super_admin', 'agent']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 bg-white p-4 rounded-4 shadow-sm mx-0">
    <div>
        <h2 class="fw-bold-700 mb-1">Inventory Master</h2>
        <p class="text-muted mb-0 small">Manage your product catalog, stock levels, and barcodes.</p>
    </div>
    
    <?php if ($can_edit): ?>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <!-- CSV Import -->
        <button class="btn btn-outline-success border-2 fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import CSV
        </button>
        <!-- Desktop Add -->
        <button class="btn btn-primary fw-bold px-3 py-2 shadow-sm" id="addBtn">
            <i class="bi bi-plus-lg me-1"></i> Add New Product
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="alertContainer"></div>

<!-- Desktop Inventory Table -->
<div class="premium-card overflow-hidden mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4"># ID</th>
                <th class="py-3 text-center">Img</th>
                <th class="py-3">Name</th>
                <th class="py-3 text-center">Category</th>
                <th class="py-3">Barcode</th>
                <th class="py-3 text-end pe-3">Cost (<?= $currency ?>)</th>
                <th class="py-3 text-end pe-3">Price (<?= $currency ?>)</th>
                <th class="py-3 text-center">Stock</th>
                <th class="py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody id="productTableBody">
            <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
        </tbody>
    </table>
</div>

<?php if ($can_edit): ?>
<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <form id="productForm" enctype="multipart/form-data">
          
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold text-primary" id="modalTitle">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Cost (<?= $currency ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="cost_price" id="cost_price" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Price (<?= $currency ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select class="form-select" name="category_id" id="category_id">
                        <option value="">Loading...</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Barcode / SKU</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" class="form-control" name="barcode" id="barcode" placeholder="(Optional, must be unique)">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Initial Stock <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" value="0" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Product Description</label>
                    <textarea class="form-control" name="description" id="description" rows="2" placeholder="Optional details..."></textarea>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Product Image</label>
                    <input type="file" class="form-control" name="image" id="image" accept="image/*">
                    <div id="imagePreview" class="text-center mt-2"></div>
                </div>
            </div>
          </div>
          
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4" id="saveProductBtn">Save Product</button>
          </div>
      </form>
    </div>
  </div>
</div>
<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow-lg">
      <form id="importForm" enctype="multipart/form-data">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title fw-bold">Bulk Import Products</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p class="small text-muted mb-4">Step 1: Download the template. Step 2: Fill it. Step 3: Upload it back.</p>
            
            <a href="/pos/api/products/sample_csv.php" class="btn btn-light w-100 mb-4 border py-3 text-success fw-bold">
                <i class="bi bi-cloud-download fs-4 d-block mb-1"></i> Download CSV Template
            </a>

            <div class="mb-3">
                <label class="form-label fw-bold">Select CSV File</label>
                <input type="file" class="form-control" name="csv_file" accept=".csv" required>
            </div>
            <div id="importAlert"></div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success fw-bold px-4" id="processImportBtn">Process Upload</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="/pos/assets/js/products.js"></script>

<?php require_once 'includes/footer.php'; ?>
