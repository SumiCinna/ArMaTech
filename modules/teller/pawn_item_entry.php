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
    .btn-check:checked + .btn-outline-secondary { background-color: #0d6efd; color: white; border-color: #0d6efd; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important; border-color: #0d6efd !important; background-color: #fff !important; }
    .amount-display { font-size: 2.5rem; letter-spacing: -1px; line-height: 1; }
    .ls-1 { letter-spacing: 1px; }
</style>

<div class="container-fluid py-4 px-lg-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-hand-holding-dollar me-2 text-primary"></i> New Transaction</h3>
            <p class="text-muted small mb-0">Record collateral details and generate a pawn contract.</p>
        </div>
        <a href="new_pawn.php" class="btn btn-light border shadow-sm fw-bold rounded-pill px-3 text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo urldecode($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="../../core/process_transaction.php" method="POST" id="pawnForm">
        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
        <input type="hidden" name="teller_id" value="<?php echo $teller_id; ?>">
        <input type="hidden" name="pt_number" value="<?php echo $pt_number; ?>">

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
            <div class="row g-0">
                
                <!-- Left Column: Collateral Info -->
                <div class="col-lg-7 p-4 p-lg-5 bg-white">
                    
                    <!-- Customer Context Badge -->
                    <div class="d-flex align-items-center mb-4 p-3 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm border" style="width: 50px; height: 50px; font-size: 1.2rem;">
                            <?php echo substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1); ?>
                        </div>
                        <div>
                            <small class="text-primary text-uppercase fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Pawnee / Customer</small>
                            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                            <span class="font-monospace text-muted small">ID: <?php echo $customer['public_id']; ?></span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-box-open me-2 text-primary"></i> Item Specifications</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Device Category <span class="text-danger">*</span></label>
                            <select name="device_type" class="form-select bg-light border-0 shadow-none py-2" required>
                                <option value="" disabled selected>-- Select --</option>
                                <option value="Smartphone">Smartphone</option>
                                <option value="Laptop">Laptop</option>
                                <option value="Tablet">Tablet</option>
                                <option value="Smartwatch">Smartwatch</option>
                                <option value="Gaming Console">Gaming Console</option>
                                <option value="Camera">Camera</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Brand / Manufacturer <span class="text-danger">*</span></label>
                            <input type="text" name="brand" class="form-control bg-light border-0 shadow-none py-2" placeholder="e.g. Apple, Samsung" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Model Name <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control bg-light border-0 shadow-none py-2" placeholder="e.g. iPhone 13 Pro" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Serial / IMEI Number <span class="text-danger">*</span></label>
                            <input type="text" name="serial_number" class="form-control bg-light border-0 shadow-none py-2 font-monospace" placeholder="XXXXXXXXXXXX" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Storage</label>
                            <select name="storage" class="form-select bg-light border-0 shadow-none py-2">
                                <option value="64GB">64GB</option>
                                <option value="128GB" selected>128GB</option>
                                <option value="256GB">256GB</option>
                                <option value="512GB">512GB</option>
                                <option value="1TB">1TB</option>
                                <option value="Other">Other / N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Memory (RAM)</label>
                            <select name="ram" class="form-select bg-light border-0 shadow-none py-2">
                                <option value="4GB">4GB</option>
                                <option value="8GB" selected>8GB</option>
                                <option value="12GB">12GB</option>
                                <option value="16GB">16GB</option>
                                <option value="32GB">32GB</option>
                                <option value="Other">Other / N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Color</label>
                            <input type="text" name="color" class="form-control bg-light border-0 shadow-none py-2" placeholder="e.g. Space Gray">
                        </div>
                    </div>

                    <hr class="my-4 opacity-25">

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.7rem;">Included Accessories</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_unit" value="Unit Only" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-bold border shadow-sm" style="font-size: 0.8rem;" for="inc_unit">Unit Only</label>

                            <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_box" value="Original Box" autocomplete="off">
                            <label class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-bold border shadow-sm" style="font-size: 0.8rem;" for="inc_box">Original Box</label>

                            <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_charger" value="Original Charger" autocomplete="off">
                            <label class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-bold border shadow-sm" style="font-size: 0.8rem;" for="inc_charger">Orig. Charger</label>

                            <input type="checkbox" class="btn-check" name="inclusions[]" id="inc_receipt" value="Receipt" autocomplete="off">
                            <label class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-bold border shadow-sm" style="font-size: 0.8rem;" for="inc_receipt">Store Receipt</label>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Condition Notes / Issues</label>
                        <textarea name="condition_notes" class="form-control bg-light border-0 shadow-none" style="height: 100px; resize: none;" placeholder="Note any dents, scratches, battery health, or defects..."></textarea>
                    </div>
                </div>

                <!-- Right Column: Financial Calculator -->
                <div class="col-lg-5 p-4 p-lg-5 border-start" style="background-color: #f8fafc;">
                    
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <h6 class="text-muted text-uppercase fw-bold mb-1 ls-1" style="font-size: 0.75rem;">Transaction Number</h6>
                        <h3 class="font-monospace text-primary fw-bold mb-0"><?php echo $pt_number; ?></h3>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">1. Choose Contract Term</label>
                        <select name="term_months" id="term_months" class="form-select form-select-lg bg-white shadow-sm border border-secondary border-opacity-25 fw-bold" onchange="updateDates()">
                            <option value="1">1 Month (30 Days)</option>
                            <option value="2">2 Months (60 Days)</option>
                            <option value="3">3 Months (90 Days)</option>
                            <option value="6">6 Months (180 Days)</option>
                            <option value="12">1 Year</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">2. Principal Loan Amount</label>
                        <div class="input-group shadow-sm rounded-3 overflow-hidden border border-success border-opacity-50">
                            <span class="input-group-text bg-white border-0 fw-bold text-success fs-5 px-4">₱</span>
                            <input type="number" name="principal" id="principal" class="form-control border-0 shadow-none fw-bold text-success fs-3 py-2" required step="0.01" oninput="updateNet()" placeholder="0.00">
                        </div>
                    </div>

                    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase ls-1" style="font-size: 0.7rem;">Net Cash to Release</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.65rem;">No Deductions</span>
                        </div>
                        <div class="amount-display text-center fw-bold text-dark mt-2 mb-2">
                            ₱<span id="net_cash">0.00</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-4 shadow-sm border p-0 mb-5 overflow-hidden">
                        <div class="list-group list-group-flush small">
                            <div class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted"><i class="fa-regular fa-calendar text-secondary me-2"></i> Date Pawned</span>
                                <span class="fw-bold text-dark"><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted"><i class="fa-solid fa-clock text-primary me-2"></i> Maturity Date</span>
                                <span id="maturity_display" class="fw-bold text-primary">---</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between py-3 bg-danger bg-opacity-10">
                                <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Foreclosure Date</span>
                                <span id="expiry_display" class="fw-bold text-danger">---</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="btn_save_pawn" class="btn btn-primary w-100 btn-lg rounded-pill shadow-sm fw-bold py-3 mt-auto">
                        <i class="fa-solid fa-shield-check me-2"></i> CONFIRM TRANSACTION
                    </button>
                </div>
                
            </div>
        </div>
    </form>
</div>

<script>
function updateNet() {
    let principal = parseFloat(document.getElementById('principal').value) || 0;
    document.getElementById('net_cash').innerText = principal.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateDates() {
    let months = parseInt(document.getElementById('term_months').value);
    let today = new Date();
    
    // Maturity
    let maturity = new Date(today);
    maturity.setMonth(today.getMonth() + months);
    
    // Expiry (30 days grace period)
    let expiry = new Date(maturity);
    expiry.setDate(maturity.getDate() + 30);

    let options = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('maturity_display').innerText = maturity.toLocaleDateString('en-US', options);
    document.getElementById('expiry_display').innerText = expiry.toLocaleDateString('en-US', options);
}

window.onload = function() {
    updateNet();
    updateDates();
};
</script>

<?php include_once '../../includes/teller_footer.php'; ?>