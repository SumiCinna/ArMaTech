<?php
// modules/admin/verify_reservations.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Filter Logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';

$status_condition = "sr.status = 'pending'"; // Default
if ($filter == 'approved') {
    $status_condition = "sr.status = 'approved'";
} elseif ($filter == 'rejected') {
    $status_condition = "sr.status = 'rejected'";
} elseif ($filter == 'all') {
    $status_condition = "1=1"; // Shows everything
}

// 1. Get Stats for the Tabs
$stats_sql = "SELECT status, COUNT(*) as count FROM shop_reservations GROUP BY status";
$stats_res = $conn->query($stats_sql);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];

if ($stats_res) {
    while ($row = $stats_res->fetch_assoc()) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = $row['count'];
        }
        $counts['all'] += $row['count'];
    }
}

// 2. Fetch Filtered Reservations
$sql = "SELECT sr.*, 
               p.first_name, p.last_name, p.contact_number, 
               si.selling_price, 
               i.device_type AS item_name, i.brand, i.model 
        FROM shop_reservations sr
        JOIN shop_items si ON sr.shop_id = si.shop_id
        JOIN items i ON si.item_id = i.item_id
        JOIN profiles p ON sr.customer_profile_id = p.profile_id
        WHERE $status_condition
        ORDER BY sr.created_at DESC";

