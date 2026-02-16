<?php
// modules/teller/pawn_item_entry.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

// Security Check & Setup
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

<div class="container mt-4">
    
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success shadow-sm">
            <h5 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Account Created!</h5>
            <p class="mb-0"><?php echo urldecode($_GET['msg']); ?></p>
        </div>
    <?php endif; ?>

    <form action="../../core/process_transaction.php" method="POST">
        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
        <input type="hidden" name="teller_id" value="<?php echo $teller_id; ?>">
        <input type="hidden" name="pt_number" value="<?php echo $pt_number; ?>">

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-phone"></i> Gadget Information</h5>
                    </div>
                    <div class="card-body">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Device Type</label>
                                <select name="device_type" class="form-select" required>
                                    <option value="Smartphone">Smartphone</option>
                                    <option value="Laptop">Laptop</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Smartwatch">Smartwatch</option>
                                    <option value="Gaming Console">Gaming Console</option>
                                    <option value="Camera">Camera</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Brand</label>
                                <input type="text" name="brand" class="form-control" list="brands" placeholder="e.g. Samsung" required>
                                <datalist id="brands">
                                    <option value="Apple"><option value="Samsung"><option value="Xiaomi">
                                    <option value="Oppo"><option value="Vivo"><option value="Lenovo">
                                    <option value="Asus"><option value="HP"><option value="Dell">
                                </datalist>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Model Name/Number</label>
                                <input type="text" name="model" class="form-control" placeholder="e.g. iPhone 13 Pro Max" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Serial / IMEI</label>
                                <input type="text" name="serial_number" class="form-control font-monospace" placeholder="Critical for Security" required>
                                <div class="form-text text-danger small">Must be unique per item.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Storage</label>
                                <select name="storage" class="form-select">
                                    <option value="64GB">64GB</option>
                                    <option value="128GB">128GB</option>
                                    <option value="256GB">256GB</option>
                                    <option value="512GB">512GB</option>
                                    <option value="1TB">1TB</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">RAM</label>
                                <select name="ram" class="form-select">
                                    <option value="4GB">4GB</option>
                                    <option value="8GB">8GB</option>
                                    <option value="12GB">12GB</option>
                                    <option value="16GB">16GB</option>
                                    <option value="32GB">32GB</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Color</label>
                                <input type="text" name="color" class="form-control" placeholder="e.g. Space Gray">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Inclusions (What comes with it?)</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="inclusions[]" value="Unit Only">
                                <label class="form-check-label">Unit Only</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="inclusions[]" value="Original Box">
                                <label class="form-check-label">Original Box</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="inclusions[]" value="Original Charger">
                                <label class="form-check-label">Orig. Charger</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="inclusions[]" value="Receipt">
                                <label class="form-check-label">Store Receipt</label>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold">Physical Condition / Issues</label>
                            <textarea name="condition_notes" class="form-control" rows="2" placeholder="Describe scratches, dents, battery health, or hidden issues..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-lg border-primary">
                    <div class="card-header bg-dark text-white text-center">
                        <small>Pawn Ticket No.</small>
                        <h3 class="text-warning mb-0"><?php echo $pt_number; ?></h3>
                    </div>
                    <div class="card-body bg-light">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contract Term</label>
                            <select name="term_months" id="term_months" class="form-select form-select-lg" onchange="updateDates()">
                                <option value="1">1 Month</option>
                                <option value="2">2 Months</option>
                                <option value="3">3 Months</option>
                                <option value="4">4 Months</option>
                                <option value="5">5 Months</option>
                                <option value="6">6 Months</option>
                                <option value="12">1 Year (12 Months)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Principal Amount (₱)</label>
                            <input type="number" name="principal" id="principal" class="form-control fw-bold fs-4 text-success" required step="0.01" oninput="updateNet()">
                        </div>

                        <div class="alert alert-success text-center">
                            <small class="text-uppercase">Net Cash to Release</small>
                            <h2 class="fw-bold mb-0">₱<span id="net_cash">0.00</span></h2>
                            <small class="text-muted">(No Service Fee)</small>
                        </div>

                        <div class="alert alert-warning small">
                             <div class="d-flex justify-content-between">
                                <span>Date Pawned:</span>
                                <strong><?php echo date('M d, Y h:i A'); ?></strong>
                            </div>
                             <div class="d-flex justify-content-between mt-1">
                                <span>Maturity Date:</span>
                                <strong id="maturity_display">---</strong>
                            </div>
                            <div class="d-flex justify-content-between text-danger mt-1">
                                <span>Expiry Date:</span>
                                <strong id="expiry_display">---</strong>
                            </div>
                        </div>

                        <button type="submit" name="btn_save_pawn" class="btn btn-primary w-100 btn-lg fw-bold">
                            CONFIRM TRANSACTION
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateNet() {
    let principal = parseFloat(document.getElementById('principal').value) || 0;
    // Since there is no service fee, Net Cash = Principal
    document.getElementById('net_cash').innerText = principal.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function updateDates() {
    let months = parseInt(document.getElementById('term_months').value);
    let today = new Date();
    
    // Maturity
    let maturity = new Date(today);
    maturity.setMonth(today.getMonth() + months);
    
    // Expiry
    let expiry = new Date(maturity);
    expiry.setDate(maturity.getDate() + 30);

    let options = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('maturity_display').innerText = maturity.toLocaleDateString('en-US', options);
    document.getElementById('expiry_display').innerText = expiry.toLocaleDateString('en-US', options);
}
// Run on load
window.onload = function() {
    updateNet();
    updateDates();
};
</script>

<?php include_once '../../includes/teller_footer.php'; ?>