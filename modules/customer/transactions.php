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

<div class="container mt-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Transaction History</h3>
            <p class="text-muted small mb-0">View all your past and present pawn records.</p>
        </div>
        <a href="dashboard.php" class="btn btn-light border shadow-sm fw-bold rounded-pill px-3 d-none d-md-inline-block">
            <i class="fa-solid fa-arrow-left me-2"></i> Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-pill overflow-hidden bg-white p-2">
        <form method="GET" class="d-flex flex-column flex-md-row m-0 g-0">
            <div class="flex-grow-1 position-relative border-md-end mb-2 mb-md-0">
                <span class="position-absolute top-50 translate-middle-y text-muted ms-3"><i class="fa-solid fa-search"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none ps-5 py-2 bg-transparent" placeholder="Search PT Number, Brand, or Model..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="d-flex" style="min-width: 250px;">
                <select name="filter" class="form-select border-0 shadow-none bg-transparent py-2 fw-bold text-secondary border-start" onchange="this.form.submit()">
                    <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo ($filter == 'active') ? 'selected' : ''; ?>>🟢 Active</option>
                    <option value="redeemed" <?php echo ($filter == 'redeemed') ? 'selected' : ''; ?>>⚫ Redeemed</option>
                    <option value="expired" <?php echo ($filter == 'expired') ? 'selected' : ''; ?>>🔴 Expired</option>
                    <option value="auctioned" <?php echo ($filter == 'auctioned') ? 'selected' : ''; ?>>🟣 Auctioned</option>
                </select>
                
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold ms-2 shadow-sm">Filter</button>
            </div>
        </form>
    </div>

    <?php if(!empty($search) || $filter != 'all'): ?>
        <div class="mb-4 d-flex align-items-center">
            <span class="text-muted small me-2">Showing results for:</span>
            <?php if(!empty($search)): ?>
                <span class="badge bg-secondary bg-opacity-10 text-dark border px-3 py-2 rounded-pill me-2">"<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
            <?php if($filter != 'all'): ?>
                <span class="badge bg-secondary bg-opacity-10 text-dark border px-3 py-2 rounded-pill me-2">Status: <?php echo ucfirst($filter); ?></span>
            <?php endif; ?>
            <a href="transactions.php" class="text-danger small text-decoration-none fw-bold ms-2">Clear Filters</a>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="d-flex flex-column gap-3">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    // Dynamic Device Icon
                    $icon = 'fa-box-open';
                    $cat_lower = strtolower($row['device_type']);
                    if (strpos($cat_lower, 'smartphone') !== false || strpos($cat_lower, 'phone') !== false) $icon = 'fa-mobile-screen-button';
                    elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
                    elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
                    elseif (strpos($cat_lower, 'watch') !== false) $icon = 'fa-stopwatch';

                    // Status Badge Logic
                    $badge_class = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                    $status_text = ucfirst($row['status']);
                    $border_accent = 'border-secondary';
                    
                    if ($row['status'] == 'active') {
                        $badge_class = 'bg-success bg-opacity-10 text-success border-success';
                        $border_accent = 'border-success';
                    } elseif ($row['status'] == 'redeemed') {
                        $badge_class = 'bg-dark bg-opacity-10 text-dark border-dark';
                        $border_accent = 'border-dark';
                    } elseif ($row['status'] == 'expired') {
                        $badge_class = 'bg-danger bg-opacity-10 text-danger border-danger';
                        $border_accent = 'border-danger';
                        $status_text = 'Foreclosed';
                    } elseif ($row['status'] == 'auctioned') {
                        $badge_class = 'bg-primary bg-opacity-10 text-primary border-primary';
                        $border_accent = 'border-primary';
                    }
                ?>

                <a href="view_details.php?id=<?php echo $row['transaction_id']; ?>" class="text-decoration-none text-dark">
                    <div class="card shadow-sm border-0 rounded-4 hover-lift transaction-card border-start border-4 <?php echo $border_accent; ?>">
                        <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                            
                            <div class="d-flex align-items-center mb-3 mb-md-0">
                                <div class="bg-light rounded-4 p-3 me-3 text-center d-flex justify-content-center align-items-center border" style="width: 65px; height: 65px;">
                                    <i class="fa-solid <?php echo $icon; ?> fs-3 text-muted"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h6>
                                    <div class="d-flex align-items-center text-muted" style="font-size: 0.8rem;">
                                        <span class="font-monospace bg-light border px-2 py-1 rounded me-2">PT# <?php echo $row['pt_number']; ?></span>
                                        <span><i class="fa-regular fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-md-end d-flex flex-row flex-md-column align-items-center align-items-md-end justify-content-between mt-2 mt-md-0">
                                <div class="mb-md-1">
                                    <small class="text-muted text-uppercase fw-bold d-none d-md-block" style="font-size: 0.65rem;">Principal</small>
                                    <h5 class="fw-bold mb-0">₱<?php echo number_format($row['principal_amount'], 2); ?></h5>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge <?php echo $badge_class; ?> border rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <i class="fa-solid fa-chevron-right ms-3 text-muted d-none d-md-inline"></i>
                                </div>
                            </div>

                        </div>
                    </div>
                </a>

            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 py-5">
            <div class="card-body text-center py-5">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                    <i class="fa-solid fa-file-invoice fa-3x text-muted opacity-50"></i>
                </div>
                <h5 class="fw-bold text-dark">No Transactions Found</h5>
                <p class="text-muted">
                    <?php echo (!empty($search) || $filter != 'all') ? 'We couldn\'t find any records matching your current filters.' : 'You have no transaction history yet.'; ?>
                </p>
                <?php if(!empty($search) || $filter != 'all'): ?>
                    <a href="transactions.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm mt-2">Clear Filters</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; background-color: #fcfcfc; }
    .transaction-card { transition: all 0.2s ease; }
    .transaction-card:active { transform: scale(0.99); }
    .form-control:focus, .form-select:focus { box-shadow: none !important; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>