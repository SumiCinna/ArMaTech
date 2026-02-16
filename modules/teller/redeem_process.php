<?php
// modules/teller/redeem_process.php
session_start();
require_once '../../config/database.php';
require_once '../../core/functions.php'; // Ensure calculatePawnInterest() is here
include_once '../../includes/teller_header.php';

if (!isset($_GET['id'])) header("Location: redeem.php");

$id = $_GET['id'];

// 1. Fetch Transaction & Item Details
// Using prepared statement for security
$sql = "SELECT t.*, p.first_name, p.last_name, p.public_id, i.brand, i.model, i.condition_notes 
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        JOIN items i ON t.transaction_id = i.transaction_id
        WHERE t.transaction_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$t = $result->fetch_assoc();

if (!$t) die("Transaction not found.");

// 2. Calculate Values
$calc = calculatePawnInterest($t['principal_amount'], $t['date_pawned']);

$principal      = $t['principal_amount'];
$interest_due   = $calc['interest'];
$total_full_pay = $calc['total']; // Principal + Interest
?>

<div class="container mt-4">
    <div class="row">
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">Item Details</div>
                <div class="card-body">
                    <h5 class="text-primary"><?php echo $t['brand'] . ' ' . $t['model']; ?></h5>
                    <p class="text-muted small"><?php echo nl2br($t['condition_notes']); ?></p>
                    <hr>
                    <small>Owner:</small>
                    <div class="fw-bold"><?php echo $t['first_name'] . " " . $t['last_name']; ?></div>
                    <div class="badge bg-dark"><?php echo $t['public_id']; ?></div>
                    <hr>
                    <small>Current Principal:</small>
                    <h3 class="text-success fw-bold">₱<?php echo number_format($principal, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Select Payment Type</h5>
                </div>
                <div class="card-body">
                    
                    <ul class="nav nav-tabs mb-4" id="paymentTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#renew">
                                🔄 Renew (Interest Only)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#partial">
                                📉 Partial Payment
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold text-success" data-bs-toggle="tab" data-bs-target="#full">
                                ✅ Full Redemption
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        
                        <div class="tab-pane fade show active" id="renew">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Pay only the interest to extend the maturity date.
                            </div>
                            <form action="../../core/process_payment.php" method="POST">
                                <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="payment_type" value="interest_only">
                                
                                <div class="mb-3 row">
                                    <label class="col-sm-5 col-form-label fw-bold">Interest Due:</label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control-plaintext fs-4 fw-bold text-primary" value="₱<?php echo number_format($interest_due, 2); ?>" readonly>
                                        <input type="hidden" name="amount_paid" value="<?php echo $interest_due; ?>">
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="btn_process_payment" class="btn btn-primary btn-lg">Pay Interest & Extend</button>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="partial">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Pay Interest + a portion of the Principal to reduce the loan.
                            </div>
                            <form action="../../core/process_payment.php" method="POST">
                                <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="payment_type" value="partial_payment">
                                <input type="hidden" name="current_principal" value="<?php echo $principal; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Interest Amount (Required)</label>
                                    <input type="number" class="form-control bg-light" name="interest_amount" value="<?php echo $interest_due; ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-success">Amount to Deduct from Principal</label>
                                    <input type="number" name="principal_deduction" class="form-control form-control-lg border-success" required min="100" max="<?php echo $principal - 100; ?>" placeholder="Enter amount...">
                                    <small class="text-muted">Max deduction: <?php echo number_format($principal - 100, 2); ?></small>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="btn_process_payment" class="btn btn-warning btn-lg fw-bold">Pay & Reduce Principal</button>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="full">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Pay full amount to claim the item.
                            </div>
                            <form action="../../core/process_payment.php" method="POST">
                                <input type="hidden" name="trans_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="payment_type" value="full_redemption">
                                <input type="hidden" name="amount_paid" value="<?php echo $total_full_pay; ?>">
                                
                                <div class="text-center mb-4">
                                    <small>Total Amount Due</small>
                                    <h1 class="text-success fw-bold display-4">₱<?php echo number_format($total_full_pay, 2); ?></h1>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="btn_process_payment" class="btn btn-success btn-lg fw-bold">Confirm Full Payment</button>
                                </div>
                            </form>
                        </div>

                    </div> </div>
            </div>
        </div>

    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>