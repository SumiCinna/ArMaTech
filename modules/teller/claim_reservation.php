<?php
// modules/teller/claim_reservation.php
require_once '../../config/database.php';
require_once '../../core/reservation_expiry.php';
include_once '../../includes/teller_header.php';

// Keep reservation statuses synchronized before loading approved claims.
run_reservation_expiry($conn);

// Pagination & Search Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%" . $search . "%";

// Base Query Parts
$base_query = "FROM shop_reservations sr
               JOIN shop_items si ON sr.shop_id = si.shop_id
               JOIN items i ON si.item_id = i.item_id
               JOIN transactions t ON si.transaction_id = t.transaction_id
               JOIN profiles p ON sr.customer_profile_id = p.profile_id
               WHERE sr.status = 'approved'";

if (!empty($search)) {
    $base_query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR t.pt_number LIKE ?)";
}

// 1. Count Total Rows
$count_sql = "SELECT COUNT(*) as total " . $base_query;
$stmt_count = $conn->prepare($count_sql);
if (!empty($search)) {
    $stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// 2. Fetch Data
$sql = "SELECT sr.*, 
               p.first_name, p.last_name, p.contact_number, p.profile_id as cust_profile_id,
               si.selling_price, 
               i.device_type AS item_name, i.brand, i.model,
               t.pt_number, t.transaction_id
        " . $base_query . "
        ORDER BY sr.created_at ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="row align-items-center mt-4 mb-4 g-3">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-hand-holding-hand me-2 text-primary"></i> Process Pickups</h3>
            <p class="text-muted mb-0 small">Manage walk-in customers claiming their reserved online shop items.</p>
        </div>
        
        <div class="col-md-5">
            <form method="GET" class="d-flex justify-content-md-end">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white" style="max-width: 350px;">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-3">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-0 shadow-none bg-transparent" placeholder="Search Name or PT#..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($search)): ?>
                        <a href="?" class="btn btn-light text-muted border-0"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-primary px-4 fw-bold" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Customer</th>
                            <th class="py-3 fw-bold">Reserved Item</th>
                            <th class="py-3 fw-bold">Payment Details</th>
                            <th class="text-end pe-4 py-3 fw-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $balance = $row['selling_price'] - $row['reservation_amount'];
                                    // Get initials for Avatar
                                    $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
                                ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary fw-bold me-3 border border-primary border-opacity-25">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                                <small class="text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i> <?php echo $row['contact_number']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center">
                                            <?php echo $row['item_name']; ?> 
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-2 small">PT# <?php echo $row['pt_number']; ?></span>
                                        </h6>
                                        <small class="text-muted"><?php echo $row['brand'] . ' ' . $row['model']; ?></small>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-4 text-end" style="min-width: 100px;">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Total Price</small>
                                                <span class="text-dark fw-bold" style="font-size: 0.85rem;">₱<?php echo number_format($row['selling_price'], 2); ?></span>
                                            </div>
                                            <div class="me-4 text-end border-end pe-4" style="min-width: 110px;">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Less: Downpayment</small>
                                                <span class="text-danger fw-bold" style="font-size: 0.85rem;">- ₱<?php echo number_format($row['reservation_amount'], 2); ?></span>
                                            </div>
                                            <div>
                                                <small class="text-primary text-uppercase fw-bold d-block" style="font-size: 0.7rem;">Collect Cash</small>
                                                <span class="fw-bold fs-5 text-success">₱<?php echo number_format($balance, 2); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <button class="btn btn-primary fw-bold px-3 rounded-pill shadow-sm btn-hover-lift" data-bs-toggle="modal" data-bs-target="#claimModal<?php echo $row['reservation_id']; ?>">
                                            Process <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="claimModal<?php echo $row['reservation_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                            <div class="bg-primary" style="height: 6px;"></div>
                                            
                                            <div class="modal-header border-0 bg-white pb-0 mt-2">
                                                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-cash-register me-2 text-primary"></i> Checkout POS</h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                                            </div>
                                            
                                            <form action="../../core/process_claim_item.php" method="POST">
                                                <div class="modal-body p-4 text-start">
                                                    
                                                    <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                    <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $row['transaction_id']; ?>">
                                                    <input type="hidden" name="customer_profile_id" value="<?php echo $row['cust_profile_id']; ?>">
                                                    <input type="hidden" name="balance_amount" value="<?php echo $balance; ?>">

                                                    <div class="bg-light rounded-4 p-4 text-center border mb-4">
                                                        <small class="text-muted text-uppercase letter-spacing-1 fw-bold">Customer Balance</small>
                                                        <h4 class="fw-bold text-dark mt-1 mb-3"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h4>
                                                        
                                                        <div class="bg-white rounded-3 shadow-sm p-3 border">
                                                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom text-muted small">
                                                                <span>Item</span>
                                                                <span class="fw-bold text-dark"><?php echo $row['brand'] . ' ' . $row['model']; ?></span>
                                                            </div>
                                                            <small class="text-primary fw-bold text-uppercase d-block mb-1">Cash to Receive</small>
                                                            <h1 class="fw-bold text-primary mb-0" style="letter-spacing: -1px;">₱<?php echo number_format($balance, 2); ?></h1>
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-warning border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 d-flex align-items-center mb-0">
                                                        <i class="fa-solid fa-vault fs-3 me-3 text-warning"></i>
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-0">Teller Check</h6>
                                                            <small class="text-muted">Retrieve <strong>PT# <?php echo $row['pt_number']; ?></strong> from the vault before clicking confirm.</small>
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="modal-footer border-0 bg-light p-3 d-flex flex-nowrap">
                                                    <button type="button" class="btn btn-light border fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btn_complete_claim" class="btn btn-success fw-bold w-50 shadow-sm">Confirm & Release</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-box-open fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Pending Pickups</h5>
                                        <p class="text-muted small"><?php echo !empty($search) ? "No reservations matched your search query." : "There are currently no approved reservations waiting for pickup."; ?></p>
                                        <?php if(!empty($search)): ?>
                                            <a href="?" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2">Clear Search</a>
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
    
    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination pagination-sm justify-content-center custom-pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link shadow-sm fw-bold" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<style>
    /* Avatar Style */
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    
    /* Table Enhancements */
    .custom-table tbody tr:hover {
        background-color: #fcfdfd !important;
    }
    
    /* Button Hover Lift */
    .btn-hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    /* Typography Utilities */
    .letter-spacing-1 {
        letter-spacing: 1px;
    }

    /* Custom Pagination Pills */
    .custom-pagination .page-link {
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 4px;
        color: #495057;
        border: none;
    }
    .custom-pagination .page-item.active .page-link {
        background-color: #0d6efd;
        color: #fff;
    }
    .custom-pagination .page-item.disabled .page-link {
        background-color: transparent;
        color: #adb5bd;
        box-shadow: none !important;
    }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>