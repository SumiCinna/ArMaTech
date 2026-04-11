<?php
// modules/admin/verify_reservations.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Filter Logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending_verification';

$status_condition = "sr.status = 'pending_verification'"; // Default
if ($filter === 'approved') {
    $status_condition = "sr.status = 'approved'";
} elseif ($filter === 'rejected') {
    $status_condition = "sr.status = 'rejected'";
} elseif ($filter === 'all') {
    $status_condition = "1=1";
}

// Stats
$stats_sql = "SELECT status, COUNT(*) as count FROM shop_reservations GROUP BY status";
$stats_res = $conn->query($stats_sql);
$counts    = ['pending_verification' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];

if ($stats_res) {
    while ($row = $stats_res->fetch_assoc()) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] += $row['count'];
        }
        $counts['all'] += $row['count'];
    }
}

// Fetch Reservations
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
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Reservation Management</h3>
            <p class="text-muted small mb-0">Review PayMongo-paid reservations and approve or reject them.</p>
        </div>
        <a href="shop_management.php" class="btn btn-light border shadow-sm fw-bold text-dark px-3 rounded-pill">
            <i class="fa-solid fa-store me-2"></i> Storefront
        </a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Info banner -->
    <div class="alert alert-primary border-0 shadow-sm rounded-3 d-flex align-items-start mb-4 bg-primary bg-opacity-10">
        <i class="fa-brands fa-cc-visa fs-3 me-3 text-primary mt-1"></i>
        <div class="small">
            <strong class="text-dark">Payments are collected automatically via PayMongo.</strong><br>
            Once a customer pays, their reservation moves to <strong>Pending Verification</strong> for your review. Approve or reject below.
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="d-flex mb-4 gap-2 border-bottom pb-3 overflow-auto custom-scrollbar">
        <a href="?filter=pending_verification" class="btn rounded-pill px-4 fw-bold <?php echo $filter === 'pending_verification' ? 'btn-warning shadow' : 'btn-light border text-muted'; ?>">
            <i class="fa-solid fa-magnifying-glass-dollar me-2"></i> Pending Verification
            <?php if ($counts['pending_verification'] > 0): ?>
                <span class="badge bg-white text-warning rounded-circle ms-2 px-2 py-1"><?php echo $counts['pending_verification']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?filter=approved" class="btn rounded-pill px-4 fw-bold <?php echo $filter === 'approved' ? 'btn-success shadow' : 'btn-light border text-muted'; ?>">
            <i class="fa-solid fa-check me-2"></i> Paid & Approved
            <span class="badge bg-secondary bg-opacity-25 text-dark rounded-circle ms-2 px-2 py-1"><?php echo $counts['approved']; ?></span>
        </a>
        <a href="?filter=rejected" class="btn rounded-pill px-4 fw-bold <?php echo $filter === 'rejected' ? 'btn-danger shadow' : 'btn-light border text-muted'; ?>">
            <i class="fa-solid fa-xmark me-2"></i> Rejected
            <span class="badge bg-secondary bg-opacity-25 text-dark rounded-circle ms-2 px-2 py-1"><?php echo $counts['rejected']; ?></span>
        </a>
        <a href="?filter=all" class="btn rounded-pill px-4 fw-bold ms-auto <?php echo $filter === 'all' ? 'btn-dark shadow' : 'btn-light border text-muted'; ?>">
            View All
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Date Reserved</th>
                            <th class="py-3 fw-bold">Customer</th>
                            <th class="py-3 fw-bold">Reserved Item</th>
                            <th class="py-3 fw-bold">Financials</th>
                            <th class="py-3 fw-bold">Payment Status</th>
                            <th class="py-3 fw-bold">Status</th>
                            <th class="text-end pe-4 py-3 fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">

                                    <!-- Date -->
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

                                    <!-- Customer -->
                                    <td class="py-3">
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h6>
                                        <small class="text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i><?php echo htmlspecialchars($row['contact_number']); ?></small>
                                    </td>

                                    <!-- Item -->
                                    <td class="py-3">
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;"><?php echo htmlspecialchars($row['item_name']); ?></small>
                                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h6>
                                    </td>

                                    <!-- Financials -->
                                    <td class="py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-success" style="font-size: 0.9rem;">DP: ₱<?php echo number_format($row['reservation_amount'], 2); ?></span>
                                            <small class="text-muted" style="font-size: 0.7rem;">Price: ₱<?php echo number_format($row['selling_price'], 2); ?></small>
                                            <?php if (!empty($row['receipt_number'])): ?>
                                                <span class="text-muted font-monospace" style="font-size:0.65rem;"><?php echo htmlspecialchars($row['receipt_number']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="py-3">
                                        <?php $ps = $row['payment_status'] ?? 'unpaid'; ?>
                                        <?php if ($ps === 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                                                <i class="fa-brands fa-cc-visa me-1"></i> Paid via PayMongo
                                            </span>
                                            <?php if (!empty($row['paid_at'])): ?>
                                                <div class="text-muted mt-1" style="font-size:0.68rem;"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($row['paid_at'])); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">
                                                <i class="fa-solid fa-hourglass-half me-1"></i> Awaiting Payment
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Reservation Status -->
                                    <td class="py-3">
                                        <?php $s = $row['status']; ?>
                                        <?php if ($s === 'pending_verification'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i> Pending Verification
                                            </span>
                                        <?php elseif ($s === 'approved'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                                                <i class="fa-solid fa-check me-1"></i> Approved
                                            </span>
                                        <?php elseif ($s === 'rejected'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">
                                                <i class="fa-solid fa-xmark me-1"></i> Rejected
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill"><?php echo ucfirst($s); ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-end pe-4 py-3">
                                        <button class="btn btn-sm btn-dark rounded-pill px-3 fw-bold shadow-sm btn-hover-lift"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resModal<?php echo $row['reservation_id']; ?>">
                                            View Details
                                        </button>
                                    </td>
                                </tr>

                                <!-- Detail Modal -->
                                <div class="modal fade" id="resModal<?php echo $row['reservation_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-md">
                                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                                            <div class="modal-header bg-dark text-white border-0 p-4">
                                                <div>
                                                    <small class="text-white-50 text-uppercase fw-bold" style="font-size:0.7rem;">Reservation Details</small>
                                                    <h5 class="modal-title fw-bold mb-0"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h5>
                                                </div>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body p-4">

                                                <!-- Payment badge -->
                                                <div class="text-center mb-4">
                                                    <?php if (($row['payment_status'] ?? '') === 'paid'): ?>
                                                        <div class="d-inline-flex align-items-center gap-2 bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-4 py-2 rounded-pill fw-bold">
                                                            <i class="fa-brands fa-cc-visa fs-5"></i>
                                                            Payment Verified by PayMongo
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="d-inline-flex align-items-center gap-2 bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-4 py-2 rounded-pill fw-bold">
                                                            <i class="fa-solid fa-hourglass-half"></i>
                                                            Awaiting Online Payment
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <ul class="list-group list-group-flush mb-4">
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Customer</span>
                                                        <span class="fw-bold small"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                                    </li>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Contact</span>
                                                        <span class="fw-bold small"><?php echo htmlspecialchars($row['contact_number']); ?></span>
                                                    </li>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Reserved Item</span>
                                                        <span class="fw-bold small"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></span>
                                                    </li>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Selling Price</span>
                                                        <span class="fw-bold small">₱<?php echo number_format($row['selling_price'], 2); ?></span>
                                                    </li>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Downpayment</span>
                                                        <span class="fw-bold text-primary small">₱<?php echo number_format($row['reservation_amount'], 2); ?></span>
                                                    </li>
                                                    <?php if (!empty($row['receipt_number'])): ?>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Receipt No.</span>
                                                        <span class="fw-bold font-monospace small text-success"><?php echo htmlspecialchars($row['receipt_number']); ?></span>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['paymongo_link_id'])): ?>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">PayMongo ID</span>
                                                        <span class="fw-bold font-monospace small text-muted" style="font-size:0.7rem;"><?php echo htmlspecialchars($row['paymongo_link_id']); ?></span>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['paid_at'])): ?>
                                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                                        <span class="text-muted small">Paid At</span>
                                                        <span class="fw-bold small"><?php echo date('M d, Y h:i A', strtotime($row['paid_at'])); ?></span>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>

                                                <!-- Contextual alert inside modal -->
                                                <?php if (($row['payment_status'] ?? '') === 'paid' && $row['status'] === 'pending_verification'): ?>
                                                    <div class="alert alert-info border-0 small bg-info bg-opacity-10 d-flex align-items-center mb-0">
                                                        <i class="fa-solid fa-circle-info fs-4 me-3 text-info"></i>
                                                        <div>
                                                            <strong>Payment Received — Awaiting Your Approval</strong><br>
                                                            PayMongo confirmed this payment. Please verify and approve or reject below.
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="modal-footer border-0 bg-light p-3">
                                                <?php if ($row['status'] === 'approved'): ?>
                                                    <form action="../../core/process_verify_reservation.php" method="POST" class="w-100 d-flex gap-2 m-0"
                                                          onsubmit="return confirm('Are you sure you want to reject this approved reservation?');">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                        <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                        <button type="button" class="btn btn-light border fw-bold rounded-3 w-50" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger fw-bold rounded-3 w-50">Reject Override</button>
                                                    </form>
                                                <?php elseif ($row['status'] === 'pending_verification'): ?>
                                                    <form action="../../core/process_verify_reservation.php" method="POST" class="w-100 d-flex gap-2 m-0">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                        <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger fw-bold rounded-3 w-50">
                                                            <i class="fa-solid fa-xmark me-1"></i> Reject
                                                        </button>
                                                        <button type="submit" name="action" value="approve" class="btn btn-success fw-bold rounded-3 w-50 shadow-sm">
                                                            <i class="fa-solid fa-check me-1"></i> Approve
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-light border fw-bold w-100" data-bs-dismiss="modal">Close</button>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width:80px;height:80px;">
                                            <i class="fa-solid fa-inbox fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Reservations Found</h5>
                                        <p class="text-muted small">There are currently no "<?php echo ucfirst(str_replace('_', ' ', $filter)); ?>" reservations.</p>
                                        <?php if ($filter !== 'pending_verification'): ?>
                                            <a href="?filter=pending_verification" class="btn btn-sm btn-outline-primary rounded-pill px-4 mt-2">View Pending Verification</a>
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
    .btn-hover-lift:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
    .custom-table tbody tr { transition: background-color 0.2s; }
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    .custom-scrollbar::-webkit-scrollbar { height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>