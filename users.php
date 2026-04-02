<?php
// C:\xampp\htdocs\pos\users.php
$require_login = true;
require_once 'includes/header.php';
require_super_admin(); // Will kill script if not super admin
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">User Management</h2>
    <button class="btn btn-primary fw-bold" id="addUserBtn">
        <i class="bi bi-person-plus me-1"></i> Add New User
    </button>
</div>

<div id="userAlertContainer"></div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="20%">Username</th>
                <th width="15%">Role</th>
                <th width="15%">Status</th>
                <th width="20%">Created At</th>
                <th width="25%">Actions</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            <!-- Populated via JS -->
            <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="userForm">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="modalTitle">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" required>
                <div id="passwordHelp" class="form-text text-warning" style="display:none;">Leave blank to keep existing password.</div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" required>
                    <option value="super_admin">Super Admin</option>
                    <option value="agent">Agent (POS Sales)</option>
                    <option value="auditor">Auditor (Reports Only)</option>
                    <option value="viewer">Viewer (Read Only)</option>
                </select>
                <div class="form-text">Controls what the user can access.</div>
            </div>

            <div class="mb-3" id="statusContainer" style="display:none;">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold" id="saveUserBtn">Save</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="/pos/assets/js/users.js"></script>

<?php require_once 'includes/footer.php'; ?>