$result = $conn->query($sql);
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Receipt Verification</h3>
            <p class="text-muted small mb-0">Review proof of payment submitted by customers for online shop reservations.</p>
        </div>
        <a href="shop_management.php" class="btn btn-light border shadow-sm fw-bold text-dark px-3 rounded-pill">
            <i class="fa-solid fa-store me-2"></i> Storefront
        </a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex mb-4 gap-2 border-bottom pb-3 overflow-auto custom-scrollbar">
        <a href="?filter=pending" class="btn rounded-pill px-4 fw-bold <?php echo $filter == 'pending' ? 'btn-primary shadow' : 'btn-light border text-muted'; ?>">
            Pending Review 
            <?php if($counts['pending'] > 0): ?>
                <span class="badge bg-white text-primary rounded-circle ms-2 px-2 py-1"><?php echo $counts['pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?filter=approved" class="btn rounded-pill px-4 fw-bold <?php echo $filter == 'approved' ? 'btn-success shadow' : 'btn-light border text-muted'; ?>">
            Approved <span class="badge bg-secondary bg-opacity-25 text-dark rounded-circle ms-2 px-2 py-1"><?php echo $counts['approved']; ?></span>
        </a>
        <a href="?filter=rejected" class="btn rounded-pill px-4 fw-bold <?php echo $filter == 'rejected' ? 'btn-danger shadow' : 'btn-light border text-muted'; ?>">
            Rejected <span class="badge bg-secondary bg-opacity-25 text-dark rounded-circle ms-2 px-2 py-1"><?php echo $counts['rejected']; ?></span>
        </a>
        <a href="?filter=all" class="btn rounded-pill px-4 fw-bold ms-auto <?php echo $filter == 'all' ? 'btn-dark shadow' : 'btn-light border text-muted'; ?>">
            View All History
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Date Submitted</th>
                            <th class="py-3 fw-bold">Customer</th>
                            <th class="py-3 fw-bold">Reserved Item</th>
                            <th class="py-3 fw-bold">Financials</th>
                            <th class="py-3 fw-bold">Status</th>
                            <th class="text-end pe-4 py-3 fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3 border">
                                                <span class="d-block fw-bold text-dark lh-1" style="font-size: 1.1rem;"><?php echo date('d', strtotime($row['created_at'])); ?></span>
                                                <span class="d-block text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo date('M', strtotime($row['created_at'])); ?></span>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;"><?php echo date('Y', strtotime($row['created_at'])); ?></span>
                                                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3">
                                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center">
                                            <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                                        </h6>
                                        <small class="text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i> <?php echo $row['contact_number']; ?></small>
                                    </td>

                                    <td class="py-3">
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;"><?php echo $row['item_name']; ?></small>
                                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem;"><?php echo $row['brand'] . ' ' . $row['model']; ?></h6>
                                    </td>

                                    <td class="py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-success" style="font-size: 0.9rem;">DP: ₱<?php echo number_format($row['reservation_amount'], 2); ?></span>
                                            <small class="text-muted" style="font-size: 0.7rem;">Price: ₱<?php echo number_format($row['selling_price'], 2); ?></small>
                                        </div>
                                    </td>

                                    <td class="py-3">
                                        <?php 
                                            $s = $row['status'];
                                            if($s == 'pending') echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Pending</span>';
                                            elseif($s == 'approved') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> Approved</span>';
                                            elseif($s == 'rejected') echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-xmark me-1"></i> Rejected</span>';
                                            else echo '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill">' . ucfirst($s) . '</span>';
                                        ?>
                                    </td>

                                    <td class="text-end pe-4 py-3">
                                        <button class="btn btn-sm btn-dark rounded-pill px-3 fw-bold shadow-sm btn-hover-lift" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $row['reservation_id']; ?>">
                                            <?php echo ($s == 'pending') ? 'Review Receipt' : 'View Details'; ?>
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="receiptModal<?php echo $row['reservation_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                            <div class="row g-0">
                                                <div class="col-md-6 bg-dark d-flex align-items-center justify-content-center p-4 position-relative">
                                                    <img src="../../assets/receipts/<?php echo htmlspecialchars($row['receipt_image']); ?>" class="img-fluid rounded shadow" alt="Proof of Payment" style="max-height: 500px; object-fit: contain;">
                                                </div>
                                                
                                                <div class="col-md-6 bg-white d-flex flex-column">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark">Transaction Review</h5>
                                                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    
                                                    <div class="modal-body">
                                                        <div class="mb-4">
                                                            <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.7rem;">Reference Number</small>
                                                            <div class="bg-light p-2 rounded border mt-1 font-monospace fs-5 text-center fw-bold text-dark tracking-wide">
                                                                <?php echo htmlspecialchars($row['reference_number']); ?>
                                                            </div>
                                                        </div>

                                                        <ul class="list-group list-group-flush mb-4">
                                                            <li class="list-group-item px-0 d-flex justify-content-between">
                                                                <span class="text-muted small">Customer</span>
                                                                <span class="fw-bold small"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span>
                                                            </li>
                                                            <li class="list-group-item px-0 d-flex justify-content-between">
                                                                <span class="text-muted small">Reserved Item</span>
                                                                <span class="fw-bold small"><?php echo $row['brand'] . ' ' . $row['model']; ?></span>
                                                            </li>
                                                            <li class="list-group-item px-0 d-flex justify-content-between">
                                                                <span class="text-muted small">Required Downpayment</span>
                                                                <span class="fw-bold text-primary small">₱<?php echo number_format($row['reservation_amount'], 2); ?></span>
                                                            </li>
                                                        </ul>

                                                        <?php if($row['status'] == 'pending'): ?>
                                                            <div class="alert alert-warning border-0 small bg-warning bg-opacity-10 d-flex align-items-center mb-0">
                                                                <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-warning"></i>
                                                                <div>
                                                                    <strong>Admin Action Required</strong><br>
                                                                    Verify that the reference number matches your GCash/Bank records before approving.
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-center mt-auto">
                                                                <p class="text-muted mb-0 small">This reservation has already been <strong class="text-dark"><?php echo strtoupper($row['status']); ?></strong>.</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="modal-footer border-0 bg-light p-3 mt-auto">
                                                        <?php if($row['status'] == 'pending'): ?>
                                                            <form action="../../core/process_verify_reservation.php" method="POST" class="w-100 d-flex gap-2 m-0">
                                                                <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                                <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                                
                                                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger fw-bold rounded-3 w-50">Reject</button>
                                                                <button type="submit" name="action" value="approve" class="btn btn-success fw-bold rounded-3 w-50 shadow-sm"><i class="fa-solid fa-check me-1"></i> Approve</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-light border fw-bold w-100" data-bs-dismiss="modal">Close</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-inbox fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Reservations Found</h5>
                                        <p class="text-muted small">There are currently no "<?php echo ucfirst($filter); ?>" reservations.</p>
                                        <?php if($filter != 'pending'): ?>
                                            <a href="?filter=pending" class="btn btn-sm btn-outline-primary rounded-pill px-4 mt-2">View Pending</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .btn-hover-lift:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    .custom-table tbody tr { transition: background-color 0.2s; }
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    
    /* Horizontal Scrollbar for Tabs on mobile */
    .custom-scrollbar::-webkit-scrollbar { height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }
    
    /* Tracking class for reference numbers */
    .tracking-wide { letter-spacing: 2px; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>