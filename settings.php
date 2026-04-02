<?php
// C:\xampp\htdocs\pos\settings.php
$require_login = true;
require_once 'includes/header.php';
require_role(['super_admin']);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-gear-fill me-2 text-secondary"></i> Company Settings</h2>
        <p class="text-muted">Configure your global brand identity, email automation, and system integrity.</p>
    </div>
</div>

<div class="row">
    <!-- Left Navigation Sidebar -->
    <div class="col-md-3">
        <div class="nav flex-column nav-pills me-3 premium-card p-3 shadow-sm mb-4" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            <button class="nav-link active fw-bold text-start p-3 mb-2" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                <i class="bi bi-building me-2"></i> Company Profile
            </button>
            <button class="nav-link fw-bold text-start p-3 mb-2" id="v-pills-email-tab" data-bs-toggle="pill" data-bs-target="#v-pills-email" type="button" role="tab">
                <i class="bi bi-envelope-at me-2"></i> Email & Notifications
            </button>
            <button class="nav-link fw-bold text-start p-3 mb-2" id="v-pills-update-tab" data-bs-toggle="pill" data-bs-target="#v-pills-update" type="button" role="tab">
                <i class="bi bi-cloud-arrow-down me-2 text-primary"></i> System Update
            </button>
            <button class="nav-link fw-bold text-start p-3" id="v-pills-maintenance-tab" data-bs-toggle="pill" data-bs-target="#v-pills-maintenance" type="button" role="tab">
                <i class="bi bi-tools me-2 text-warning"></i> System Maintenance
            </button>
        </div>
    </div>

    <!-- Right Content Area -->
    <div class="col-md-9">
        <div id="alertContainer"></div>
        
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- [TAB 1] Company Profile -->
            <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                <div class="premium-card p-4">
                    <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-building-fill me-2"></i> Company Identity</h5>
                    <form id="settingsForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-4 text-center border-end">
                                <div class="mb-3 bg-light rounded-circle d-inline-block p-3 shadow-sm" style="width: 120px; height: 120px; display: flex !important; align-items: center; justify-content: center;">
                                    <img id="logoPreview" src="https://placehold.co/150x50?text=No+Logo" alt="Brand Logo" style="max-height: 80px; max-width: 100px; object-fit: contain;">
                                </div>
                                <label class="form-label fw-bold d-block">Company Logo</label>
                                <input class="form-control form-control-sm" type="file" name="company_logo" id="company_logo" accept="image/*">
                                <small class="text-muted d-block mt-2">Recommended: PNG/JPG</small>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Legal Company Name</label>
                                    <input type="text" class="form-control" name="company_name" id="company_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Primary Address</label>
                                    <textarea class="form-control" name="company_address" id="company_address" rows="2"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Contact Email</label>
                                        <input type="email" class="form-control" name="company_email" id="company_email">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Contact Phone</label>
                                        <input type="text" class="form-control" name="company_phone" id="company_phone">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4 opacity-25">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Invoice Footer Message</label>
                                <textarea class="form-control" name="invoice_footer_message" id="invoice_footer_message" rows="2" placeholder="Thank you for your business..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Currency Symbol</label>
                                <input type="text" class="form-control" name="currency_symbol" id="currency_symbol" required maxlength="10">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Low Stock Alert Threshold</label>
                                <input type="number" class="form-control" name="low_stock_threshold" id="low_stock_threshold" min="0" required>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary fw-bold px-5 py-2 shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- [TAB 2] Email & Notifications -->
            <div class="tab-pane fade" id="v-pills-email" role="tabpanel">
                <div class="premium-card p-4">
                    <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-envelope-at-fill me-2"></i> Email & Communications</h5>
                    <form id="emailSettingsForm">
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border h-100 shadow-sm">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="email_alerts_enabled" name="email_alerts_enabled" value="1">
                                    <label class="form-check-label fw-bold" for="email_alerts_enabled">Live Low Stock Alerts</label>
                                    <small class="d-block text-muted">Notify admin immediately of critical stock levels.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border h-100 shadow-sm">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="email_daily_summary_enabled" name="email_daily_summary_enabled" value="1">
                                    <label class="form-check-label fw-bold" for="email_daily_summary_enabled">Daily Business Digest</label>
                                    <small class="d-block text-muted">Nightly performance snapshot sent to owner.</small>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-4 rounded-4 border mb-4">
                            <h6 class="fw-bold mb-3 small text-muted text-uppercase">SMTP Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold small">SMTP Host</label>
                                    <input type="text" class="form-control shadow-none" name="smtp_host" id="smtp_host">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Port</label>
                                    <input type="text" class="form-control shadow-none" name="smtp_port" id="smtp_port">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Username / Email</label>
                                    <input type="text" class="form-control shadow-none" name="smtp_user" id="smtp_user">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">App Password <a href="#" class="ms-1" data-bs-toggle="modal" data-bs-target="#gmailHelpModal"><i class="bi bi-question-circle"></i></a></label>
                                    <input type="password" class="form-control shadow-none" name="smtp_pass" id="smtp_pass">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Encryption</label>
                                    <select class="form-select shadow-none" name="smtp_encryption" id="smtp_encryption">
                                        <option value="tls">TLS (Recommended)</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Sender Address</label>
                                    <input type="email" class="form-control shadow-none" name="smtp_from_email" id="smtp_from_email">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-4">
                            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="saveEmailBtn">Update SMTP</button>
                            <button type="button" class="btn btn-outline-info fw-bold" id="testEmailBtn"><i class="bi bi-send-fill me-1"></i> Connection Test</button>
                            <button type="button" class="btn btn-outline-success fw-bold" id="triggerDailyBtn"><i class="bi bi-calendar-check-fill me-1"></i> Send Daily Summary Now</button>
                            <button type="button" class="btn btn-outline-primary fw-bold" id="triggerMonthlyBtn"><i class="bi bi-trophy-fill me-1"></i> Send Monthly Summary Now</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold ms-auto" data-bs-toggle="modal" data-bs-target="#automationGuideModal"><i class="bi bi-info-circle me-1"></i> Guide</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- [TAB 3] System Update -->
            <div class="tab-pane fade" id="v-pills-update" role="tabpanel">
                <div class="premium-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1"><i class="bi bi-cloud-arrow-down-fill text-primary me-2"></i> XentraUpdate Core</h4>
                            <p class="text-muted small mb-0">Synchronize your local files with the latest GitHub stable release.</p>
                        </div>
                        <span class="badge bg-light text-dark border p-2 px-3 fw-bold" id="localVersionBadge">v... Loading</span>
                    </div>

                    <div id="updateStatusArea" class="mb-4">
                         <!-- Initial state for manual check -->
                         <div class="text-center py-5 bg-light rounded-4 border dashed">
                            <i class="bi bi-github fs-1 text-muted mb-3 d-block"></i>
                            <h6 class="fw-bold">Ready to check for updates</h6>
                            <p class="small text-muted mb-4 opacity-75">Establishing a manual connection to the primary GitHub repository.</p>
                            <button type="button" class="btn btn-dark fw-bold px-4 shadow-sm" id="manualCheckBtn">
                               <i class="bi bi-search me-1"></i> Check Now
                            </button>
                         </div>
                    </div>
                    
                    <div class="bg-light p-4 rounded-4 border">
                        <h6 class="fw-bold small text-muted text-uppercase mb-3"><i class="bi bi-shield-check me-1 text-success"></i> Safe Sync Guarantee</h6>
                        <ul class="text-muted small ps-3 mb-0">
                            <li>Your local configuration (<code class="text-dark">database.php</code>) is **never** overwritten.</li>
                            <li>Company identity assets and uploaded Logos are **always** preserved.</li>
                            <li>SQL Migrations ensure your database schema stays future-proof.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- [TAB 4] System Maintenance -->
            <div class="tab-pane fade" id="v-pills-maintenance" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="premium-card p-4 h-100 flex-column d-flex">
                            <h5 class="fw-bold mb-3"><i class="bi bi-database-fill-down me-2 text-warning"></i> Data Backups</h5>
                            <p class="text-muted small mb-4">Export a complete SQL snapshot of your products, sales data, and customer registry for safekeeping.</p>
                            <a href="/pos/api/system/backup.php" class="btn btn-warning w-100 fw-bold py-3 shadow-sm border-0 mt-auto">
                                <i class="bi bi-cloud-download-fill me-2"></i> Download SQL Archive
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="premium-card p-4 h-100 flex-column d-flex">
                            <h5 class="fw-bold mb-3"><i class="bi bi-qr-code-scan me-2 text-dark"></i> Branding Tools</h5>
                            <p class="text-muted small mb-4">Automatically generate and print high-quality barcode labels for your shelf pricing tags.</p>
                            <a href="/pos/barcodes.php" class="btn btn-outline-dark w-100 fw-bold py-3 shadow-sm mt-auto">
                                <i class="bi bi-upc-scan me-2"></i> Open Barcode Designer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // 1. UI Elements Initialization
    const manualCheckBtn = document.getElementById('manualCheckBtn');
    const updateArea = document.getElementById('updateStatusArea');
    const localBadge = document.getElementById('localVersionBadge');
    const alertContainer = document.getElementById('alertContainer');

    // 2. Tab Persistence Logic
    const activeTab = localStorage.getItem('lastSettingsTab');
    if (activeTab) {
        const tabEl = document.querySelector(`#${activeTab}`);
        if (tabEl) {
            const tabObj = new bootstrap.Tab(tabEl);
            tabObj.show();
        }
    }

    document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            localStorage.setItem('lastSettingsTab', e.target.id);
        });
    });

    // 3. Load All Settings from API
    try {
        const res = await fetch('api/settings/get.php');
        const data = await res.json();
        
        if(data.success) {
            // Identity fields
            const identityFields = ['company_name', 'company_address', 'company_email', 'company_phone', 'invoice_footer_message', 'currency_symbol', 'low_stock_threshold'];
            identityFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = data.data[id] || '';
            });
            
            if(data.data.company_logo) {
                const lp = document.getElementById('logoPreview');
                if (lp) lp.src = data.data.company_logo;
            }

            // Email & SMTP fields
            const emailFields = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption', 'smtp_from_email'];
            emailFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = data.data[id] || '';
            });
            
            const alertsEnabled = document.getElementById('email_alerts_enabled');
            if (alertsEnabled) alertsEnabled.checked = (data.data.email_alerts_enabled === '1');
            
            const dailyEnabled = document.getElementById('email_daily_summary_enabled');
            if (dailyEnabled) dailyEnabled.checked = (data.data.email_daily_summary_enabled === '1');
        }
    } catch(e) { 
        console.error('Initial Settings Load Failed', e); 
    }

    // 4. Logo Preview Handler
    const logoInput = document.getElementById('company_logo');
    if (logoInput) {
        logoInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const lp = document.getElementById('logoPreview');
                    if (lp) lp.src = e.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // 5. Shared Form Submission Logic
    const saveSettings = async (e, formId) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        const formData = new FormData(e.target);
        if (formId === 'emailSettingsForm') {
            if(!formData.has('email_alerts_enabled')) formData.append('email_alerts_enabled', '0');
            if(!formData.has('email_daily_summary_enabled')) formData.append('email_daily_summary_enabled', '0');
        }

        try {
            const res = await fetch('api/settings/update.php', { method: 'POST', body: formData });
            const result = await res.json();
            if(!res.ok) throw new Error(result.message);
            showAlert(result.message, 'success');
        } catch(err) {
            showAlert('Update Failed: ' + err.message, 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    };

    const sForm = document.getElementById('settingsForm');
    if (sForm) sForm.onsubmit = (e) => saveSettings(e, 'settingsForm');
    
    const eForm = document.getElementById('emailSettingsForm');
    if (eForm) eForm.onsubmit = (e) => saveSettings(e, 'emailSettingsForm');

    // 6. Test Email Logic
    const testEmailBtn = document.getElementById('testEmailBtn');
    if (testEmailBtn) {
        testEmailBtn.onclick = async () => {
            const originalText = testEmailBtn.innerHTML;
            testEmailBtn.disabled = true;
            testEmailBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
            try {
                const res = await fetch('api/system/test_email.php');
                const data = await res.json();
                if(data.success) showAlert('Success! Connection healthy.', 'success');
                else throw new Error(data.message);
            } catch(err) { 
                showAlert(err.message, 'danger'); 
            } finally { 
                testEmailBtn.disabled = false; 
                testEmailBtn.innerHTML = originalText; 
            }
        };
    }

    // 7. Manual Summary Triggers
    const triggerDailyBtn = document.getElementById('triggerDailyBtn');
    if (triggerDailyBtn) {
        triggerDailyBtn.onclick = async () => {
            triggerDailyBtn.disabled = true;
            try {
                const res = await fetch('api/system/daily_cron.php');
                const data = await res.json();
                if(data.success) showAlert('Daily Digest dispatched.', 'success');
            } finally { triggerDailyBtn.disabled = false; }
        };
    }

    const triggerMonthlyBtn = document.getElementById('triggerMonthlyBtn');
    if (triggerMonthlyBtn) {
        triggerMonthlyBtn.onclick = async () => {
            triggerMonthlyBtn.disabled = true;
            try {
                const res = await fetch('api/system/monthly_cron.php');
                const data = await res.json();
                if(data.success) showAlert('Monthly Summary dispatched.', 'success');
            } finally { triggerMonthlyBtn.disabled = false; }
        };
    }

    // 8. XentraUpdate Core Logic
    async function checkSystemUpdate() {
        if (!updateArea) return;
        updateArea.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm me-2"></div><span class="small fw-bold">Connecting...</span></div>`;

        try {
            const res = await fetch('api/system/update_core.php?action=check');
            const data = await res.json();
            
            if (data.success) {
                if(localBadge) localBadge.innerText = 'v' + data.current + ' (Current)';
                
                if (data.hasUpdate) {
                    const patchNotesHtml = (data.patch_notes && data.patch_notes.length > 0) 
                        ? `<div class="bg-white bg-opacity-25 rounded-3 p-3 mb-3"><h6 class="small fw-bold opacity-75 mb-2"><i class="bi bi-list-check me-1"></i> Updating Modules:</h6><ul class="small mb-0 ps-3">${data.patch_notes.map(note => `<li>${note}</li>`).join('')}</ul></div>` 
                        : '';

                    updateArea.innerHTML = `
                        <div class="alert alert-warning border-0 shadow-sm mb-0">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-rocket-takeoff-fill me-2 fs-5 text-dark"></i>
                                <h6 class="fw-bold mb-0 text-dark">New Build Identified: v${data.latest}</h6>
                            </div>
                            <p class="small text-dark mb-3 opacity-75">${data.description || `Build released on ${data.release_date}.`}</p>
                            ${patchNotesHtml}
                            <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm" id="applyUpdateBtn">
                                Apply Synchronized Update Now
                            </button>
                        </div>
                    `;
                    const applyBtn = document.getElementById('applyUpdateBtn');
                    if (applyBtn) applyBtn.onclick = applyUpdate;
                } else {
                    updateArea.innerHTML = `
                        <div class="alert alert-success border-0 shadow-sm mb-0 d-flex align-items-center py-3">
                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Local system is healthy</h6>
                                <p class="small mb-0 text-dark opacity-75">You are running the latest stable build.</p>
                            </div>
                        </div>
                    `;
                }
            } else { throw new Error(data.message); }
        } catch (err) {
            updateArea.innerHTML = `
                <div class="alert alert-light border shadow-sm mb-0 p-3 small text-muted text-center">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> GitHub Sync Failed. <button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="location.reload()">Retry Connection</button>
                </div>
            `;
        }
    }

    async function applyUpdate() {
        if (!confirm("Proceed with GitHub synchronization? (Local config/logo will be protected)")) return;
        if (updateArea) updateArea.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary mb-3"></div><h6 class="fw-bold">Merging Assets...</h6></div>`;
        try {
            const res = await fetch('api/system/update_core.php?action=apply');
            const data = await res.json();
            if (data.success) {
                updateArea.innerHTML = `<div class="alert alert-success border-0 text-center py-4"><i class="bi bi-check-circle fs-1 mb-3 d-block"></i><h6 class="fw-bold text-dark">Sync Successful!</h6><button class="btn btn-dark btn-sm mt-3" onclick="location.reload()">Refresh System</button></div>`;
            } else throw new Error(data.message);
        } catch (err) { 
            showAlert(err.message, 'danger'); 
            checkSystemUpdate(); 
        }
    }

    if (manualCheckBtn) manualCheckBtn.onclick = checkSystemUpdate;

    const forceSyncLink = document.getElementById('forceSyncLink');
    if (forceSyncLink) {
        forceSyncLink.onclick = () => {
            if (confirm("FORCE RE-SYNC?\n\nThis will re-download the entire XentraPOS system from GitHub.\n\nUse this only if your system is reporting 'Healthy' but is broken.")) {
                applyUpdate();
            }
        };
    }
});

