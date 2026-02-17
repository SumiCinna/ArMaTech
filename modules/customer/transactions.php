<?php
// modules/customer/transactions.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// SECURITY: Ensure user is logged in
if (!isset($_SESSION['account_id'])) {
    header("Location: ../../customer_login.php");
    exit();
}

// Fetch Customer Profile ID
$stmt = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt->bind_param("i", $_SESSION['account_id']);
$stmt->execute();
$customer_id = $stmt->get_result()->fetch_assoc()['profile_id'];

// SEARCH LOGIC
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Base Query
$sql = "SELECT t.*, i.device_type, i.brand, i.model 
        FROM transactions t 
        JOIN items i ON t.transaction_id = i.transaction_id 
        WHERE t.customer_id = ?";

// Add Search Filters
if (!empty($search)) {
    $sql .= " AND (t.pt_number LIKE ? OR i.model LIKE ? OR i.brand LIKE ?)";
}
if ($filter != 'all') {
    $sql .= " AND t.status = ?";
}

$sql .= " ORDER BY t.date_pawned DESC";

$stmt = $conn->prepare($sql);

// Bind Parameters Dynamically
if (!empty($search) && $filter != 'all') {
    $term = "%$search%";
    $stmt->bind_param("isss", $customer_id, $term, $term, $term, $filter);
} elseif (!empty($search)) {
    $term = "%$search%";
    $stmt->bind_param("isss", $customer_id, $term, $term, $term);
} elseif ($filter != 'all') {
    $stmt->bind_param("is", $customer_id, $filter);
} else {
    $stmt->bind_param("i", $customer_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Transaction History</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-white rounded">
            <form method="GET" class="row g-2">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by PT Number or Item Name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select bg-light">
                        <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo ($filter == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="redeemed" <?php echo ($filter == 'redeemed') ? 'selected' : ''; ?>>Redeemed</option>
                        <option value="expired" <?php echo ($filter == 'expired') ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="row g-3">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    // Status Badge Logic
                    $badge_color = 'bg-secondary';
                    $icon = 'fa-circle-question';
                    
                    if ($row['status'] == 'active') {
                        $badge_color = 'bg-success';
                        $icon = 'fa-circle-check';
                    } elseif ($row['status'] == 'redeemed') {
                        $badge_color = 'bg-primary';
                        $icon = 'fa-hand-holding-hand';
                    } elseif ($row['status'] == 'expired') {
                        $badge_color = 'bg-danger';
                        $icon = 'fa-triangle-exclamation';
                    }
                ?>

                <div class="col-md-6 col-lg-12">
                    <div class="card shadow-sm border-0 h-100 hover-card">
                        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                            
                            <div class="d-flex align-items-center mb-2 mb-md-0">
                                <div class="rounded-circle p-3 me-3 <?php echo $badge_color; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badge_color); ?>">
                                    <i class="fa-solid <?php echo $icon; ?> fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><?php echo $row['brand'] . ' ' . $row['model']; ?></h5>
                                    <small class="text-muted d-block">PT#: <?php echo $row['pt_number']; ?></small>
                                    <small class="text-muted"><i class="fa-solid fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></small>
                                </div>
                            </div>

                            <div class="text-end mb-2 mb-md-0 px-3 border-start border-end d-none d-md-block">
                                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Principal Amount</small>
                                <h4 class="fw-bold text-dark mb-0">₱<?php echo number_format($row['principal_amount'], 2); ?></h4>
                            </div>
                            
                            <div class="text-end">
                                <span class="badge <?php echo $badge_color; ?> text-uppercase mb-2 px-3 py-2 rounded-pill">
                                    <?php echo $row['status']; ?>
                                </span>
                                <br>
                                <a href="view_details.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-link btn-sm text-decoration-none fw-bold text-primary p-0">
                                    View Details <i class="fa-solid fa-chevron-right ms-1"></i>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fa-solid fa-file-invoice fa-4x text-muted opacity-25"></i>
            </div>
            <h5 class="text-muted">No transactions found matching your criteria.</h5>
        </div>
    <?php endif; ?>

</div>

<?php include_once '../../includes/customer_footer.php'; ?>