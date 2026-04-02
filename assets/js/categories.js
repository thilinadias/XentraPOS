// C:\xampp\htdocs\pos\assets\js\categories.js

document.addEventListener('DOMContentLoaded', () => {
    const categoryTableBody = document.getElementById('categoryTableBody');
    if (!categoryTableBody) return;

    const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const categoryForm = document.getElementById('categoryForm');
    let currentCategoryId = null;

    async function loadCategories() {
        try {
            const res = await apiCall('api/categories/list.php');
            categoryTableBody.innerHTML = '';
            
            if(res.data.length === 0) {
                categoryTableBody.innerHTML = '<tr><td colspan="4" class="text-center">No categories found.</td></tr>';
                return;
            }

            res.data.forEach(cat => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${cat.id}</td>
                    <td>${cat.name}</td>
                    <td>${cat.description || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-cat='${JSON.stringify(cat).replace(/'/g, "&apos;")}'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${cat.id}"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                categoryTableBody.appendChild(tr);
            });
            attachListeners();
        } catch (e) {
            showAlert('Failed to load categories: ' + e.message, 'danger', 'alertContainer');
        }
    }

    function attachListeners() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const cat = JSON.parse(e.currentTarget.getAttribute('data-cat'));
                currentCategoryId = cat.id;
                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('cat_name').value = cat.name;
                document.getElementById('cat_desc').value = cat.description || '';
                categoryModal.show();
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                if(!confirm('Delete this category?')) return;
                const id = e.currentTarget.getAttribute('data-id');
                try {
                    const res = await apiCall('api/categories/delete.php', 'POST', { id });
                    showAlert(res.message, 'success', 'alertContainer');
                    loadCategories();
                } catch(err) {
                    alert(err.message);
                }
            });
        });
    }

    document.getElementById('addBtn').addEventListener('click', () => {
        currentCategoryId = null;
        categoryForm.reset();
        document.getElementById('modalTitle').textContent = 'Add Category';
        categoryModal.show();
    });

    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            name: document.getElementById('cat_name').value,
            description: document.getElementById('cat_desc').value
        };

        try {
            let res;
            if(currentCategoryId) {
                payload.id = currentCategoryId;
                res = await apiCall('api/categories/update.php', 'POST', payload);
            } else {
                res = await apiCall('api/categories/create.php', 'POST', payload);
            }
            categoryModal.hide();
            showAlert(res.message, 'success', 'alertContainer');
            loadCategories();
        } catch(err) {
            alert('Failed: ' + err.message);
        }
    });

    loadCategories();
});
