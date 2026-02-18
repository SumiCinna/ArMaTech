<?php
// modules/admin/view_transaction_details.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. SECURITY: Get Transaction ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trans_id = intval($_GET['id']);

// 2. FETCH TRANSACTION, ITEM & CUSTOMER DETAILS
// We join transactions, items, and profiles (customer)
$sql = "SELECT t.*, 
               i.device_type, i.brand, i.model, i.serial_number, i.inclusions, i.condition_notes,
               p.first_name, p.last_name, p.contact_number, p.email, p.public_id as cust_public_id,
               a.username as processed_by_user
        FROM transactions t
        JOIN items i ON t.transaction_id = i.transaction_id
        JOIN profiles p ON t.customer_id = p.profile_id
        LEFT JOIN accounts a ON t.teller_id = a.account_id
        WHERE t.transaction_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();

if (!$t) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Transaction not found.</div></div>";
    include_once '../../includes/admin_footer.php';
    exit();
}

// 3. FETCH PAYMENT HISTORY (With Teller Name)
$sql_pay = "SELECT py.*, a.username as teller_name 
            FROM payments py
            LEFT JOIN accounts a ON py.teller_id = a.account_id
            WHERE py.transaction_id = ? 
            ORDER BY py.date_paid DESC";
$stmt_p = $conn->prepare($sql_pay);
$stmt_p->bind_param("i", $trans_id);
$stmt_p->execute();
$payments = $stmt_p->get_result();

// Status Badge Logic
$status_color = 'secondary';
if ($t['status'] == 'active') $status_color = 'success';
if ($t['status'] == 'redeemed') $status_color = 'primary';
if ($t['status'] == 'expired') $status_color = 'danger';
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">Loan Details</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_customers.php" class="text-decoration-none">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transaction #<?php echo $t['pt_number']; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <button class="btn btn-light border shadow-sm btn-sm me-2 fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-2"></i> Print Record</button>
            <a href="javascript:history.back()" class="btn btn-dark btn-sm fw-bold shadow-sm"><i class="fa-solid fa-arrow-left me-2"></i> Back</a>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8">
            
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-laptop me-2"></i> Item Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Item Name</small>
                            <h5 class="fw-bold text-dark mb-3"><?php echo $t['device_type']; ?></h5>
                            
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Brand & Model</small>
                            <p class="mb-3"><?php echo $t['brand'] . ' ' . $t['model']; ?></p>

                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Serial Number</small>
                            <p class="mb-0 font-monospace bg-light d-inline-block px-2 rounded"><?php echo $t['serial_number'] ?: 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Description / Inclusions</small>
                            <p class="mb-3 small text-muted"><?php echo nl2br($t['inclusions']); ?></p>
                            
                            <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning border-opacity-25">
                                <small class="text-warning text-uppercase fw-bold" style="font-size:0.7rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Condition Notes</small>
                                <p class="mb-0 small text-dark fst-italic"><?php echo $t['condition_notes'] ?: 'No specific issues noted.'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-list-check me-2"></i> Transaction History</h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">Audit Trail</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Date & Time</th>
                                    <th>Transaction Type</th>
                                    <th>Processed By</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments->num_rows > 0): ?>
                                    <?php while($pay = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small">
                                                <div class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($pay['date_paid'])); ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($pay['date_paid'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if($pay['payment_type'] == 'interest_only'): ?>
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3">Interest Payment</span>
                                                <?php elseif($pay['payment_type'] == 'redeem'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3">Full Redemption</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3">Payment</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-uppercase fw-bold text-secondary">
                                                    <i class="fa-solid fa-user-tie me-1"></i> <?php echo $pay['teller_name'] ?? 'System'; ?>
                                                </small>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-dark">
                                                ₱<?php echo number_format($pay['amount_paid'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No payment history found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="card shadow-sm border-0 mb-4 bg-gradient bg-<?php echo $status_color; ?> text-white">
                <div class="card-body p-4 text-center">
                    <small class="text-uppercase opacity-75 fw-bold ls-1">Current Status</small>
                    <h2 class="fw-bold mb-0 text-uppercase mt-2"><i class="fa-solid fa-circle-info me-2 opacity-50"></i><?php echo $t['status']; ?></h2>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-wallet me-2"></i> Financial Details</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush financial-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Principal Amount</span>
                            <span class="fw-bold fs-5 text-dark">₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Interest Rate</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success">3% / Month</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Date Pawned</span>
                            <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Maturity Date</span>
                            <span class="fw-bold text-danger"><?php echo date('M d, Y', strtotime($t['maturity_date'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user me-2"></i> Customer Details</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                         <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-weight:bold; font-size: 1.5rem;">
                            <?php echo substr($t['first_name'], 0, 1) . substr($t['last_name'], 0, 1); ?>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></h5>
                    <p class="text-muted small mb-4"><span class="badge bg-light text-dark border">ID: <?php echo $t['cust_public_id']; ?></span></p>
                    
                    <a href="tel:<?php echo $t['contact_number']; ?>" class="btn btn-light border shadow-sm btn-sm w-100 mb-2 fw-bold text-dark">
                        <i class="fa-solid fa-phone me-2"></i> <?php echo $t['contact_number']; ?>
                    </a>
                    <a href="mailto:<?php echo $t['email']; ?>" class="btn btn-light border shadow-sm btn-sm w-100 fw-bold text-dark">
                        <i class="fa-solid fa-envelope me-2"></i> Email Customer
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Financial Details Hover Effect */
    .financial-list .list-group-item {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .financial-list .list-group-item:hover {
        background-color: #f0f7ff; /* Soft Blue */
        border-left-color: #0d6efd; /* Accent Color */
        padding-left: 1rem !important; /* Slide Effect */
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    /* Table Hover Effect */
    .table-hover tbody tr {
        transition: all 0.2s ease-in-out;
    }
    .table-hover tbody tr:hover {
        background-color: #f0f7ff !important;
        transform: scale(1.005);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        position: relative;
        z-index: 1;
    }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>