function showAlert(message, type = 'success', containerId = 'alertContainer') {
    const container = document.getElementById(containerId);
    if (!container) return;
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show shadow-sm border-0 mb-4`;
    alert.innerHTML = `<div class="d-flex align-items-center"><i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2 fs-5"></i><div>${message}</div></div><button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    container.innerHTML = ''; 
    container.appendChild(alert);
}
</script>

<!-- Modals -->
<div class="modal fade" id="gmailHelpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title fw-bold">Gmail Setup</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="small text-muted mb-3">Professional Gmail automation requires an **App Password** for security:</p>
        <ol class="small list-group list-group-numbered border-0">
          <li class="list-group-item border-0 px-0">Enable **2-Step Verification** in Google Settings.</li>
          <li class="list-group-item border-0 px-0">Search for "App Passwords" in your account.</li>
          <li class="list-group-item border-0 px-0">Create a new key for "XentraPOS".</li>
          <li class="list-group-item border-0 px-0">Copy the 16-character code into the password field.</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="automationGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-robot me-2"></i> Report Automation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
          <div class="row g-4">
              <div class="col-md-6 border-end">
                  <h6 class="fw-bold text-primary mb-3">Option 1: Login Heartbeat (Active)</h6>
                  <p class="small text-muted">XentraPOS checks for missing reports automatically whenever an admin logs in across your network.</p>
              </div>
              <div class="col-md-6">
                  <h6 class="fw-bold text-success mb-3">Option 2: Windows Background Task</h6>
                  <p class="small text-muted">Use Task Scheduler pointing to <code class="text-dark">pos/scripts/daily_summary.bat</code> for precision dispatch.</p>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
