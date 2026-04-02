// C:\xampp\htdocs\pos\assets\js\products.js

document.addEventListener('DOMContentLoaded', () => {
    const productTableBody = document.getElementById('productTableBody');
    if (!productTableBody) return;

    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const productForm = document.getElementById('productForm');
    const imagePreview = document.getElementById('imagePreview');
    let currentProductId = null;

    async function loadProducts() {
        try {
            const res = await apiCall('api/products/list.php');
            productTableBody.innerHTML = '';
            
            if(res.data.length === 0) {
                productTableBody.innerHTML = '<tr><td colspan="8" class="text-center">No products found.</td></tr>';
                return;
            }

            res.data.forEach(prod => {
                let imgHtml = prod.image_path ? `<img src="/pos/${prod.image_path}" alt="Image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">` : `<span class="badge bg-light text-dark border">No Image</span>`;
                const cur = window.POS_SETTINGS.currency || '$';
                const threshold = window.POS_SETTINGS.low_stock || 10;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${prod.id}</td>
                    <td>${imgHtml}</td>
                    <td class="fw-bold">${prod.name}</td>
                    <td><span class="badge bg-info text-dark">${prod.category_name || 'Uncategorized'}</span></td>
                    <td>${prod.barcode || '-'}</td>
                    <td>${cur}${parseFloat(prod.cost_price || 0).toFixed(2)}</td>
                    <td>${cur}${parseFloat(prod.price).toFixed(2)}</td>
                    <td>
                        <span class="badge ${prod.stock_quantity > threshold ? 'bg-success' : (prod.stock_quantity > 0 ? 'bg-warning' : 'bg-danger')}">
                            ${prod.stock_quantity}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-prod='${JSON.stringify(prod).replace(/'/g, "&apos;")}'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${prod.id}"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                productTableBody.appendChild(tr);
            });
            attachListeners();
        } catch (e) {
            showAlert('Failed to load products: ' + e.message, 'danger', 'alertContainer');
        }
    }

    // Load categories into select
    async function populateCategorySelect() {
        const catSelect = document.getElementById('category_id');
        try {
            const res = await apiCall('api/categories/list.php');
            catSelect.innerHTML = '<option value="">-- No Category --</option>';
            res.data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                catSelect.appendChild(opt);
            });
        } catch (e) {
            console.error("Failed to load categories", e);
        }
    }

    function attachListeners() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const prod = JSON.parse(e.currentTarget.getAttribute('data-prod'));
                currentProductId = prod.id;
                document.getElementById('modalTitle').textContent = 'Edit Product';
                
                document.getElementById('name').value = prod.name;
                document.getElementById('barcode').value = prod.barcode || '';
                document.getElementById('category_id').value = prod.category_id || '';
                document.getElementById('cost_price').value = prod.cost_price || 0;
                document.getElementById('price').value = prod.price;
                document.getElementById('stock_quantity').value = prod.stock_quantity;
                document.getElementById('description').value = prod.description || '';
                document.getElementById('image').value = ''; // Reset file input

                if(prod.image_path) {
                    imagePreview.innerHTML = `<img src="/pos/${prod.image_path}" style="max-width: 100px; max-height: 100px; border-radius: 5px;" class="mt-2">`;
                } else {
                    imagePreview.innerHTML = '';
                }

                productModal.show();
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                if(!confirm('Delete this product?')) return;
                const id = e.currentTarget.getAttribute('data-id');
                try {
                    const res = await apiCall('api/products/delete.php', 'POST', { id });
                    showAlert(res.message, 'success', 'alertContainer');
                    loadProducts();
                } catch(err) {
                    alert(err.message);
                }
            });
        });
    }

    document.getElementById('addBtn').addEventListener('click', () => {
        currentProductId = null;
        productForm.reset();
        imagePreview.innerHTML = '';
        document.getElementById('modalTitle').textContent = 'Add Product';
        productModal.show();
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('saveProductBtn');
        submitBtn.disabled = true;
        
        // Since we have an image, we MUST use FormData, not JSON.
        const formData = new FormData(productForm);
        if (currentProductId) {
            formData.append('id', currentProductId);
        }

        try {
            const endpoint = currentProductId ? 'api/products/update.php' : 'api/products/create.php';
            
            const response = await fetch('/pos/' + endpoint, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData // Browser sets multipart boundary automatically
            });
            
            let resText = await response.text();
            let res;
            try {
                res = JSON.parse(resText);
            } catch(jsonErr) {
                console.error("Non-JSON", resText);
                throw new Error("Server returned invalid response");
            }

            if (!response.ok) throw new Error(res.message || 'HTTP Error');

            productModal.hide();
            showAlert(res.message, 'success', 'alertContainer');
            loadProducts();
            
        } catch(err) {
            alert('Failed: ' + err.message);
        } finally {
            submitBtn.disabled = false;
        }
    });

    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('processImportBtn');
            const alertBox = document.getElementById('importAlert');
            btn.disabled = true;
            btn.innerHTML = 'Processing...';
            alertBox.innerHTML = '';

            const formData = new FormData(importForm);

            try {
                const res = await fetch('/pos/api/products/import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if(!data.success) throw new Error(data.message);

                alertBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                    location.reload();
                }, 2000);

            } catch (err) {
                alertBox.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Process Upload';
            }
        });
    }

    populateCategorySelect();
    loadProducts();
});
