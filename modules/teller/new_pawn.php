<?php
// modules/teller/new_pawn.php
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

// Handle Search Logic
$search_results = [];
$search_term = "";
$is_search = false;
$total_pages = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $is_search = true;
    $search_term = trim($_GET['search']);
    $like_term = "%" . $search_term . "%";
    
    // Search by Name or Username/ID
    $sql = "SELECT p.*, a.username FROM profiles p 
            LEFT JOIN accounts a ON p.profile_id = a.profile_id 
            WHERE p.first_name LIKE ? OR p.last_name LIKE ? OR a.username LIKE ? OR p.public_id LIKE ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $like_term, $like_term, $like_term, $like_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()){
        $search_results[] = $row;
    }
} else {
    // Default View: Recent Customers
    // 1. Count Total
    $count_sql = "SELECT COUNT(*) as total FROM accounts WHERE role = 'customer'";
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    // 2. Fetch Data
    $sql = "SELECT p.*, a.username 
            FROM accounts a
            JOIN profiles p ON a.profile_id = p.profile_id
            WHERE a.role = 'customer'
            ORDER BY p.profile_id DESC 
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()){
        $search_results[] = $row;
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            
            <h2 class="mb-4 fw-bold text-secondary">Select Customer Type</h2>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-0 transition-card">
                        <div class="card-body py-5">
                            <div class="mb-3 text-primary"><i class="bi bi-people-fill display-4"></i></div>
                            <h4 class="fw-bold">Existing Customer</h4>
                            <p class="text-muted small">Search for previous records.</p>
                            <form action="" method="GET" class="mt-3">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?php echo htmlspecialchars($search_term); ?>">
                                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-0 transition-card">
                        <div class="card-body py-5">
                            <div class="mb-3 text-success"><i class="bi bi-person-plus-fill display-4"></i></div>
                            <h4 class="fw-bold">New Walk-in Customer</h4>
                            <p class="text-muted small">Register and create auto-account.</p>
                            
                            <button type="button" class="btn btn-success btn-lg w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#walkinModal">
                                Register & Transaction <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Results Section (Search or Recent) -->
            <?php if(count($search_results) > 0 || $is_search): ?>
                <div class="mt-5 text-start">
                    <?php if($is_search): ?>
                        <h5 class="text-muted mb-3">Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h5>
                    <?php else: ?>
                        <h5 class="text-muted mb-3 fw-bold text-uppercase small">Recent Registered Customers</h5>
                    <?php endif; ?>
                    
                    <?php if(count($search_results) > 0): ?>
                        <div class="list-group shadow-sm">
                            <?php foreach($search_results as $cust): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 mb-2 border rounded shadow-sm">
                                    <div>
                                        <span class="badge bg-secondary"><?php echo $cust['public_id'] ?? 'N/A'; ?></span>
                                        <h5 class="d-inline ms-2 fw-bold text-dark"><?php echo $cust['first_name'] . " " . $cust['last_name']; ?></h5>
                                        <div class="small text-muted mt-1 ms-1"><i class="bi bi-telephone"></i> <?php echo $cust['contact_number']; ?></div>
                                    </div>
                                    <a href="pawn_item_entry.php?customer_id=<?php echo $cust['profile_id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">Select <i class="bi bi-chevron-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if (!$is_search && $total_pages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-warning"><i class="bi bi-exclamation-circle"></i> No customer found. Please register as new walk-in.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'modal_walkin.php'; ?>

<?php include_once '../../includes/teller_footer.php'; ?>