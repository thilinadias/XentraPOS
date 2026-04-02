// C:\xampp\htdocs\pos\assets\js\users.js
document.addEventListener('DOMContentLoaded', () => {
    const userTableBody = document.getElementById('userTableBody');
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    const userForm = document.getElementById('userForm');
    let currentUserId = null;

    if (!userTableBody) return; // Only run on users.php

    // Load users
    async function loadUsers() {
        try {
            const data = await apiCall('api/users/list.php', 'GET');
            
            userTableBody.innerHTML = '';
            
            if(data.data.length === 0) {
                userTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
                return;
            }

            data.data.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td><span class="badge bg-secondary">${user.role.replace('_', ' ')}</span></td>
                    <td><span class="status-${user.status === 'active' ? 'active' : 'suspended'}">${user.status}</span></td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${user.id}" data-user='${JSON.stringify(user)}'><i class="bi bi-pencil"></i> Edit</button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${user.id}"><i class="bi bi-trash"></i> Delete</button>
                    </td>
                `;
                userTableBody.appendChild(tr);
            });

            attachActionListeners();
        } catch (error) {
            showAlert('Failed to load users: ' + error.message, 'danger', 'userAlertContainer');
        }
    }

    // Attach edit/delete handlers after render
    function attachActionListeners() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const user = JSON.parse(e.currentTarget.getAttribute('data-user'));
                currentUserId = user.id;
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('username').value = user.username;
                document.getElementById('password').value = ''; // Don't show password
                document.getElementById('password').required = false; // Not required on edit
                document.getElementById('passwordHelp').style.display = 'block';
                document.getElementById('role').value = user.role;
                document.getElementById('status').value = user.status;
                document.getElementById('statusContainer').style.display = 'block';
                document.getElementById('saveUserBtn').textContent = 'Update User';
                userModal.show();
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.currentTarget.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this user? This cannot be undone.')) {
                    try {
                        const res = await apiCall('api/users/delete.php', 'POST', { id: id });
                        alert(res.message);
                        loadUsers();
                    } catch (err) {
                        alert('Failed to delete: ' + err.message);
                    }
                }
            });
        });
    }

    // Open Add User Modal
    document.getElementById('addUserBtn').addEventListener('click', () => {
        currentUserId = null;
        userForm.reset();
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('password').required = true;
        document.getElementById('passwordHelp').style.display = 'none';
        document.getElementById('status').value = 'active';
        document.getElementById('statusContainer').style.display = 'none'; // Default to active on creation
        document.getElementById('saveUserBtn').textContent = 'Create User';
        userModal.show();
    });

    // Save User (Create or Update)
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            username: document.getElementById('username').value,
            role: document.getElementById('role').value,
            status: document.getElementById('status').value
        };

        const pwd = document.getElementById('password').value;
        if (pwd) {
            payload.password = pwd;
        }

        try {
            let res;
            if (currentUserId) {
                // Update
                payload.id = currentUserId;
                res = await apiCall('api/users/update.php', 'POST', payload);
            } else {
                // Create
                res = await apiCall('api/users/create.php', 'POST', payload);
            }
            
            userModal.hide();
            showAlert(res.message, 'success', 'userAlertContainer');
            loadUsers();
        } catch (err) {
            alert('Failed to save user: ' + err.message);
        }
    });

    // Initial load
    loadUsers();
});
