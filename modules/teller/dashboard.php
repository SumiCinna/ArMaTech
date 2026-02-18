<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teller', 'admin'])) {
    header("Location: ../../teller_login.php?error=Unauthorized Access");
    exit();
}

$teller_name = $_SESSION['full_name'];
$teller_id = $_SESSION['account_id'];

// 1. Get Transactions Today Count
$sql_count = "SELECT COUNT(*) as total FROM transactions WHERE DATE(date_pawned) = CURDATE()";
$count_today = $conn->query($sql_count)->fetch_assoc()['total'] ?? 0;

// 2. Get Total Pawn Value (Money Out) - Teller Specific
$sql_pawn = "SELECT SUM(i.appraised_value) as total FROM transactions t 
             JOIN items i ON t.transaction_id = i.transaction_id 
             WHERE t.teller_id = ? AND DATE(t.date_pawned) = CURDATE()";
$stmt = $conn->prepare($sql_pawn);
$stmt->bind_param("i", $teller_id);
$stmt->execute();
$total_pawn = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Get Total Payments (Money In) - Teller Specific
$sql_pay = "SELECT SUM(amount_paid) as total FROM payments WHERE teller_id = ? AND DATE(date_paid) = CURDATE()";
$stmt = $conn->prepare($sql_pay);
$stmt->bind_param("i", $teller_id);
$stmt->execute();
$total_pay = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Get Recent Transactions (Union of New Pawns and Payments)
$sql_recent = "
    (SELECT 
        t.pt_number, 
        CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
        'New Pawn' AS type,
        t.principal_amount AS amount,
        t.date_pawned AS date_time
    FROM transactions t
    JOIN profiles p ON t.customer_id = p.profile_id)
    
    UNION ALL
    
    (SELECT 
        t.pt_number,
        CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
        pay.payment_type AS type,
        pay.amount_paid AS amount,
        pay.date_paid AS date_time
    FROM payments pay
    JOIN transactions t ON pay.transaction_id = t.transaction_id
    JOIN profiles p ON t.customer_id = p.profile_id)
    
    ORDER BY date_time DESC 
    LIMIT 10";
$recent_txns = $conn->query($sql_recent);

include_once '../../includes/teller_header.php';
?>



<div class="container">
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase small fw-bold">Total Pawned (Out)</h6>
                    <div class="d-flex align-items-center">
                        <div class="display-6 fw-bold text-danger me-3">₱<?php echo number_format($total_pawn, 2); ?></div>
                        <div class="text-danger bg-danger-subtle rounded p-2"><i class="bi bi-dash-circle fs-4"></i></div>
                    </div>
                    <small class="text-muted">Principal released today</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase small fw-bold">Total Payments (In)</h6>
                    <div class="d-flex align-items-center">
                        <div class="display-6 fw-bold text-success me-3">₱<?php echo number_format($total_pay, 2); ?></div>
                        <div class="text-success bg-success-subtle rounded p-2"><i class="bi bi-plus-circle fs-4"></i></div>
                    </div>
                    <small class="text-muted">Collections processed today</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase small fw-bold">Transactions Today</h6>
                    <div class="d-flex align-items-center">
                        <div class="display-6 fw-bold text-dark me-3"><?php echo $count_today; ?></div>
                        <div class="text-primary bg-primary-subtle rounded p-2"><i class="bi bi-receipt fs-4"></i></div>
                    </div>
                    <small class="text-muted">Total activity count</small>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                <div>
                    <strong class="text-dark">System Notice:</strong> 
                    Reminder to check ID expiration dates for all high-value transactions.
                </div>
    </div>

    <h5 class="mb-3 text-secondary fw-bold">Quick Operations</h5>
    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <a href="new_pawn.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-primary"><i class="bi bi-gem display-4"></i></div>
                        <h4 class="fw-bold text-dark">New Pawn</h4>
                        <p class="text-muted small">Create a new loan transaction for gadgets.</p>
                        <span class="btn btn-outline-primary btn-sm rounded-pill px-4">Start <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="redeem.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-success"><i class="bi bi-cash-coin display-4"></i></div>
                        <h4 class="fw-bold text-dark">Redeem / Renew</h4>
                        <p class="text-muted small">Process payments, renewals, and item claims.</p>
                        <span class="btn btn-outline-success btn-sm rounded-pill px-4">Process <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="search.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-secondary"><i class="bi bi-search display-4"></i></div>
                        <h4 class="fw-bold text-dark">Search Records</h4>
                        <p class="text-muted small">Find customers, items, or transaction history.</p>
                        <span class="btn btn-outline-secondary btn-sm rounded-pill px-4">Search <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Transactions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th>PT Number</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_txns && $recent_txns->num_rows > 0): ?>
                        <?php while($row = $recent_txns->fetch_assoc()): ?>
                            <?php
                                // Determine Display Logic
                                $badge_class = "bg-secondary";
                                $type_label = $row['type'];
                                $amount_class = "text-dark";
                                $amount_prefix = "₱";

                                switch($row['type']) {
                                    case 'New Pawn':
                                        $badge_class = "bg-primary";
                                        $type_label = "New Pawn";
                                        $amount_class = "text-danger"; // Money Out
                                        $amount_prefix = "- ₱";
                                        break;
                                    case 'interest_only':
                                        $badge_class = "bg-info text-dark";
                                        $type_label = "Renewal";
                                        $amount_class = "text-success"; // Money In
                                        $amount_prefix = "+ ₱";
                                        break;
                                    case 'partial_payment':
                                        $badge_class = "bg-warning text-dark";
                                        $type_label = "Partial Pay";
                                        $amount_class = "text-success";
                                        $amount_prefix = "+ ₱";
                                        break;
                                    case 'full_redemption':
                                        $badge_class = "bg-success";
                                        $type_label = "Redemption";
                                        $amount_class = "text-success";
                                        $amount_prefix = "+ ₱";
                                        break;
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['pt_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $type_label; ?></span></td>
                                <td class="<?php echo $amount_class; ?> fw-bold"><?php echo $amount_prefix . number_format($row['amount'], 2); ?></td>
                                <td><?php echo date('M d, h:i A', strtotime($row['date_time'])); ?></td>
                                <td><i class="bi bi-check-circle-fill text-success"></i></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No recent transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    /* Specific CSS for Dashboard Hover Effects */
    .transition-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .transition-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>