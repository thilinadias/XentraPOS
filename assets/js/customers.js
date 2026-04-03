// C:\xampp\htdocs\pos\assets\js\customers.js

let customerModal;
let ledgerModal;
const cur = window.POS_SETTINGS.currency || '$';

document.addEventListener('DOMContentLoaded', () => {
    customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
    ledgerModal = new bootstrap.Modal(document.getElementById('ledgerModal'));
    loadCustomers();
});

async function loadCustomers() {
    try {
        const res = await fetch('/pos/api/customers/list.php');
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        const tbody = document.getElementById('customerTableBody');
        const paySelect = document.getElementById('payCustomerId');
        
        tbody.innerHTML = '';
        paySelect.innerHTML = '<option value="">Choose...</option>';
        
        let totalDebt = 0;

        data.data.forEach(c => {
            totalDebt += parseFloat(c.balance);
            const balClass = parseFloat(c.balance) > 0 ? 'text-danger fw-bold' : 'text-success';
            
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${c.name}</div>
                        <div class="small text-muted">${c.email || 'No email'}</div>
                    </td>
                    <td>${c.phone}</td>
                    <td class="text-end ${balClass}">${cur}${parseFloat(c.balance).toLocaleString()}</td>
                    <td class="text-center">
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-sm btn-outline-info px-2" onclick="openLedger(${c.id}, '${c.name.replace(/'/g, "\\'")}')" title="View Full Ledger"><i class="bi bi-journal-text me-1"></i> Ledger</button>
                            <button class="btn btn-sm btn-outline-dark px-2" onclick='openEditCustomerModal(${JSON.stringify(c).replace(/'/g, "&apos;")})' title="Edit Profile"><i class="bi bi-pencil"></i></button>
                        </div>
                    </td>
                </tr>
            `;

            paySelect.innerHTML += `<option value="${c.id}">${c.name} (${c.phone}) - Bal: ${cur}${c.balance}</option>`;
        });

        document.getElementById('totalDebt').textContent = `${cur}${totalDebt.toLocaleString(undefined, {minimumFractionDigits: 2})}`;

    } catch (err) {
        showAlert('Load Error: ' + err.message, 'danger', 'customerAlert');
    }
}

function openAddModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('custId').value = '';
    document.getElementById('customerModalTitle').textContent = 'New Customer Profile';
    document.getElementById('saveCustBtn').textContent = 'Create Profile';
    customerModal.show();
}

function openEditCustomerModal(c) {
    document.getElementById('customerForm').reset();
    document.getElementById('custId').value = c.id;
    document.getElementById('custName').value = c.name;
    document.getElementById('custPhone').value = c.phone;
    document.getElementById('custEmail').value = c.email || '';
    document.getElementById('custAddress').value = c.address || '';
    
    document.getElementById('customerModalTitle').textContent = 'Edit Customer Profile';
    document.getElementById('saveCustBtn').textContent = 'Update Profile';
    customerModal.show();
}

document.getElementById('customerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const saveBtn = document.getElementById('saveCustBtn');
    const custId = document.getElementById('custId').value;
    saveBtn.disabled = true;

    const formData = new URLSearchParams();
    if (custId) formData.append('id', custId);
    formData.append('name', document.getElementById('custName').value);
    formData.append('phone', document.getElementById('custPhone').value);
    formData.append('email', document.getElementById('custEmail').value);
    formData.append('address', document.getElementById('custAddress').value);

    const endpoint = custId ? '/pos/api/customers/update.php' : '/pos/api/customers/create.php';

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        showAlert(data.message, 'success', 'customerAlert');
        customerModal.hide();
        loadCustomers();
    } catch (err) {
        alert(err.message);
    } finally {
        saveBtn.disabled = false;
    }
});

document.getElementById('paymentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.submitter;
    btn.disabled = true;

    const formData = new URLSearchParams();
    formData.append('customer_id', document.getElementById('payCustomerId').value);
    formData.append('amount', document.getElementById('payAmount').value);
    formData.append('payment_method', document.getElementById('payMethod').value);

    try {
        const res = await fetch('/pos/api/customers/payment_add.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        showAlert(data.message, 'success', 'customerAlert');
        e.target.reset();
        loadCustomers();
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled = false;
    }
});

// Ledger History Logic
async function openLedger(customerId, customerName) {
    document.getElementById('ledgerCustomerHeader').innerHTML = `<h6 class='text-muted'>History for: <span class='text-dark fw-bold'>${customerName}</span></h6>`;
    const tbody = document.getElementById('ledgerTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Loading statement...</td></tr>';
    
    ledgerModal.show();

    try {
        const res = await fetch(`/pos/api/customers/ledger.php?id=${customerId}`);
        const data = await res.json();
        if(!data.success) throw new Error(data.message);

        tbody.innerHTML = '';
        if(data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No transactions found for this customer.</td></tr>';
            return;
        }

        data.data.forEach(item => {
            const date = new Date(item.created_at).toLocaleString();
            const badge = (item.type === 'Sale') ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
            const prefix = (item.type === 'Sale') ? '+' : '-';
            
            tbody.innerHTML += `
                <tr>
                    <td><small class="text-muted">${date}</small></td>
                    <td><span class="badge ${badge}">${item.type}</span></td>
                    <td>${item.type === 'Sale' ? 'Inv: ' + (item.invoice_number || 'N/A') : 'Payment: ' + item.payment_method}</td>
                    <td class="text-end fw-bold">${prefix}${cur}${parseFloat(item.amount).toLocaleString()}</td>
                </tr>
            `;
        });
    } catch(err) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
    }
}

// Moved to DOMContentLoaded at the top
