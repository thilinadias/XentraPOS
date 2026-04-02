<?php
// C:\xampp\htdocs\pos\barcodes.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin', 'agent']);
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold mb-1">Barcode Label Designer</h2>
        <p class="text-muted">Select products and enter quantities to generate printable price tags.</p>
    </div>
    <button class="btn btn-dark fw-bold px-4 shadow-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Labels
    </button>
</div>

<div class="row g-4 no-print">
    <!-- Selection Table -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold">Select Products</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Product Name</th>
                                <th width="100px">Qty to Print</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="barcodeSourceBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold text-primary">Print Preview</h5>
            </div>
            <div class="card-body bg-light" id="labelsPreview">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-upc-scan" style="font-size: 3rem;"></i>
                    <p class="mt-2">Select a product and enter quantity to preview labels.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-Only Style -->
<style id="printStyle">
    .no-print { display: block; }
    #printableArea { display: none; }

    @media print {
        .no-print, nav, .navbar, footer, .sidebar { display: none !important; }
        body, .container { background: white !important; margin: 0; padding: 0; }
        .container { width: 100% !important; max-width: 100% !important; }
        
        #printableArea { 
            display: grid !important; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; 
            padding: 10px;
        }

        .label-card {
            border: 1px solid #eee;
            padding: 10px;
            text-align: center;
            height: auto;
            width: 100%;
            page-break-inside: avoid;
        }
        
        .label-name { font-weight: bold; font-size: 14px; display: block; margin-bottom: 2px; }
        .label-price { font-size: 16px; font-weight: 800; display: block; }
        .label-barcode { margin-top: 5px; width: 100%; height: 50px; }
    }
</style>

<!-- Actual Printable Area -->
<div id="printableArea"></div>

<!-- Barcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
let printQueue = {};

async function loadProducts() {
    try {
        const res = await fetch('/pos/api/products/list.php');
        const data = await res.json();
        const tbody = document.getElementById('barcodeSourceBody');
        tbody.innerHTML = '';
        
        data.data.forEach(p => {
            const cur = window.POS_SETTINGS.currency || '$';
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="fw-bold fs-6">${p.name}</div>
                        <small class="text-muted">${cur}${parseFloat(p.price).toFixed(2)} | Code: ${p.barcode || 'N/A'}</small>
                    </td>
                    <td>
                        <input type="number" min="0" class="form-control form-control-sm" 
                            value="${printQueue[p.id] || 0}" 
                            oninput="updateQueue(${p.id}, '${p.name}', '${p.price}', '${p.barcode || p.id}', this.value)">
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick="quickAdd(${p.id}, '${p.name}', '${p.price}', '${p.barcode || p.id}')">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } catch (e) {
        console.error(e);
    }
}

function quickAdd(id, name, price, barcode) {
    const qty = (printQueue[id] ? printQueue[id].qty : 0) + 1;
    updateQueue(id, name, price, barcode, qty);
    loadProducts(); // re-render to update input
}

function updateQueue(id, name, price, barcode, qty) {
    qty = parseInt(qty) || 0;
    if(qty <= 0) {
        delete printQueue[id];
    } else {
        printQueue[id] = { name, price, barcode, qty };
    }
    renderPreview();
}

function renderPreview() {
    const preview = document.getElementById('labelsPreview');
    const printArea = document.getElementById('printableArea');
    const cur = window.POS_SETTINGS.currency || '$';

    if (Object.keys(printQueue).length === 0) {
        preview.innerHTML = '<div class="text-center py-5 text-muted"><p>No labels selected.</p></div>';
        printArea.innerHTML = '';
        return;
    }

    let html = '<div class="row g-3">';
    let printHtml = '';

    for (let id in printQueue) {
        const item = printQueue[id];
        for (let i = 0; i < item.qty; i++) {
            const labelId = `label-${id}-${i}`;
            const labelContent = `
                <div class="col-4 mb-3">
                    <div class="border bg-white text-center p-2 rounded shadow-sm" style="min-height: 120px;">
                        <span class="d-block small fw-bold text-truncate">${item.name}</span>
                        <span class="d-block fs-5 fw-bold text-primary">${cur}${parseFloat(item.price).toFixed(2)}</span>
                        <svg id="${labelId}" class="w-100 mt-1" style="height: 40px;"></svg>
                    </div>
                </div>
            `;
            html += labelContent;
            
            // For print area, we use a simpler version
            printHtml += `
                <div class="label-card">
                    <span class="label-name">${item.name}</span>
                    <span class="label-price">${cur}${parseFloat(item.price).toFixed(2)}</span>
                    <svg id="print-${labelId}" class="label-barcode"></svg>
                </div>
            `;
        }
    }

    preview.innerHTML = html + '</div>';
    printArea.innerHTML = printHtml;

    // Generate SVGs
    for (let id in printQueue) {
        const item = printQueue[id];
        for (let i = 0; i < item.qty; i++) {
            const labelId = `label-${id}-${i}`;
            const barcodeVal = item.barcode || id;
            
            JsBarcode(`#${labelId}`, barcodeVal, {
                format: "CODE128",
                width: 1,
                height: 30,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
            JsBarcode(`#print-${labelId}`, barcodeVal, {
                format: "CODE128",
                width: 2,
                height: 40,
                displayValue: true,
                fontSize: 12,
                margin: 0
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', loadProducts);
</script>

<?php require_once 'includes/footer.php'; ?>
