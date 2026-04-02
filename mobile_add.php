<?php
// C:\xampp\htdocs\pos\mobile_add.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent']);
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0"><i class="bi bi-phone"></i> Mobile Add Device</h3>
            <a href="/pos/products.php" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
        
        <div id="alertContainer"></div>

        <div class="card shadow-sm border-0 root-mobile-card">
            <div class="card-body">
                
                <!-- Barcode Scanner Region -->
                <div class="mb-4 text-center">
                    <div id="reader" style="width: 100%; border-radius: 8px; overflow: hidden;" class="mb-2"></div>
                    <button id="startScanBtn" class="btn btn-dark w-100 fw-bold">
                        <i class="bi bi-camera me-1"></i> Start Barcode Scanner
                    </button>
                    <button id="stopScanBtn" class="btn btn-danger w-100 fw-bold d-none">
                        <i class="bi bi-stop-circle me-1"></i> Stop Scanner
                    </button>
                </div>

                <hr>

                <!-- Product Form -->
                <form id="mobileProductForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold font-sm">Barcode</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-upc"></i></span>
                            <input type="text" class="form-control" name="barcode" id="barcode" placeholder="Scan or type...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Product Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Price *</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" value="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select class="form-select" name="category_id" id="category_id">
                            <option value="">Loading...</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Take Photo (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-primary"><i class="bi bi-camera"></i></span>
                            <input type="file" class="form-control" name="image" accept="image/*" capture="environment">
                        </div>
                        <small class="text-muted d-block mt-1">Uses mobile camera to snap product image.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold" id="saveProductBtn">
                        <i class="bi bi-cloud-arrow-up"></i> Save Product
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Load html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="/pos/assets/js/mobile_scanner.js"></script>

<?php require_once 'includes/footer.php'; ?>
