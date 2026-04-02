// C:\xampp\htdocs\pos\assets\js\pos.js

let cart = [];
let globalDiscount = 0;
let taxAmount = 0; // Configurable tax later
let amountTenderedInput;

document.addEventListener('DOMContentLoaded', () => {
    amountTenderedInput = document.getElementById('amountTendered');
    // Focus search on load
    document.getElementById('posBarcode').focus();
    loadPosCustomers();

    // Prevent form submission on enter, instead search
    document.getElementById('barcodeSearchForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('posBarcode');
        const barcode = input.value.trim();
        if(!barcode) return;
        
        try {
            const res = await fetch(`/pos/api/products/lookup.php?barcode=${barcode}`);
            if (res.ok) {
                const data = await res.json();
                if (data.success && data.data) {
                    addProdToCart({
                        id: data.data.id,
                        name: data.data.name,
                        price: data.data.price,
                        stock: data.data.stock_quantity
                    });
                    input.value = ''; // clear
                } else {
                    showAlert('Product not found or out of stock!', 'warning', 'alertContainer');
                }
            } else {
                showAlert('Product not found!', 'danger', 'alertContainer');
            }
        } catch(err) {
            console.error(err);
        }
    });

    // Handle Payment Type Toggles
    const payRadios = document.querySelectorAll('input[name="paymentType"]');
    payRadios.forEach(r => {
        r.addEventListener('change', (e) => {
            if(e.target.value === 'Cash') {
                document.getElementById('cashTenderedGroup').style.display = 'block';
            } else { // Card or Credit
                document.getElementById('cashTenderedGroup').style.display = 'none';
                amountTenderedInput.value = calculateGrandTotal().toFixed(2); // Auto fill exact
            }
        });
    });

    // Calculate Change on input
    if (amountTenderedInput) {
        amountTenderedInput.addEventListener('input', updateChangeDue);
    }

    // Auto-fill customer details in POS
    document.getElementById('posCustomerId').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if(this.value) {
            document.getElementById('customerName').value = opt.dataset.name;
            document.getElementById('customerPhone').value = opt.dataset.phone;
        } else {
            document.getElementById('customerName').value = '';
            document.getElementById('customerPhone').value = '';
        }
    });

    // POS Global Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        // Trigger Checkout on 'Enter' if cart has items and no modal is open
        if (e.key === 'Enter' && cart.length > 0 && !document.getElementById('paymentModal').classList.contains('show')) {
            // Check if focus is NOT in the search box (otherwise search form handles it)
            if (document.activeElement.id !== 'posBarcode') {
                e.preventDefault();
                openPaymentModal();
            }
        }
    });
});

async function loadPosCustomers() {
    try {
        const res = await fetch('/pos/api/customers/list.php');
        const data = await res.json();
        if(data.success) {
            const select = document.getElementById('posCustomerId');
            data.data.forEach(c => {
                select.innerHTML += `<option value="${c.id}" data-name="${c.name}" data-phone="${c.phone}">${c.name} (${c.phone})</option>`;
            });
        }
    } catch(err) { console.error(err); }
}

function addProdToCart(product) {
    // Check if already in cart
    const existing = cart.find(i => i.id == product.id);
    if(existing) {
        if(existing.quantity < product.stock) {
            existing.quantity++;
        } else {
            showAlert(`Cannot add more. Only ${product.stock} in stock!`, 'danger', 'alertContainer');
        }
    } else {
        if(product.stock > 0) {
            cart.push({...product, quantity: 1, discount: 0});
        } else {
            showAlert('Item is out of stock!', 'danger', 'alertContainer');
            return;
        }
    }
    renderCart();
}

function updateQty(id, newQty) {
    const item = cart.find(i => i.id == id);
    if(!item) return;
    
    if(newQty > item.stock) {
        showAlert(`Only ${item.stock} available.`, 'warning', 'alertContainer');
        newQty = item.stock;
    }
    
    if(newQty <= 0) {
        cart = cart.filter(i => i.id != id);
    } else {
        item.quantity = newQty;
    }
    renderCart();
}

function removeItem(id) {
    cart = cart.filter(i => i.id != id);
    renderCart();
}

function promptDiscount() {
    let val = prompt("Enter total discount amount ($):", globalDiscount);
    if(val !== null && !isNaN(val)) {
        globalDiscount = parseFloat(val);
        if(globalDiscount < 0) globalDiscount = 0;
        renderCart();
    }
}

function clearCart() {
    if(confirm("Are you sure you want to empty the cart?")) {
        cart = [];
        globalDiscount = 0;
        renderCart();
    }
}

function calculateSubtotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity) - item.discount, 0);
}

function calculateGrandTotal() {
    const sub = calculateSubtotal();
    let total = sub - globalDiscount + taxAmount;
    return total > 0 ? total : 0;
}

