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
$limit = 8; // Slightly smaller limit for cleaner UI
$offset = ($page - 1) * $limit;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $is_search = true;
    $search_term = trim($_GET['search']);
    $like_term = "%" . $search_term . "%";
    
    // Search by Name or Username/ID
    $sql = "SELECT p.*, a.username FROM profiles p 
            LEFT JOIN accounts a ON p.profile_id = a.profile_id 
            WHERE a.role = 'customer' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR a.username LIKE ? OR p.public_id LIKE ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $like_term, $like_term, $like_term, $like_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()){
        $search_results[] = $row;
    }
} else {
    // Default View: Recent Customers
    $count_sql = "SELECT COUNT(*) as total FROM accounts WHERE role = 'customer'";
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

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

<div class="container-fluid px-4 pb-5">
    
    <div class="row justify-content-center mt-5">
        <div class="col-md-10 col-lg-8 text-center">
            
            <h2 class="fw-bold text-dark mb-2">Create New Pawn</h2>
            <p class="text-muted mb-5">Select or register a customer to begin a new transaction.</p>
            
            <div class="row g-4 mb-5">
                
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-0 rounded-4 hover-lift">
                        <div class="card-body p-4 p-lg-5">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-users fa-2x"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Existing Customer</h4>
                            <p class="text-muted small mb-4">Search the database for previous records.</p>
                            
                            <form action="" method="GET">
                                <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                                    <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="fa-solid fa-search"></i></span>
                                    <input type="text" name="search" class="form-control border-0 shadow-none bg-white py-2" placeholder="Name or ID..." value="<?php echo htmlspecialchars($search_term); ?>">
                                    <button class="btn btn-primary px-4 fw-bold" type="submit">Find</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-0 rounded-4 hover-lift" style="background: linear-gradient(145deg, #198754 0%, #146c43 100%);">
                        <div class="card-body p-4 p-lg-5 d-flex flex-column align-items-center justify-content-center text-white">
                            <div class="bg-white bg-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-user-plus fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-2">New Customer</h4>
                            <p class="text-white-50 small mb-4">Register a first-time walk-in client.</p>
                            
                            <button type="button" class="btn btn-light text-success fw-bold btn-lg rounded-pill px-5 shadow-sm w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#walkinModal">
                                Register Now <i class="fa-solid fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="text-start">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2 border-bottom pb-2">
                    <?php if($is_search): ?>
                        <h6 class="text-muted fw-bold text-uppercase mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Search Results: "<?php echo htmlspecialchars($search_term); ?>"</h6>
                        <a href="new_pawn.php" class="text-danger small fw-bold text-decoration-none">Clear</a>
                    <?php else: ?>
                        <h6 class="text-muted fw-bold text-uppercase mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Recently Registered</h6>
                    <?php endif; ?>
                </div>
                
                <?php if(count($search_results) > 0): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                        <div class="list-group list-group-flush">
                            <?php foreach($search_results as $cust): ?>
                                <a href="pawn_item_entry.php?customer_id=<?php echo $cust['profile_id']; ?>" class="list-group-item list-group-item-action p-3 d-flex align-items-center justify-content-between border-bottom-0 custom-list-item">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3 flex-shrink-0 fw-bold" style="width: 50px; height: 50px;">
                                            <?php echo substr($cust['first_name'], 0, 1) . substr($cust['last_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <h6 class="d-inline mb-0 fw-bold text-dark"><?php echo htmlspecialchars($cust['first_name'] . " " . $cust['last_name']); ?></h6>
                                            <div class="d-flex align-items-center mt-1 gap-2">
                                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;"><?php echo $cust['public_id'] ?? 'N/A'; ?></span>
                                                <span class="text-muted small"><i class="fa-solid fa-phone ms-1 me-1"></i> <?php echo $cust['contact_number']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold">
                                        Select
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!$is_search && $total_pages > 1): ?>
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

                <?php else: ?>
                    <div class="text-center py-5 my-3 bg-white rounded-4 shadow-sm">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                            <i class="fa-solid fa-user-slash fs-1 opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">No Customer Found</h5>
                        <p class="text-muted small mb-0">We couldn't find anyone matching "<?php echo htmlspecialchars($search_term); ?>". Please register them as a new walk-in.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include 'modal_walkin.php'; ?>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    
    .custom-list-item { transition: background-color 0.2s; border-bottom: 1px solid #f1f2f4 !important; }
    .custom-list-item:hover { background-color: #f8faff !important; z-index: 1; position: relative; }
    
    .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important; border-color: #0d6efd !important; }
    
    /* Custom Pagination Pills */
    .custom-pagination .page-link { border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin: 0 5px; color: #495057; border: none; }
    .custom-pagination .page-item.active .page-link { background-color: #0d6efd; color: #fff; }
    .custom-pagination .page-item.disabled .page-link { background-color: transparent; color: #adb5bd; box-shadow: none !important; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>