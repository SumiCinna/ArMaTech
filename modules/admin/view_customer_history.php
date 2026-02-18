<?php
// modules/admin/view_customer_history.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. SECURITY: Get ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_customers.php");
    exit();
}

$account_id = intval($_GET['id']);

// 2. FETCH CUSTOMER PROFILE
$sql_cust = "SELECT p.*, a.username, a.status, 
             CONCAT(ad.house_no_street, ', ', ad.barangay, ', ', ad.city, ', ', ad.province) AS full_address 
             FROM accounts a
             JOIN profiles p ON a.profile_id = p.profile_id
             LEFT JOIN addresses ad ON p.profile_id = ad.profile_id
             WHERE a.account_id = ?";
$stmt = $conn->prepare($sql_cust);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Customer not found.</div></div>";
    include_once '../../includes/admin_footer.php';
    exit();
}

// 3. FETCH TRANSACTIONS
$sql_trans = "SELECT t.*, i.device_type, i.brand, i.model 
              FROM transactions t
              JOIN items i ON t.transaction_id = i.transaction_id
              WHERE t.customer_id = ? 
              ORDER BY t.date_pawned DESC";
// Note: transactions table uses profile_id (customer_id), so we need that ID, not account_id
$customer_profile_id = $customer['profile_id'];

$stmt_t = $conn->prepare($sql_trans);
$stmt_t->bind_param("i", $customer_profile_id);
$stmt_t->execute();
$transactions = $stmt_t->get_result();

// Calculate Stats
$total_pawns = $transactions->num_rows;
$active_loans = 0;
$total_principal = 0;

// We need to loop twice, so let's fetch all into an array first
$trans_data = [];
while ($row = $transactions->fetch_assoc()) {
    $trans_data[] = $row;
    if ($row['status'] == 'active') {
        $active_loans++;
        $total_principal += $row['principal_amount'];
    }
}
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Customer History</h3>
            <small class="text-muted">Viewing records for <span class="fw-bold text-primary"><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></span></small>
        </div>
        <a href="manage_customers.php" class="btn btn-outline-secondary btn-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <div class="row">
        
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2em; font-weight:bold;">
                            <?php echo substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1); ?>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></h5>
                    <p class="text-muted small mb-3">@<?php echo $customer['username']; ?></p>
                    
                    <span class="badge <?php echo ($customer['status'] == 'active') ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 text-<?php echo ($customer['status'] == 'active') ? 'success' : 'danger'; ?> px-3 py-2 rounded-pill mb-3">
                        <?php echo strtoupper($customer['status']); ?> ACCOUNT
                    </span>

                    <hr class="opacity-10 my-3">
                    
                    <div class="text-start small">
                        <p class="mb-2"><i class="fa-solid fa-id-card me-2 text-muted"></i> <?php echo $customer['public_id']; ?></p>
                        <p class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i> <?php echo $customer['contact_number']; ?></p>
                        <p class="mb-2"><i class="fa-solid fa-envelope me-2 text-muted"></i> <?php echo $customer['email']; ?></p>
                        <p class="mb-0"><i class="fa-solid fa-map-pin me-2 text-muted"></i> <?php echo $customer['full_address']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary">
                        <small class="text-muted text-uppercase fw-bold">Total Transactions</small>
                        <h2 class="fw-bold mb-0"><?php echo $total_pawns; ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 border-start border-4 border-success">
                        <small class="text-muted text-uppercase fw-bold">Active Loans</small>
                        <h2 class="fw-bold mb-0"><?php echo $active_loans; ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning">
                        <small class="text-muted text-uppercase fw-bold">Current Principal</small>
                        <h2 class="fw-bold mb-0">₱<?php echo number_format($total_principal, 2); ?></h2>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Transaction History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>PT Number</th>
                                    <th>Item Details</th>
                                    <th>Principal</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($trans_data) > 0): ?>
                                    <?php foreach($trans_data as $row): ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap">
                                                <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo $row['pt_number']; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark"><?php echo $row['brand'] . ' ' . $row['model']; ?></span>
                                                    <small class="text-muted"><?php echo $row['device_type']; ?></small>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-dark">₱<?php echo number_format($row['principal_amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                    $status_color = 'secondary';
                                                    if ($row['status'] == 'active') $status_color = 'success';
                                                    if ($row['status'] == 'redeemed') $status_color = 'primary';
                                                    if ($row['status'] == 'expired') $status_color = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> rounded-pill px-3">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_transaction_details.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-light text-primary border" title="View Full Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            No transaction records found for this customer.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>