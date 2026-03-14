<?php
// modules/teller/redeem.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$error = "";
$results = [];
$search_mode = false;
$total_pages = 1; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_mode = true;
    $q = trim($_GET['q']);
    $like_q = "%" . $q . "%";
    
    // BACKEND FIX: Removed 'expired' from the IN clause to strictly block them.
    $sql = "SELECT t.*, 
                   p.first_name, p.last_name, p.public_id,
                   i.brand, i.model, i.device_type
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE (t.pt_number LIKE ? OR p.public_id LIKE ?) 
            AND t.status = 'active'
            ORDER BY t.date_pawned DESC"; 
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $like_q, $like_q);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    if (count($results) == 0) {
        $error = "No active transaction found for '$q'. If the item is expired, it has been foreclosed and moved to inventory.";
    }
} else {
    // BACKEND FIX: Removed 'expired' from the default view as well.
    $count_sql = "SELECT COUNT(*) as total FROM transactions WHERE status = 'active'";
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    $sql = "SELECT t.*, 
                   p.first_name, p.last_name, p.public_id,
                   i.brand, i.model, i.device_type
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE t.status = 'active'
            ORDER BY t.date_pawned DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="row justify-content-center mt-5 mb-5">
        <div class="col-md-8 text-center">
            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm border border-success border-opacity-25" style="width: 80px; height: 80px;">
                <i class="fa-solid fa-cash-register fa-2x ps-1"></i>
            </div>
            <h2 class="fw-bold text-dark mb-1">Process Payment</h2>
            <p class="text-muted mb-4 small">Scan a Pawn Ticket or search by Customer ID to process renewals and redemptions.</p>

            <form method="GET" action="redeem.php">
                <div class="input-group input-group-lg shadow rounded-pill overflow-hidden bg-white border border-secondary border-opacity-25" style="max-width: 600px; margin: 0 auto;">
                    <span class="input-group-text bg-transparent border-0 text-success ps-4">
                        <i class="fa-solid fa-barcode fa-lg"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent py-3 fw-bold text-dark" placeholder="Scan PT Number or ID..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" autofocus autocomplete="off">
                    <?php if($search_mode): ?>
                        <a href="redeem.php" class="btn btn-light text-muted border-0 d-flex align-items-center"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-success px-4 fw-bold text-uppercase" type="submit" style="letter-spacing: 1px;">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if($error): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-md-8 col-lg-6">
                <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-4">
                    <i class="fa-solid fa-circle-exclamation fa-2x me-3 opacity-75"></i> 
                    <div>
                        <strong class="d-block mb-1">Search Failed</strong>
                        <span class="small"><?php echo $error; ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            
            <?php if (!$search_mode && empty($error)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                    <h6 class="text-muted fw-bold text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">Recent Active Loans</h6>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?php echo $total_rows; ?> Total</span>
                </div>
            <?php endif; ?>

            <?php if (count($results) > 0): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results as $row): ?>
                            <?php 
                                // Dynamic Device Icon
                                $icon = 'fa-box-open';
                                $cat_lower = strtolower($row['device_type']);
                                if (strpos($cat_lower, 'phone') !== false || strpos($cat_lower, 'smartphone') !== false) $icon = 'fa-mobile-screen-button';
                                elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
                                elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
                                elseif (strpos($cat_lower, 'watch') !== false) $icon = 'fa-stopwatch';
                            ?>
                            
                            <a href="redeem_process.php?id=<?php echo $row['transaction_id']; ?>" class="list-group-item list-group-item-action p-3 p-md-4 d-flex align-items-center justify-content-between border-bottom-0 btn-hover-lift" style="border-bottom: 1px solid #f1f2f4 !important;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-4 bg-light d-flex align-items-center justify-content-center me-3 me-md-4 flex-shrink-0 border" style="width: 60px; height: 60px;">
                                        <i class="fa-solid <?php echo $icon; ?> fs-3 text-secondary opacity-75 ps-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h5>
                                        <div class="d-flex align-items-center flex-wrap gap-2 text-muted" style="font-size: 0.8rem;">
                                            <span class="badge bg-secondary bg-opacity-10 text-dark border font-monospace">PT# <?php echo $row['pt_number']; ?></span>
                                            <span><i class="fa-regular fa-user me-1"></i> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end ps-3 border-start ms-auto">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 text-uppercase mb-2 d-none d-md-inline-block">
                                        <i class="fa-solid fa-shield-check me-1"></i> Active
                                    </span>
                                    <div class="btn btn-sm btn-dark rounded-pill fw-bold px-3 shadow-sm d-block w-100">
                                        Process <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!$search_mode && $total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center custom-pagination">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                            </li>
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link shadow-sm fw-bold" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php elseif (!$search_mode): ?>
                <div class="text-center py-5 my-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 border shadow-sm" style="width: 90px; height: 90px;">
                        <i class="fa-solid fa-box-open fs-1 text-muted opacity-50"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">No Active Loans</h4>
                    <p class="text-muted small">There are currently no active transactions in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .btn-hover-lift { transition: transform 0.2s ease, background-color 0.2s ease; }
    .btn-hover-lift:hover { transform: scale(1.01); background-color: #f8faff !important; z-index: 1; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important; border-color: #198754 !important; }
    
    /* Custom Pagination */
    .custom-pagination .page-link { border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin: 0 5px; color: #495057; border: none; }
    .custom-pagination .page-item.active .page-link { background-color: #198754; color: #fff; }
    .custom-pagination .page-item.disabled .page-link { background-color: transparent; color: #adb5bd; box-shadow: none !important; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>