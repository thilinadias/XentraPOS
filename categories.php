<?php
// C:\xampp\htdocs\pos\categories.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent', 'viewer']); // All can view, JS will hide edit buttons if not authorized (API blocks too)
$can_edit = in_array($_SESSION['role'], ['super_admin', 'agent']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Categories</h2>
    <?php if ($can_edit): ?>
    <button class="btn btn-primary fw-bold" id="addBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Category
    </button>
    <?php endif; ?>
</div>

<div id="alertContainer"></div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th width="10%">ID</th>
                <th width="30%">Name</th>
                <th width="40%">Description</th>
                <th width="20%">Actions</th>
            </tr>
        </thead>
        <tbody id="categoryTableBody">
            <tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
        </tbody>
    </table>
</div>

<?php if ($can_edit): ?>
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="categoryForm">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="modalTitle">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" id="cat_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="cat_desc" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Category</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="/pos/assets/js/categories.js"></script>

<?php require_once 'includes/footer.php'; ?>
