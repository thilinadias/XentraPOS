// C:\xampp\htdocs\pos\assets\js\suppliers.js

let supModal;
let stockModal;
let historyModal;

document.addEventListener('DOMContentLoaded', () => {
    supModal = new bootstrap.Modal(document.getElementById('supplierModal'));
    stockModal = new bootstrap.Modal(document.getElementById('stockInModal'));
    historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    loadSuppliers();
});

async function loadSuppliers() {
    try {
        const res = await fetch('/pos/api/suppliers/list.php');
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        const tbody = document.getElementById('supplierTableBody');
        const stockSupSelect = document.getElementById('stockInSupId');
        
        tbody.innerHTML = '';
        stockSupSelect.innerHTML = '<option value="">Choose supplier...</option>';
        
        data.data.forEach(s => {
            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold">${s.name}</td>
                    <td>${s.contact_person || 'N/A'}</td>
                    <td>${s.phone || 'N/A'}</td>
                    <td>${s.email || 'N/A'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary px-2" onclick="openHistory(${s.id}, '${s.name.replace(/'/g, "\\'")}')" title="View Purchase History"><i class="bi bi-box-seam me-1"></i> History</button>
                    </td>
                </tr>
            `;

            stockSupSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });

    } catch (err) {
        showAlert('Load Error: ' + err.message, 'danger', 'supplierAlert');
    }
}

async function loadProductsForStockIn() {
    try {
        const res = await fetch('/pos/api/products/list.php');
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        const prodSelect = document.getElementById('stockInProdId');
        prodSelect.innerHTML = '<option value="">Choose product...</option>';
        
        data.data.forEach(p => {
            prodSelect.innerHTML += `<option value="${p.id}" data-cost="${p.cost_price}">${p.name} (Cur: ${p.stock_quantity})</option>`;
        });
        
    } catch (err) { console.error(err); }
}

function openAddSupplierModal() {
    document.getElementById('supplierForm').reset();
    supModal.show();
}

function openStockInModal() {
    document.getElementById('stockInForm').reset();
    loadProductsForStockIn();
    stockModal.show();
}

// Auto-fill cost price when product selected
document.getElementById('stockInProdId').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if(opt.dataset.cost) {
        document.getElementById('stockInCost').value = opt.dataset.cost;
    }
});

document.getElementById('supplierForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const saveBtn = document.getElementById('saveSupBtn');
    saveBtn.disabled = true;

    const formData = new URLSearchParams();
    formData.append('name', document.getElementById('supName').value);
    formData.append('contact_person', document.getElementById('supContact').value);
    formData.append('phone', document.getElementById('supPhone').value);

    try {
        const res = await fetch('/pos/api/suppliers/create.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        showAlert(data.message, 'success', 'supplierAlert');
        supModal.hide();
        loadSuppliers();
    } catch (err) {
        alert(err.message);
    } finally {
        saveBtn.disabled = false;
    }
});

document.getElementById('stockInForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveStockBtn');
    btn.disabled = true;

    const formData = new URLSearchParams();
    formData.append('product_id', document.getElementById('stockInProdId').value);
    formData.append('supplier_id', document.getElementById('stockInSupId').value);
    formData.append('quantity', document.getElementById('stockInQty').value);
    formData.append('cost_price', document.getElementById('stockInCost').value);

    try {
        const res = await fetch('/pos/api/suppliers/purchase.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        showAlert(data.message, 'success', 'supplierAlert');
        stockModal.hide();
        loadSuppliers();
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled = false;
    }
});

// Purchase History Logic
async function openHistory(supplierId, supplierName) {
    document.getElementById('historySupplierHeader').innerHTML = `<h6 class='text-muted'>Purchase records for: <span class='text-dark fw-bold'>${supplierName}</span></h6>`;
    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading history...</td></tr>';
    
    historyModal.show();

    try {
        const res = await fetch(`/pos/api/suppliers/history.php?id=${supplierId}`);
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        tbody.innerHTML = '';
        if(data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No purchase records found for this supplier.</td></tr>';
            return;
        }

        const cur = window.POS_SETTINGS.currency || '$';
        data.data.forEach(item => {
            const date = new Date(item.created_at).toLocaleString();
            tbody.innerHTML += `
                <tr>
                    <td><small class="text-muted">${date}</small></td>
                    <td class="fw-bold">${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td class="text-end">${cur}${parseFloat(item.unit_cost).toLocaleString()}</td>
                    <td class="text-end fw-bold">${cur}${parseFloat(item.total_cost).toLocaleString()}</td>
                </tr>
            `;
        });
    } catch(err) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${err.message}</td></tr>`;
    }
}

// Moved to DOMContentLoaded at the top
