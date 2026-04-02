// C:\xampp\htdocs\pos\assets\js\mobile_scanner.js
document.addEventListener('DOMContentLoaded', () => {
    const startScanBtn = document.getElementById('startScanBtn');
    const stopScanBtn = document.getElementById('stopScanBtn');
    const barcodeInput = document.getElementById('barcode');
    const form = document.getElementById('mobileProductForm');
    
    let html5QrcodeScanner = null;

    // Load cats
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

    if (startScanBtn) {
        startScanBtn.addEventListener('click', () => {
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5Qrcode("reader");
            }

            const config = { fps: 10, qrbox: { width: 250, height: 150 } };

            html5QrcodeScanner.start({ facingMode: "environment" }, config, 
                // Success callback
                (decodedText, decodedResult) => {
                    // Beep or vibrate (if supported)
                    if (navigator.vibrate) navigator.vibrate(200);
                    
                    barcodeInput.value = decodedText;
                    
                    // Stop scanning
                    stopScanner();
                    
                    showAlert(`Barcode ${decodedText} scanned successfully!`, 'success', 'alertContainer');

                    // If we want to auto lookup the product we can do so here:
                    checkBarcodeExists(decodedText);
                },
                // Error callback (ignored to not spam console)
                (errorMessage) => { }
            ).catch(err => {
                showAlert("Error starting camera: " + err, "danger", "alertContainer");
            });

            startScanBtn.classList.add('d-none');
            stopScanBtn.classList.remove('d-none');
        });
    }

    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', stopScanner);
    }

    function stopScanner() {
        if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
            html5QrcodeScanner.stop().then(() => {
                startScanBtn.classList.remove('d-none');
                stopScanBtn.classList.add('d-none');
            }).catch(err => {
                console.error("Failed to stop scanner", err);
            });
        }
    }

    // Optional: auto-fill form if barcode already exists
    async function checkBarcodeExists(barcode) {
        try {
            const res = await fetch(`/pos/api/products/lookup.php?barcode=${barcode}`);
            if (res.ok) {
                const data = await res.json();
                if (data.success && data.data) {
                    showAlert('Product already exists! Editing it instead.', 'info', 'alertContainer');
                    document.getElementById('name').value = data.data.name;
                    document.getElementById('price').value = data.data.price;
                    document.getElementById('stock_quantity').value = data.data.stock_quantity;
                    document.getElementById('category_id').value = data.data.category_id || '';
                }
            }
        } catch (err) { }
    }

    // Submit form via multipart
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('saveProductBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

            const formData = new FormData(form);

            try {
                // Determine if updating or creating (simplified check, if name is populated from lookup it will duplicate if we just push.
                // We'll treat mobile app as create-only for now unless further refined, but the API handles duplicates via constraint.
                
                const response = await fetch('/pos/api/products/create.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                
                const res = await response.json();
                
                if (!response.ok) throw new Error(res.message);

                showAlert(res.message, 'success', 'alertContainer');
                form.reset();
                window.scrollTo(0,0);
            } catch(e) {
                showAlert(e.message, 'danger', 'alertContainer');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i> Save Product';
            }
        });
    }

    populateCategorySelect();
});
