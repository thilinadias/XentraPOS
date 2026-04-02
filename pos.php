<?php
// C:\xampp\htdocs\pos\pos.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent']);

// Fetch all products for the grid (categorized)
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id, name, price, stock_quantity, image_path, category_id FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();
?>

<!-- Minimalistic full-width UI override -->
<style>
    /* POS Specific overrides to maximize space within the new shell */
    main.main-scrollable { padding-top: 0 !important; padding-bottom: 0 !important; display: flex; flex-direction: column; }
    
    .pos-layout { flex: 1; display: flex; flex-direction: row; overflow: hidden; }
    .product-grid { flex: 1; height: 100%; overflow-y: auto; padding: 20px; background: #fdfdfd; border-right: 1px solid #eee; }
    .cart-sidebar { width: 450px; background: #fff; display: flex; flex-direction: column; height: 100%; box-shadow: -5px 0 20px rgba(0,0,0,0.03); }
    .cart-items { flex: 1; overflow-y: auto; padding: 15px; background: #fff; }
    .cart-totals { padding: 20px; background: #fafafa; border-top: 1px solid #eee; }
    
    /* Category Scrollbar Styling */
    #categoryFilterTabs::-webkit-scrollbar { height: 4px; }
    #categoryFilterTabs::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
    #categoryFilterTabs { scrollbar-width: thin; padding-bottom: 8px; border-bottom: 1px solid #f8f9fa; }

    .product-card { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: 1px solid #f1f1f1 !important; border-radius: 12px; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0,0,0,0.06) !important; border-color: var(--primary-color) !important; }
</style>

<div id="alertContainer"></div>

<div class="pos-layout">
    <!-- Left Side: Product Search & Grid -->
    <div class="product-grid">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form id="barcodeSearchForm" class="d-flex">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="posBarcode" class="form-control border-start-0" placeholder="Scan Barcode or Type Code..." autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary ms-2 px-4 shadow-sm" id="searchBtn">Add</button>
                </form>
            </div>
        </div>


        <!-- Category Filter Tabs -->
        <div class="mb-3 d-flex overflow-auto pb-2" id="categoryFilterTabs">
            <button class="btn btn-sm btn-dark me-2 border-0 fw-bold px-3 active" onclick="filterByCategory('all', this)">All</button>
            <?php 
                $stmtCats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
                while($cat = $stmtCats->fetch(PDO::FETCH_ASSOC)):
            ?>
            <button class="btn btn-sm btn-outline-secondary me-2 border-0 fw-bold px-3" onclick="filterByCategory(<?= $cat['id'] ?>, this)"><?= htmlspecialchars($cat['name']) ?></button>
            <?php endwhile; ?>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 pb-5" id="productGridGroup">
            <?php foreach($products as $p): 
                $img = $p['image_path'] ? '/pos/'.$p['image_path'] : 'https://placehold.co/150x150?text=No+Img';
            ?>
            <div class="col product-item" data-category="<?= $p['category_id'] ?? 'none' ?>" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                <div class="card h-100 product-card shadow-sm border-0" onclick="addProdToCart(<?php echo htmlspecialchars(json_encode([
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'stock' => $p['stock_quantity']
                ])); ?>)">
                    <img src="<?php echo $img; ?>" class="card-img-top" alt="Product" style="height: 120px; object-fit: contain; padding: 10px;">
                    <div class="card-body text-center py-2 bg-light">
                        <h6 class="card-title fw-bold mb-1 fs-6 text-truncate" title="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></h6>
                        <span class="badge bg-primary fs-6"><?= $currency ?><?= number_format($p['price'], 2) ?></span>
                        <div class="small text-muted mt-1">Stock: <?= $p['stock_quantity'] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Side: Live Checkout Cart -->
    <div class="cart-sidebar shadow-sm">
            <div class="p-3 bg-dark text-white d-flex justify-content-between align-items-center rounded-0">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cart3"></i> Current Sale</h5>
                <button class="btn btn-sm btn-outline-light border-0" onclick="clearCart()"><i class="bi bi-trash"></i> Empty</button>
            </div>
            
            <div class="cart-items" id="cartList">
                <div class="text-center text-muted mt-5">
                    <i class="bi bi-basket2 mb-2" style="font-size: 3rem;"></i>
                    <p>No items added yet.<br>Scan barcode or tap a product.</p>
                </div>
            </div>
            
            <div class="cart-totals">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Subtotal</span>
                    <span class="fw-bold" id="cartSubtotal"><?= $currency ?>0.00</span>
                </div>
                <!-- Interactive Discount Row -->
                <div class="d-flex justify-content-between mb-2">
                    <a href="#" class="text-primary text-decoration-none fw-bold" onclick="promptDiscount()"><i class="bi bi-tag"></i> Discount</a>
                    <span class="text-danger fw-bold" id="cartDiscount">-<?= $currency ?>0.00</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <span class="fs-4 fw-bold">Total</span>
                    <span class="fs-3 fw-bold text-success" id="cartTotal"><?= $currency ?>0.00</span>
                </div>
                <button type="button" class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-3" id="checkoutBtn" disabled onclick="openPaymentModal()">
                    <i class="bi bi-cash-coin me-1"></i> Pay Now (Enter)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold">Complete Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center pb-2">
        <h3 class="mb-4 display-6 fw-bold text-success" id="modalPayTotal"><?= $currency ?>0.00</h3>
        <div class="mb-3">
            <label class="form-label small fw-bold">Link to Customer (Required for Credit)</label>
            <select class="form-select" id="posCustomerId">
                <option value="">Walk-in Customer</option>
            </select>
        </div>

        <div class="row gx-2 mb-3 mt-3">
            <div class="col-6">
                <input type="text" class="form-control" id="customerName" placeholder="Name (Auto-filled)">
            </div>
            <div class="col-6">
                <input type="text" class="form-control" id="customerPhone" placeholder="Phone (Auto-filled)">
            </div>
        </div>

        <div class="btn-group w-100 mb-4" role="group">
          <input type="radio" class="btn-check" name="paymentType" id="payCash" value="Cash" checked autocomplete="off">
          <label class="btn btn-outline-dark fw-bold" for="payCash"><i class="bi bi-cash me-1"></i>Cash</label>

          <input type="radio" class="btn-check" name="paymentType" id="payCard" value="Card" autocomplete="off">
          <label class="btn btn-outline-dark fw-bold" for="payCard"><i class="bi bi-credit-card me-1"></i>Card</label>

          <input type="radio" class="btn-check" name="paymentType" id="payCredit" value="Credit" autocomplete="off">
          <label class="btn btn-outline-dark fw-bold" for="payCredit"><i class="bi bi-journal-check me-1"></i>Credit</label>
        </div>

        <div id="cashTenderedGroup">
            <div class="input-group input-group-lg mb-3">
                <span class="input-group-text fw-bold">Tendered <?= $currency ?></span>
                <input type="number" step="0.01" class="form-control text-center fs-4 fw-bold" id="amountTendered" oninput="updateChangeDue()">
            </div>
            
            <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                <span class="fs-5 text-muted">Change Due:</span>
                <span class="fs-3 fw-bold" id="changeDueDisplay"><?= $currency ?>0.00</span>
            </div>
        </div>

      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-secondary w-100 mb-2" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" id="confirmPaymentBtn">
            Confirm & Print Receipt
        </button>
      </div>
    </div>
  </div>
</div>

<script src="/pos/assets/js/pos.js"></script>

<?php require_once 'includes/footer.php'; ?>
