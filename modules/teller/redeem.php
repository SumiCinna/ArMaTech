<?php
// modules/teller/redeem.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$error = "";
$results = [];
$search_mode = false;
$total_pages = 0;
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

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-search"></i> Redeem Item</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control" placeholder="Scan Pawn Ticket (PT-...) or Customer ID (CUS-...)" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" autofocus>
                        <button class="btn btn-primary" type="submit">Search</button>
                    </form>
                </div>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!$search_mode && empty($error)): ?>
                <h6 class="text-muted mb-3 fw-bold text-uppercase small">Recent Active Items</h6>
            <?php endif; ?>

            <?php if (count($results) > 0): ?>
                <div class="list-group">
                    <?php foreach ($results as $row): ?>
                        <a href="redeem_process.php?id=<?php echo $row['transaction_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-2 shadow-sm border rounded">
                            <div>
                                <h5 class="mb-1 text-primary fw-bold"><?php echo $row['pt_number']; ?></h5>
                                <p class="mb-1 fw-bold"><?php echo $row['brand'] . ' ' . $row['model']; ?> (<?php echo $row['device_type']; ?>)</p>
                                <small class="text-muted">
                                    Owner: <span class="badge bg-secondary"><?php echo $row['public_id']; ?></span> 
                                    <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark mb-1">Status: <?php echo strtoupper($row['status']); ?></span><br>
                                <span class="btn btn-sm btn-success fw-bold">Select <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if (!$search_mode && $total_pages > 1): ?>
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

            <?php elseif (!$search_mode): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fa-3x mb-3 opacity-25"></i>
                    <p>No active items found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>