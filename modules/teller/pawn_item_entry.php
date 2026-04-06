<?php
// modules/teller/pawn_item_entry.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

if (!isset($_GET['customer_id'])) { header("Location: new_pawn.php?error=NoCustomer"); exit(); }

$customer_id = $_GET['customer_id'];
$teller_id = $_SESSION['account_id'];
$pt_number = "PT-" . date('Y') . "-" . rand(10000, 99999);

$stmt = $conn->prepare("SELECT * FROM profiles WHERE profile_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
?>

<style>
    /* Premium FinTech UI */
    :root {
        --fintech-bg: #f4f7f6;
        --fintech-card: #ffffff;
        --fintech-primary: #0d6efd;
        --fintech-border: #e2e8f0;
    }
    body { background-color: var(--fintech-bg); }

    /* Custom Form Elements */
    .form-floating > .form-control, .form-floating > .form-select { 
        height: 3.5rem; font-weight: 600; color: #1e293b; background-color: #f8fafc; border: 1px solid var(--fintech-border); border-radius: 0.75rem; 
    }
    .form-floating > label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; padding: 1rem 1rem; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1) !important; border-color: var(--fintech-primary) !important; background-color: #fff !important; }
    
    /* Security Check Toggles */
    .security-check { border: 2px solid var(--fintech-border); transition: all 0.2s ease; cursor: pointer; border-radius: 1rem; }
    .btn-check:checked + .security-check { border-color: #198754; background-color: rgba(25, 135, 84, 0.05); }
    .btn-check:checked + .security-check .status-icon { color: #198754; content: "\f058"; } 
    
    /* Inclusions Pills */
    .btn-check:checked + .inc-pill { background-color: var(--fintech-primary); color: white; border-color: var(--fintech-primary); box-shadow: 0 4px 10px rgba(13,110,253,0.3); transform: translateY(-2px); }
    .inc-pill { transition: all 0.2s ease; border: 1px solid var(--fintech-border); color: #475569; font-weight: 600; background: white; }

    /* Image Upload Zones */
    .upload-zone { border: 2px dashed #cbd5e1; transition: all 0.2s ease; background: #f8fafc; cursor: pointer; overflow: hidden; border-radius: 1rem;}
    .upload-zone:hover { border-color: var(--fintech-primary); background: #f1f5f9; transform: translateY(-2px); }
    .upload-zone img { transition: opacity 0.3s ease; }
    .upload-zone:hover img { opacity: 0.8; }
    
    /* Receipt Sidebar */
    .receipt-sidebar { border-top: 6px solid var(--fintech-primary); background: white; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .digital-readout { font-size: 2.75rem; letter-spacing: -1px; line-height: 1; font-family: 'Courier New', Courier, monospace; }
    .receipt-divider { border-bottom: 2px dashed var(--fintech-border); margin: 1.5rem 0; position: relative; }
    
    .step-badge { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--fintech-primary); color: white; font-weight: bold; margin-right: 12px; }
</style>

<div class="container-fluid py-4 px-lg-5 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-file-signature me-2 text-primary"></i> Generate Pawn Contract</h3>
            <p class="text-muted small mb-0">Step 2: Record collateral details and finalize terms.</p>
        </div>
        <a href="new_pawn.php" class="btn btn-white border shadow-sm fw-bold rounded-pill px-4 text-secondary hover-lift">
            <i class="fa-solid fa-arrow-left me-2"></i> Cancel & Go Back
        </a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4 rounded-4 d-flex align-items-center p-4">
            <i class="fa-solid fa-circle-check fa-2x me-3 text-success"></i>
            <div class="fw-bold"><?php echo urldecode($_GET['msg']); ?></div>
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="../../core/process_transaction.php" method="POST" id="pawnForm" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
        <input type="hidden" name="teller_id" value="<?php echo $teller_id; ?>">
        <input type="hidden" name="pt_number" value="<?php echo $pt_number; ?>">

        <div class="row g-4">
            <div class="col-lg-7">
                
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-dark text-white">
                    <div class="card-body p-4 d-flex align-items-center position-relative">
                        <i class="fa-solid fa-fingerprint position-absolute opacity-10" style="font-size: 6rem; right: -10px; top: -10px;"></i>
                        <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold me-4 shadow" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <?php echo substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1); ?>
                        </div>
                        <div class="position-relative z-index-1">
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 mb-2 px-2 py-1"><i class="fa-solid fa-check-circle me-1"></i> Customer Verified</span>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                            <div class="text-white-50 font-monospace small"><i class="fa-regular fa-id-badge me-1"></i> ID: <?php echo $customer['public_id']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4 p-lg-5">
                        
                        <div class="d-flex align-items-center mb-4">
                            <div class="step-badge">1</div>
                            <h5 class="fw-bold text-dark mb-0">Hardware Specifications</h5>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="device_type" id="device_type" class="form-select" required onchange="fetchApiSpecs()">
                                        <option value="" disabled selected>Select Category...</option>
                                        <option value="Smartphone">Smartphone</option>
                                        <option value="Laptop">Laptop</option>
                                        <option value="Tablet">Tablet</option>
                                        <option value="Smartwatch">Smartwatch</option>
                                        <option value="Gaming Console">Gaming Console</option>
                                        <option value="Camera">Camera</option>
                                    </select>
                                    <label>Device Category <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="brand" id="brand_input" class="form-control" list="brands" placeholder="Brand" required autocomplete="off">
                                    <label>Brand / Manufacturer <span class="text-danger">*</span></label>
                                    <datalist id="brands">
                                        <option value="Apple"><option value="Samsung"><option value="Sony">
                                    </datalist>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="model" class="form-control" placeholder="Model" required>
                                    <label>Model Name <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="serial_number" id="serial" class="form-control font-monospace text-primary" placeholder="Serial" required>
                                    <label>Serial / IMEI Number <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-5" id="dynamic_specs">
                            </div>

                        <hr class="my-5 opacity-10">

                        <div class="d-flex align-items-center mb-4">
                            <div class="step-badge bg-warning text-dark">2</div>
                            <h5 class="fw-bold text-dark mb-0">Security & Inspection</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <input type="checkbox" class="btn-check" id="check_lock" required>
                                <label class="security-check w-100 p-3 d-flex align-items-center bg-white shadow-sm" for="check_lock">
                                    <div class="me-3 fs-3 text-muted"><i class="fa-solid fa-cloud status-icon"></i></div>
                                    <div>
                                        <span class="d-block fw-bold text-dark mb-1">Cloud Locks Removed?</span>
                                        <span class="small text-muted d-block" style="font-size: 0.7rem; line-height:1.2;">Ensure iCloud/Google accounts are signed out.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" class="btn-check" id="check_pass" required>
                                <label class="security-check w-100 p-3 d-flex align-items-center bg-white shadow-sm" for="check_pass">
                                    <div class="me-3 fs-3 text-muted"><i class="fa-solid fa-lock-open status-icon"></i></div>
                                    <div>
                                        <span class="d-block fw-bold text-dark mb-1">Passcode Removed?</span>
                                        <span class="small text-muted d-block" style="font-size: 0.7rem; line-height:1.2;">Device must be accessible for testing.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2 d-block">Included Accessories</label>
                            <div class="d-flex flex-wrap gap-2">
                                <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_unit" value="Unit Only" checked>
                                <label class="btn inc-pill rounded-pill px-4 py-2" for="inc_unit">Unit Only</label>

                                <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_box" value="Original Box">
                                <label class="btn inc-pill rounded-pill px-4 py-2" for="inc_box">Original Box</label>

                                <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_charger" value="Original Charger">
                                <label class="btn inc-pill rounded-pill px-4 py-2" for="inc_charger">Orig. Charger</label>

                                <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_receipt" value="Receipt">
                                <label class="btn inc-pill rounded-pill px-4 py-2" for="inc_receipt">Store Receipt</label>
                            </div>
                        </div>

                        <div class="form-floating mb-5">
                            <textarea name="condition_notes" class="form-control" style="height: 100px; resize: none;" placeholder="Notes" required></textarea>
                            <label>Condition Notes (Scratches, Dents, Dead Pixels) <span class="text-danger">*</span></label>
                        </div>

                        <hr class="my-5 opacity-10">

                        <div class="d-flex align-items-center mb-4">
                            <div class="step-badge bg-info">3</div>
                            <h5 class="fw-bold text-dark mb-0">Photographic Evidence</h5>
                        </div>
                        
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <div class="upload-zone p-4 text-center position-relative h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fa-solid fa-camera fs-2 text-muted opacity-50 mb-2"></i>
                                    <div class="fw-bold text-muted text-uppercase" style="font-size:0.7rem;">Front View</div>
                                    <input type="file" name="img_front" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" accept="image/*" required onchange="previewImage(this, 'prev1')">
                                    <img id="prev1" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover d-none" style="z-index: 2; pointer-events: none;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="upload-zone p-4 text-center position-relative h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fa-solid fa-mobile-button fs-2 text-muted opacity-50 mb-2"></i>
                                    <div class="fw-bold text-muted text-uppercase" style="font-size:0.7rem;">Back / Sides</div>
                                    <input type="file" name="img_back" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" accept="image/*" required onchange="previewImage(this, 'prev2')">
                                    <img id="prev2" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover d-none" style="z-index: 2; pointer-events: none;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="upload-zone p-4 text-center position-relative h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fa-solid fa-barcode fs-2 text-muted opacity-50 mb-2"></i>
                                    <div class="fw-bold text-muted text-uppercase" style="font-size:0.7rem;">Serial / IMEI</div>
                                    <input type="file" name="img_serial" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" accept="image/*" required onchange="previewImage(this, 'prev3')">
                                    <img id="prev3" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover d-none" style="z-index: 2; pointer-events: none;">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="receipt-sidebar position-sticky" style="top: 20px;">
                    
                    <div class="p-4 p-xl-5">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-receipt fs-1 text-primary opacity-25 mb-2"></i>
                            <h6 class="text-muted text-uppercase fw-bold mb-1 ls-1" style="font-size: 0.75rem;">Contract Number</h6>
                            <h3 class="font-monospace text-dark fw-bold mb-0"><?php echo $pt_number; ?></h3>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="d-flex align-items-center mb-4 mt-4">
                            <div class="step-badge bg-success">4</div>
                            <h6 class="fw-bold text-dark mb-0 text-uppercase letter-spacing-1">Financial Terms</h6>
                        </div>

                        <div class="form-floating mb-4">
                            <select name="term_months" id="term_months" class="form-select bg-light border-0 fw-bold text-dark" onchange="updateDates()">
                                <option value="1">1 Month (30 Days)</option>
                                <option value="2">2 Months (60 Days)</option>
                                <option value="3">3 Months (90 Days)</option>
                                <option value="6">6 Months (180 Days)</option>
                            </select>
                            <label>Select Contract Term</label>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Principal Loan Amount <span class="text-danger">*</span></label>
                            <div class="input-group shadow-sm overflow-hidden" style="border: 2px solid var(--fintech-primary); border-radius: 1rem;">
                                <span class="input-group-text bg-white border-0 fw-bold text-primary fs-4 px-4">₱</span>
                                <input type="number" name="principal" id="principal" class="form-control border-0 shadow-none fw-bold text-dark fs-2 py-3" required step="0.01" oninput="updateNet()" placeholder="0.00">
                            </div>
                        </div>

                        <div class="bg-light rounded-4 border p-4 mb-4 text-center" id="netCashBox" style="transition: all 0.3s ease;">
                            <span class="text-muted small fw-bold text-uppercase ls-1 d-block mb-1">Cash to Release</span>
                            <div class="digital-readout fw-bold text-success mb-2">
                                ₱<span id="net_cash">0.00</span>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1" style="font-size: 0.65rem;">No Deductions Applied</span>
                        </div>

                        <div class="bg-white rounded-4 border p-3 mb-5">
                            <div class="d-flex justify-content-between mb-2 small pb-2 border-bottom">
                                <span class="text-muted fw-bold">Date Issued</span>
                                <span class="text-dark fw-bold"><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small pb-2 border-bottom">
                                <span class="text-muted fw-bold">Maturity Date</span>
                                <span class="text-primary fw-bold" id="maturity_display">---</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1">
                                <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Foreclosure</span>
                                <span class="text-danger fw-bold" id="expiry_display">---</span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 btn-lg rounded-pill shadow fw-bold py-3" onclick="prepareReviewModal()">
                            <i class="fa-solid fa-lock me-2"></i> REVIEW & GENERATE
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div class="modal fade" id="reviewModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-warning border-0 p-4">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-shield-halved me-2"></i> Final Verification</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light text-center">
                        <p class="text-muted mb-4">Verify the Principal Amount before dispensing cash. This action cannot be undone.</p>
                        
                        <div class="bg-white rounded-4 p-4 border shadow-sm mb-4 text-start">
                            <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size:0.7rem;">Collateral Registered</small>
                            <h5 class="fw-bold text-dark mb-1" id="review_item_name">---</h5>
                            <small class="font-monospace text-muted fw-bold" id="review_serial">---</small>
                        </div>

                        <div class="bg-success bg-opacity-10 rounded-4 p-4 border border-success border-opacity-25">
                            <small class="text-success text-uppercase fw-bold d-block mb-1 ls-1">Dispense Cash Amount</small>
                            <h1 class="fw-bold text-success mb-0 display-4">₱<span id="review_amount">0.00</span></h1>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-white d-flex">
                        <button type="button" class="btn btn-light border fw-bold flex-grow-1 rounded-pill py-3" data-bs-dismiss="modal">Edit Data</button>
                        <button type="submit" name="btn_save_pawn" class="btn btn-success fw-bold flex-grow-1 rounded-pill py-3 shadow-sm" id="finalSubmitBtn">
                            <i class="fa-solid fa-check-double me-1"></i> Lock Transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
// 1. Python Flask API Base URL (Make sure app.py is running!)
const API_BASE_URL = 'http://127.0.0.1:5000/api';

// 2. Fetch Schema from API dynamically
async function fetchApiSpecs() {
    const category = document.getElementById('device_type').value;
    const container = document.getElementById('dynamic_specs');
    
    // Clear input fields and clear the dynamic container
    document.getElementById('brand_input').value = "";
    container.innerHTML = ''; 
    
    if (!category) return;

    try {
        const response = await fetch(`${API_BASE_URL}/schema/${category}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const specs = data.dynamic_specs;
            
            // A. Populate Brands Datalist (Since Brand is hardcoded outside the dynamic block)
            if (specs.brands) {
                const brandList = document.getElementById('brands');
                brandList.innerHTML = ''; 
                specs.brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand;
                    brandList.appendChild(option);
                });
            }

            // B. DYNAMICALLY GENERATE DROPDOWNS FOR EVERYTHING ELSE!
            for (const [key, values] of Object.entries(specs)) {
                if (key === 'brands') continue; // We already did brands

                // Make the label look nice (e.g., 'camera_type' -> 'Camera Type')
                let labelText = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                // Create outer column
                let colDiv = document.createElement('div');
                colDiv.className = 'col-md-4';

                // Create Bootstrap floating div
                let floatDiv = document.createElement('div');
                floatDiv.className = 'form-floating';

                // Create SELECT element. 
                // We name it extra_specs[key] so PHP bundles them into an array!
                let select = document.createElement('select');
                select.name = `extra_specs[${key}]`; 
                select.className = 'form-select bg-light border-0';

                // Add a default "N/A" option
                let defaultOpt = document.createElement('option');
                defaultOpt.value = "N/A";
                defaultOpt.innerText = "N/A";
                select.appendChild(defaultOpt);

                // Add options from Python API
                values.forEach(val => {
                    let option = document.createElement('option');
                    option.value = val;
                    option.innerText = val;
                    select.appendChild(option);
                });

                // Create Label
                let label = document.createElement('label');
                label.innerText = labelText;

                // Stitch them together and push to container
                floatDiv.appendChild(select);
                floatDiv.appendChild(label);
                colDiv.appendChild(floatDiv);
                container.appendChild(colDiv);
            }

            // C. Always manually append the 'Color' field at the end
            // Note: named 'color' because your DB explicitly has a 'color' column
            container.innerHTML += `
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" name="color" class="form-control bg-light border-0" placeholder="Color">
                        <label>Device Color</label>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error("API Error - Could not connect to Python backend:", error);
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i> Error connecting to the specification database. Please enter details manually in the condition notes.</div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" name="color" class="form-control bg-light border-0" placeholder="Color">
                    <label>Device Color</label>
                </div>
            </div>
        `;
    }
}

function updateNet() {
    let principalInput = document.getElementById('principal').value;
    let principal = parseFloat(principalInput) || 0;
    document.getElementById('net_cash').innerText = principal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    let box = document.getElementById('netCashBox');
    if (principal > 0) { 
        box.classList.replace('bg-light', 'bg-success');
        box.classList.add('bg-opacity-10', 'border-success', 'border-opacity-25');
        box.style.transform = 'scale(1.02)'; 
    } else { 
        box.classList.replace('bg-success', 'bg-light');
        box.classList.remove('bg-opacity-10', 'border-success', 'border-opacity-25');
        box.style.transform = 'scale(1)'; 
    }
}

function updateDates() {
    let months = parseInt(document.getElementById('term_months').value);
    let today = new Date();
    let maturity = new Date(today);
    maturity.setMonth(today.getMonth() + months);
    let expiry = new Date(maturity);
    expiry.setDate(maturity.getDate() + 30); 
    let options = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('maturity_display').innerText = maturity.toLocaleDateString('en-US', options);
    document.getElementById('expiry_display').innerText = expiry.toLocaleDateString('en-US', options);
}

function previewImage(input, imgId) {
    const preview = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('d-none');
    }
}

function prepareReviewModal() {
    let form = document.getElementById('pawnForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        form.querySelector(':invalid').focus();
        return;
    }
    let brand = document.querySelector('input[name="brand"]').value;
    let model = document.querySelector('input[name="model"]').value;
    let serial = document.getElementById('serial').value;
    let principal = parseFloat(document.getElementById('principal').value) || 0;

    document.getElementById('review_item_name').innerText = brand + ' ' + model;
    document.getElementById('review_serial').innerText = 'SN/IMEI: ' + serial;
    document.getElementById('review_amount').innerText = principal.toLocaleString('en-US', {minimumFractionDigits: 2});

    var myModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    myModal.show();
}

document.getElementById('pawnForm').addEventListener('submit', function() {
    let btn = document.getElementById('finalSubmitBtn');
    setTimeout(function() {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';
        btn.disabled = true;
    }, 50); 
});

window.onload = function() {
    updateNet();
    updateDates();
};
</script>

<?php include_once '../../includes/teller_footer.php'; ?>