function renderCart() {
    const list = document.getElementById('cartList');
    const cur = window.POS_SETTINGS.currency || '$';
    if(cart.length === 0) {
        list.innerHTML = `
            <div class="text-center text-muted mt-5">
                <i class="bi bi-cart-x mb-2" style="font-size: 3rem;"></i>
                <p>No items added yet.</p>
            </div>
        `;
        document.getElementById('checkoutBtn').disabled = true;
        document.getElementById('cartSubtotal').textContent = cur + '0.00';
        document.getElementById('cartDiscount').textContent = '-' + cur + '0.00';
        document.getElementById('cartTotal').textContent = cur + '0.00';
        return;
    }

    list.innerHTML = '';
    cart.forEach(item => {
        const itemTotal = (item.price * item.quantity) - item.discount;
        list.innerHTML += `
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="flex-grow-1 me-2">
                    <h6 class="mb-0 fw-bold">${item.name}</h6>
                    <small class="text-muted">${cur}${parseFloat(item.price).toFixed(2)} x ${item.quantity}</small>
                </div>
                <div class="d-flex flex-column align-items-end">
                    <span class="fw-bold">${cur}${itemTotal.toFixed(2)}</span>
                    <div class="btn-group btn-group-sm mt-1">
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${item.id}, ${item.quantity - 1})">-</button>
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${item.id}, ${item.quantity + 1})">+</button>
                        <button class="btn btn-danger px-2" onclick="removeItem(${item.id})"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    });

    const sub = calculateSubtotal();
    const grand = calculateGrandTotal();

    document.getElementById('cartSubtotal').textContent = `${cur}${sub.toFixed(2)}`;
    document.getElementById('cartDiscount').textContent = `-${cur}${globalDiscount.toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `${cur}${grand.toFixed(2)}`;
    document.getElementById('checkoutBtn').disabled = false;
}

function openPaymentModal() {
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const total = calculateGrandTotal();
    const cur = window.POS_SETTINGS.currency || '$';
    document.getElementById('modalPayTotal').textContent = `${cur}${total.toFixed(2)}`;
    amountTenderedInput.value = '';
    document.getElementById('changeDueDisplay').textContent = cur + '0.00';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    
    // Reset to cash
    document.getElementById('payCash').checked = true;
    document.getElementById('cashTenderedGroup').style.display = 'block';

    modal.show();
    setTimeout(() => amountTenderedInput.focus(), 500);
}

function updateChangeDue() {
    const tenderedInput = document.getElementById('amountTendered');
    const display = document.getElementById('changeDueDisplay');
    if(!tenderedInput || !display) return;

    const tendered = parseFloat(tenderedInput.value) || 0;
    const total = calculateGrandTotal();
    const cur = window.POS_SETTINGS.currency || '$';
    
    let change = tendered - total;
    
    if (change < 0) {
        // Show remaining balance as a warning
        display.innerHTML = `<span class="text-danger">-${cur}${Math.abs(change).toFixed(2)}</span>`;
    } else {
        // Show positive change in green
        display.innerHTML = `<span class="text-success">${cur}${change.toFixed(2)}</span>`;
    }
}

document.getElementById('confirmPaymentBtn').addEventListener('click', async () => {
    const total = calculateGrandTotal();
    const payType = document.querySelector('input[name="paymentType"]:checked').value;
    let tendered = parseFloat(amountTenderedInput.value) || 0;

    if(payType === 'Cash' && tendered < total - 0.01) {
        alert("Amount tendered indicates insufficient funds!");
        return;
    }

    const customerId = document.getElementById('posCustomerId').value;
    if(payType === 'Credit' && !customerId) {
        alert("A customer must be selected to process a Credit sale!");
        return;
    }

    if(payType !== 'Cash') {
        tendered = total; // Cards are exact exact
    }

    const payload = {
        customer_id: customerId || null,
        customer_name: document.getElementById('customerName').value,
        customer_phone: document.getElementById('customerPhone').value,
        items: cart,
        subtotal: calculateSubtotal(),
        discount_amount: globalDiscount,
        tax_amount: taxAmount,
        grand_total: total,
        payment_type: payType,
        amount_tendered: tendered,
        change_due: payType === 'Cash' ? (tendered - total) : 0
    };

    const btn = document.getElementById('confirmPaymentBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

    try {
        const res = await apiCall('api/sales/create.php', 'POST', payload);
        
        // Hide Modal
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        
        // Open Receipt in new tab
        window.open(`/pos/receipt.php?id=${res.sale_id}`, '_blank');
        
        showAlert(res.message, 'success', 'alertContainer');
        cart = [];
        globalDiscount = 0;
        renderCart();
    } catch(err) {
        alert("Checkout Failed: " + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Confirm & Print Receipt';
    }
});

/**
 * Filter product grid by category
 */
function filterByCategory(catId, btn) {
    // UI Update
    const buttons = document.querySelectorAll('#categoryFilterTabs button');
    buttons.forEach(b => {
        b.classList.remove('btn-dark', 'active');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-dark', 'active');

    // Filter Logic
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        if (catId === 'all' || item.dataset.category == catId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });

    // Clear text search when switching categories
    document.getElementById('posBarcode').value = '';
}

/**
 * Live Smart Search
 */
document.getElementById('posBarcode').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    if (!query) return;

    // Only filter the grid if it's not a barcode-length digit string (optional heuristic)
    // But for "Smart Search", we filter as they type.
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        if (item.dataset.name.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
