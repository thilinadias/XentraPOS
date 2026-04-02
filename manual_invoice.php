<?php
// C:\xampp\htdocs\pos\manual_invoice.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent']);

// Fetch all active products for the dropdown
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id, name, price, stock_quantity FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-3">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-file-earmark-plus text-primary"></i> Create Manual Invoice</h2>
        <p class="text-muted">Generate a custom invoice. You can mix physical stock items with custom service lines.</p>
    </div>
</div>

<div id="alertContainer"></div>

<div class="row">
    <!-- Left: Invoice Builder -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Customer Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Link to Customer Profile (Optional)</label>
                    <select class="form-select" id="invCustomerId" onchange="autoFillCustomer()">
                        <option value="">-- Select from CRM --</option>
                    </select>
                    <small class="text-muted">Linking to a profile will track the debt in the Customer Ledger if unpaid.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Customer Name</label>
                        <input type="text" class="form-control" id="invCustomerName" placeholder="e.g. Acme Corp">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone / Contact</label>
                        <input type="text" class="form-control" id="invCustomerPhone" placeholder="e.g. 555-0199">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Line Items</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addCustomRow()">+ Add Custom Item</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="invoiceTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Item Description</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 15%;">Price</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="invoiceLines">
                        <!-- Rows go here -->
                        <tr id="emptyStateRow"><td colspan="5" class="text-center text-muted py-4">Add a product or custom item below to start.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white p-3 border-top-0">
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-muted">Quick Add Stock Product</label>
                        <div class="input-group">
                            <select class="form-select" id="stockProductSelector">
                                <option value="">-- Select a physical product --</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-stock="<?= $p['stock_quantity'] ?>">
                                        <?= htmlspecialchars($p['name']) ?> (<?= $currency ?><?= number_format($p['price'], 2) ?>) - Stock: <?= $p['stock_quantity'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-secondary" type="button" onclick="addStockRow()">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Summary & Save -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-body p-4 bg-light rounded">
                <h5 class="fw-bold mb-4">Invoice Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-bold" id="invSubtotal"><?= $currency ?>0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <span class="text-muted">Total Discount</span>
                    <input type="number" step="0.01" class="form-control form-control-sm w-50 text-end" id="invDiscount" value="0" oninput="calculateTotals()">
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4 mt-3">
                    <span class="fs-5 fw-bold text-dark">Total</span>
                    <span class="fs-4 fw-bold text-success" id="invTotal"><?= $currency ?>0.00</span>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold small">Payment Status</label>
                    <select class="form-select" id="invPaymentType">
                        <option value="Credit">Credit / Unpaid</option>
                        <option value="Cash">Paid via Cash</option>
                        <option value="Card">Paid via Card</option>
                    </select>
                    <small class="text-muted d-block mt-1">"Credit" creates standard invoice. Paid options complete the register logic.</small>
                </div>

                <button class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" id="generateBtn" onclick="generateInvoice()">
                    <i class="bi bi-file-earmark-check me-1"></i> Generate Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cartItems = [];
let rowIdCounter = 0;

async function loadInvCustomers() {
    try {
        const res = await fetch('/pos/api/customers/list.php');
        const data = await res.json();
        if(data.success) {
            const select = document.getElementById('invCustomerId');
            data.data.forEach(c => {
                select.innerHTML += `<option value="${c.id}" data-name="${c.name}" data-phone="${c.phone}">${c.name} (${c.phone})</option>`;
            });
        }
    } catch(err) { console.error(err); }
}

function autoFillCustomer() {
    const select = document.getElementById('invCustomerId');
    const opt = select.options[select.selectedIndex];
    if(select.value) {
        document.getElementById('invCustomerName').value = opt.dataset.name;
        document.getElementById('invCustomerPhone').value = opt.dataset.phone;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadInvCustomers();
});

function updateEmptyState() {
    const emptyRow = document.getElementById('emptyStateRow');
    if (cartItems.length > 0) {
        if(emptyRow) emptyRow.style.display = 'none';
    } else {
        if(emptyRow) emptyRow.style.display = 'table-row';
    }
}

function addStockRow() {
    const selector = document.getElementById('stockProductSelector');
    if(!selector.value) return;
    
    const id = parseInt(selector.value);
    const selectedOpt = selector.options[selector.selectedIndex];
    const name = selectedOpt.getAttribute('data-name');
    const price = parseFloat(selectedOpt.getAttribute('data-price'));
    const stock = parseInt(selectedOpt.getAttribute('data-stock'));

    if(stock <= 0) {
        // Technically can warn, but let the backend reject or allow admin override
    }

    // Check if already in cart
    const existing = cartItems.find(i => i.is_custom === false && i.id === id);
    if(existing) {
        existing.quantity++;
    } else {
        cartItems.push({
            rowId: ++rowIdCounter,
            is_custom: false,
            id: id,
            name: name,
            price: price,
            quantity: 1,
            discount: 0
        });
    }
    selector.value = '';
    renderTable();
}

function addCustomRow() {
    cartItems.push({
        rowId: ++rowIdCounter,
        is_custom: true,
        custom_name: 'Custom Service / Item',
        price: 0.00,
        quantity: 1,
        discount: 0
    });
    renderTable();
}

function removeRow(rowId) {
    cartItems = cartItems.filter(i => i.rowId !== rowId);
    renderTable();
}

function updateRowVal(rowId, field, val) {
    const item = cartItems.find(i => i.rowId === rowId);
    if(item) {
        if(field === 'quantity' || field === 'price') val = parseFloat(val) || 0;
        item[field] = val;
        calculateTotals();
    }
}

function calculateTotals() {
    let subtotal = 0;
    const cur = window.POS_SETTINGS.currency || '$';
    cartItems.forEach(item => {
        subtotal += (item.price * item.quantity);
    });
    
    let discount = parseFloat(document.getElementById('invDiscount').value) || 0;
    let grand = subtotal - discount;
    if(grand < 0) grand = 0;

    document.getElementById('invSubtotal').textContent = cur + subtotal.toFixed(2);
    document.getElementById('invTotal').textContent = cur + grand.toFixed(2);
}

function renderTable() {
    updateEmptyState();
    const tbody = document.getElementById('invoiceLines');
    const cur = window.POS_SETTINGS.currency || '$';
    
    // Remote all rows except empty state
    Array.from(tbody.children).forEach(child => {
        if(child.id !== 'emptyStateRow') child.remove();
    });

    cartItems.forEach(item => {
        const tr = document.createElement('tr');
        
        // Description Column
        let descHtml = '';
        if(item.is_custom) {
            descHtml = `<input type="text" class="form-control form-control-sm" value="${item.custom_name}" oninput="updateRowVal(${item.rowId}, 'custom_name', this.value)">`;
        } else {
            descHtml = `<span class="fw-bold">${item.name}</span>`;
        }

        // Type Column
        let typeHtml = item.is_custom ? `<span class="badge bg-secondary">Custom</span>` : `<span class="badge bg-primary">Stock</span>`;

        // Price Column
        let priceHtml = '';
        if(item.is_custom) {
            priceHtml = `<input type="number" step="0.01" class="form-control form-control-sm" value="${item.price}" oninput="updateRowVal(${item.rowId}, 'price', this.value)">`;
        } else {
            priceHtml = `${cur}${item.price.toFixed(2)}`;
        }

        tr.innerHTML = `
            <td>${descHtml}</td>
            <td>${typeHtml}</td>
            <td>${priceHtml}</td>
            <td><input type="number" min="1" class="form-control form-control-sm" value="${item.quantity}" oninput="updateRowVal(${item.rowId}, 'quantity', this.value)"></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" tabindex="-1" onclick="removeRow(${item.rowId})"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    calculateTotals();
}

async function generateInvoice() {
    if(cartItems.length === 0) {
        showAlert('Please add at least one item to the invoice.', 'warning', 'alertContainer');
        return;
    }

    const customerName = document.getElementById('invCustomerName').value.trim();
    if(!customerName && !confirm("You haven't entered a Customer Name. Create invoice without a customer?")) {
        return;
    }

    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

    // Build payload matching API expectations
    let subtotal = 0;
    const finalItems = cartItems.map(i => {
        subtotal += (i.price * i.quantity);
        return {
            is_custom: i.is_custom,
            id: i.id, // Ignored if is_custom=true
            custom_name: i.custom_name,
            price: i.price,
            quantity: i.quantity,
            discount: i.discount
        };
    });

    const discountAmount = parseFloat(document.getElementById('invDiscount').value) || 0;
    const grandTotal = subtotal - discountAmount;
    const paymentType = document.getElementById('invPaymentType').value;

    const payload = {
        customer_id: document.getElementById('invCustomerId').value || null,
        customer_name: customerName,
        customer_phone: document.getElementById('invCustomerPhone').value.trim(),
        items: finalItems,
        subtotal: subtotal,
        discount_amount: discountAmount,
        tax_amount: 0,
        grand_total: grandTotal,
        payment_type: paymentType,
        amount_tendered: paymentType === 'Credit' ? 0 : grandTotal,
        change_due: 0
    };

    try {
        const res = await fetch('/pos/api/sales/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        let resText = await res.text();
        let data;
        try { data = JSON.parse(resText); } catch(e) { throw new Error(resText); }

        if(!res.ok) throw new Error(data.message);

        showAlert('Invoice Generated Successfully!', 'success', 'alertContainer');
        
        // Open the A4 Invoice in a new tab
        window.open(`/pos/invoice.php?id=${data.sale_id}`, '_blank');
        
        // Reset form
        cartItems = [];
        rowIdCounter = 0;
        document.getElementById('invCustomerName').value = '';
        document.getElementById('invCustomerPhone').value = '';
        document.getElementById('invDiscount').value = '0';
        document.getElementById('invPaymentType').value = 'Credit';
        renderTable();

    } catch (err) {
        showAlert('Error: ' + err.message, 'danger', 'alertContainer');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-file-earmark-check me-1"></i> Generate Invoice';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
