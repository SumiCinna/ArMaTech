<?php
// modules/teller/redeem_process.php
session_start();
require_once '../../config/database.php';
require_once '../../core/functions.php'; 
include_once '../../includes/teller_header.php';

if (!isset($_GET['id'])) header("Location: redeem.php");

$id = $_GET['id'];

// 1. Fetch Data (Added last_renewed_date to the SELECT query)
$sql = "SELECT t.*, t.last_renewed_date, p.first_name, p.last_name, p.public_id, i.brand, i.model, i.condition_notes 
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        JOIN items i ON t.transaction_id = i.transaction_id
        WHERE t.transaction_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();

if (!$t) die("Transaction not found.");

// 2. Calculate Values (Now passing the last_renewed_date!)
$calc = calculatePawnInterest($t['principal_amount'], $t['last_renewed_date']);
$principal      = $t['principal_amount'];
$interest_due   = $calc['interest'];
$total_full_pay = $calc['total']; 
?>

<style>
    /* The Left Card (Credit Card Look) */
    .banking-card {
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        color: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    /* Decorative Circle */
    .banking-card::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }

    /* The Right Column Action Buttons (Radio Cards) */
    .selection-label {
        display: block;
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 10px;
    }
    
    .selection-label:hover {
        background: #f8f9fa;
        border-color: #ced4da;
    }

    /* Active State Logic */
    .btn-check:checked + .selection-label {
        border-color: #0d6efd; /* Blue Border */
        background-color: #f0f7ff; /* Light Blue BG */
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    }
    
    .btn-check:checked + .selection-label .icon-box {
        background-color: #0d6efd;
        color: white;
    }

    /* Icon Styling inside buttons */
    .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #e9ecef;
        color: #495057;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.2s;
    }

    /* Form Switching Animation */
    .payment-form { display: none; }
    .payment-form.active { display: block; animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Input Styling */
    .form-control-lg { border-radius: 10px; padding: 15px; font-weight: bold; }
    .input-group-text { border-radius: 10px 0 0 10px; background: #f8f9fa; }
</style>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Transaction Overview</h3>
            <span class="text-muted">Process payment for PT# <?php echo $t['pt_number']; ?></span>
        </div>
        <a href="redeem.php" class="btn btn-light border shadow-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Search
        </a>
    </div>

    <div class="row">
        
        <div class="col-lg-5 mb-4">
            <div class="banking-card p-4 h-100 d-flex flex-column justify-content-between">
                
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <small class="text-white-50 text-uppercase ls-1 fw-bold">Pawned Item</small>
                            <h3 class="fw-bold mb-0"><?php echo $t['brand']; ?></h3>
                            <span class="fs-5 opacity-75"><?php echo $t['model']; ?></span>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded p-2">
                            <i class="fa-solid fa-mobile-screen fa-2x"></i>
                        </div>
                    </div>

                    <div class="bg-black bg-opacity-25 rounded p-3 mb-4 border border-white border-opacity-10">
                        <label class="text-warning small fw-bold text-uppercase mb-1"><i class="fa-solid fa-note-sticky me-1"></i> Condition Notes</label>
                        <p class="mb-0 small fst-italic text-white-50">
                            "<?php echo empty($t['condition_notes']) ? 'No remarks.' : nl2br($t['condition_notes']); ?>"
                        </p>
                    </div>

                    <!-- Interest Breakdown -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-white-50 text-uppercase fw-bold ls-1">Interest Breakdown (3%)</small>
                            <span class="badge bg-white bg-opacity-25 border border-white border-opacity-25 text-white"><?php echo $calc['months']; ?> Months</span>
                        </div>
                        <div class="rounded-3 overflow-hidden border border-white border-opacity-25" style="background-color: rgba(0, 0, 0, 0.4);">
                            <div style="max-height: 160px; overflow-y: auto;">
                                <div class="d-flex flex-column w-100">
                                    <?php 
                                        $breakdown_date = new DateTime($t['last_renewed_date']);
                                        $monthly_amt = $principal * 0.03;
                                        for($m = 1; $m <= $calc['months']; $m++):
                                            $p_start = $breakdown_date->format('M d, Y');
                                            $breakdown_date->modify('+30 days');
                                            $p_end = $breakdown_date->format('M d, Y');
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-white border-opacity-10">
                                        <div class="fw-medium" style="color: #f8fafc; font-size: 0.85em;">
                                            <i class="fa-regular fa-calendar-days me-2" style="color: #94a3b8;"></i><?php echo $p_start . ' to ' . $p_end; ?>
                                        </div>
                                        <div class="fw-bold" style="color: #fbbf24; font-size: 0.9em;">
                                            + ₱<?php echo number_format($monthly_amt, 2); ?>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="p-3 bg-black bg-opacity-50">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-white-50 small">Total Interest</span>
                                    <span class="text-warning fw-bold small">₱<?php echo number_format($interest_due, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white-50 small">Principal Amount</span>
                                    <span class="text-success fw-bold small">₱<?php echo number_format($principal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-white border-opacity-10">
                                    <span class="text-white fw-bold text-uppercase" style="font-size: 0.85em;">Total Redemption</span>
                                    <span class="text-white fw-bold">₱<?php echo number_format($total_full_pay, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-white-50 d-block">Owner</small>
                            <span class="fw-bold"><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-white-50 d-block">Date Pawned</small>
                            <span class="fw-bold"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </div>
                    </div>

                    <div class="bg-white text-dark rounded-3 p-3 d-flex justify-content-between align-items-center shadow-sm">
                        <span class="small text-uppercase fw-bold text-muted">Principal Amount</span>
                        <span class="fs-3 fw-bold text-success">₱<?php echo number_format($principal, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-wallet me-2 text-primary"></i> Select Payment Mode</h5>

                    <div class="mb-4">
                        
                        <input type="radio" class="btn-check" name="pay_mode" id="opt_renew" checked onclick="switchForm('renew')">
                        <label class="selection-label d-flex align-items-center" for="opt_renew">
                            <div class="icon-box me-3"><i class="fa-solid fa-rotate"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark">Renewal</h6>
                                <small class="text-muted">Pay interest only (Extend 30 Days)</small>
                            </div>
                            <span class="fw-bold text-primary">₱<?php echo number_format($interest_due, 2); ?></span>
                        </label>

                        <input type="radio" class="btn-check" name="pay_mode" id="opt_partial" onclick="switchForm('partial')">
                        <label class="selection-label d-flex align-items-center" for="opt_partial">
                            <div class="icon-box me-3"><i class="fa-solid fa-chart-pie"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark">Partial Payment</h6>
                                <small class="text-muted">Pay Principal + Interest</small>
                            </div>
                            <span class="badge bg-warning text-dark">Flexible</span>
                        </label>

                        <input type="radio" class="btn-check" name="pay_mode" id="opt_full" onclick="switchForm('full')">
                        <label class="selection-label d-flex align-items-center" for="opt_full">
                            <div class="icon-box me-3"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark">Full Redemption</h6>
                                <small class="text-muted">Close transaction & release item</small>
                            </div>
                            <span class="fw-bold text-success">₱<?php echo number_format($total_full_pay, 2); ?></span>
                        </label>

                    </div>

                    <hr class="text-muted opacity-25 mb-4">

                    <div id="form_renew" class="payment-form active">
                        <form action="../../core/process_payment.php" method="POST">
                            <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="payment_type" value="interest_only">
                            <input type="hidden" name="amount_paid" value="<?php echo $interest_due; ?>">
                            
                            <div class="alert alert-primary border-0 d-flex align-items-center">
                                <i class="fa-solid fa-info-circle fa-lg me-3"></i>
                                <div>
                                    <strong>Confirm Renewal</strong><br>
                                    Only the interest of ₱<?php echo number_format($interest_due, 2); ?> will be collected.
                                </div>
                            </div>
                            <button type="submit" name="btn_process_payment" class="btn btn-primary w-100 btn-lg rounded-3 fw-bold shadow-sm">
                                Pay Interest & Extend
                            </button>
                        </form>
                    </div>

                    <div id="form_partial" class="payment-form">
                        <form action="../../core/process_payment.php" method="POST">
                            <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="payment_type" value="partial_payment">
                            <input type="hidden" name="current_principal" value="<?php echo $principal; ?>">
                            <input type="hidden" name="interest_amount" value="<?php echo $interest_due; ?>">
                            
                            <label class="form-label fw-bold text-muted small text-uppercase">Amount to Deduct from Principal</label>
                            <div class="input-group input-group-lg mb-3">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-minus text-danger"></i></span>
                                <input type="number" id="principal_deduction" name="principal_deduction" class="form-control" required min="100" max="<?php echo $principal - 100; ?>" placeholder="Enter amount" oninput="calculatePartialTotal()">
                            </div>
                            <div id="partial_error" class="text-danger small fw-bold mb-2" style="display:none;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Maximum deduction is ₱<?php echo number_format($principal - 100, 2); ?>. For full payment, use "Full Redemption".
                            </div>
                            
                            <div class="d-flex justify-content-between mb-1 text-muted small">
                                <span>+ Interest Due:</span>
                                <span>₱<?php echo number_format($interest_due, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-3 fw-bold text-dark border-top pt-2">
                                <span>Total to Pay:</span>
                                <span id="partial_total_display" class="text-success">₱<?php echo number_format($interest_due, 2); ?></span>
                            </div>

                            <button type="submit" id="btn_partial_submit" name="btn_process_payment" class="btn btn-warning w-100 btn-lg rounded-3 fw-bold text-dark shadow-sm">
                                Calculate & Pay
                            </button>
                        </form>
                    </div>

                    <div id="form_full" class="payment-form">
                        <form action="../../core/process_payment.php" method="POST">
                            <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="payment_type" value="full_redemption">
                            <input type="hidden" name="amount_paid" value="<?php echo $total_full_pay; ?>">
                            
                            <div class="text-center py-2">
                                <small class="text-uppercase fw-bold text-success ls-1">Total Payoff Amount</small>
                                <h1 class="display-3 fw-bold text-success mb-4">₱<?php echo number_format($total_full_pay, 2); ?></h1>
                            </div>

                            <button type="submit" name="btn_process_payment" class="btn btn-success w-100 btn-lg rounded-3 fw-bold shadow">
                                <i class="fa-solid fa-receipt me-2"></i> Confirm Payment & Release
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function switchForm(type) {
        // Hide all forms
        document.querySelectorAll('.payment-form').forEach(el => el.classList.remove('active'));
        // Show the selected one
        document.getElementById('form_' + type).classList.add('active');
    }

    function calculatePartialTotal() {
        let interest = <?php echo $interest_due; ?>;
        let maxDeduction = <?php echo $principal - 100; ?>;
        let input = document.getElementById('principal_deduction');
        let deduction = parseFloat(input.value);
        let errorDiv = document.getElementById('partial_error');
        let btn = document.getElementById('btn_partial_submit');

        if (isNaN(deduction)) deduction = 0;

        if (deduction > maxDeduction) {
            errorDiv.style.display = 'block';
            input.classList.add('is-invalid');
            btn.disabled = true;
        } else {
            errorDiv.style.display = 'none';
            input.classList.remove('is-invalid');
            btn.disabled = false;
        }

        let total = interest + deduction;
        document.getElementById('partial_total_display').innerText = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>

<?php include_once '../../includes/teller_footer.php'; ?>