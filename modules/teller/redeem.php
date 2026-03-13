<?php
// modules/teller/redeem.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$error = "";
$results = [];
$search_mode = false;
// Initialize variables to avoid undefined variable warnings if not set in search block
$total_pages = 1; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_mode = true;
    $q = trim($_GET['q']);
    $like_q = "%" . $q . "%";
    // Search by Ticket Number OR Customer Public ID
    $sql = "SELECT t.*, 
                   p.first_name, p.last_name, p.public_id,
                   i.brand, i.model, i.device_type
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE (t.pt_number LIKE ? OR p.public_id LIKE ?) 
            AND t.status IN ('active', 'expired')
            ORDER BY t.date_pawned DESC"; 
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $like_q, $like_q);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    if (count($results) == 0) {
        $error = "No active transaction found for '$q'.";
    }
} else {
    // Default View: Latest Items with Pagination
    // 1. Count Total
    $count_sql = "SELECT COUNT(*) as total FROM transactions WHERE status IN ('active', 'expired')";
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    // 2. Fetch Data
    $sql = "SELECT t.*, 
                   p.first_name, p.last_name, p.public_id,
                   i.brand, i.model, i.device_type
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE t.status IN ('active', 'expired')
            ORDER BY t.date_pawned DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container-fluid px-4 pb-5">
    
    <!-- Search Header -->
    <div class="row justify-content-center mt-5 mb-4">
        <div class="col-md-8 text-center">
            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="fa-solid fa-cash-register fs-2"></i>
            </div>
            <h2 class="fw-bold text-dark">Redeem or Renew Items</h2>
            <p class="text-muted mb-4">Search for a Pawn Ticket number or Customer ID to process payments.</p>

            <form method="GET" action="redeem.php">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white border">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-4">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent py-3" placeholder="Scan Pawn Ticket (PT-...) or Customer ID..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" autofocus>
                    <button class="btn btn-success px-4 fw-bold" type="submit">Search Record</button>
                </div>
            </form>
        </div>
    </div>

    <?php if($error): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="alert alert-danger border-0 shadow-sm rounded-3 text-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <?php if (!$search_mode && empty($error)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold text-uppercase letter-spacing-1 mb-0">Recent Active Pawns</h6>
                </div>
            <?php endif; ?>

            <?php if (count($results) > 0): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results as $row): ?>
                            <?php 
                                $status_class = ($row['status'] == 'active') ? 'bg-success text-success' : 'bg-danger text-danger';
                                $status_icon = ($row['status'] == 'active') ? 'fa-circle-check' : 'fa-triangle-exclamation';
                            ?>
                            <a href="redeem_process.php?id=<?php echo $row['transaction_id']; ?>" class="list-group-item list-group-item-action p-4 d-flex align-items-center justify-content-between border-bottom-0" style="border-bottom: 1px solid #f8f9fa !important;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm text-secondary" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-mobile-screen fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold text-dark"><?php echo $row['brand'] . ' ' . $row['model']; ?></h5>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-light text-dark border me-2 font-monospace">PT: <?php echo $row['pt_number']; ?></span>
                                            Owner: <strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $status_class; ?> bg-opacity-10 border rounded-pill px-3 mb-2 text-uppercase">
                                        <i class="fa-solid <?php echo $status_icon; ?> me-1"></i> <?php echo $row['status']; ?>
                                    </span>
                                    <div class="text-primary small fw-bold">Select <i class="fa-solid fa-arrow-right ms-1"></i></div>
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
                <div class="text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-inbox fs-1 opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Active Items</h5>
                    <p class="text-muted small">There are currently no active or expired transactions to redeem.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .letter-spacing-1 { letter-spacing: 1px; }
    .list-group-item:hover { background-color: #fcfdfd; }
    
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
        background-color: #198754; /* Success Green */
        color: #fff;
    }
    .custom-pagination .page-item.disabled .page-link {
        background-color: transparent;
        color: #adb5bd;
        box-shadow: none !important;
    }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>