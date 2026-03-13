<?php
// modules/admin/inventory.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. FILTER LOGIC
$filter_status = $_GET['status'] ?? 'all';
$filter_type   = $_GET['type'] ?? 'all';
$search_query  = $_GET['search'] ?? '';

// 2. BUILD SQL QUERY
$sql = "SELECT i.*, t.pt_number, t.status, t.principal_amount, t.date_pawned, t.transaction_id 
        FROM items i
        JOIN transactions t ON i.transaction_id = t.transaction_id
        WHERE 1=1";

// Apply Filters
if ($filter_status != 'all') {
    $sql .= " AND t.status = '$filter_status'";
}
if ($filter_type != 'all') {
    $sql .= " AND i.device_type = '$filter_type'";
}
if (!empty($search_query)) {
    $term = "%$search_query%";
    $sql .= " AND (i.device_type LIKE '$term' OR i.brand LIKE '$term' OR i.serial_number LIKE '$term' OR t.pt_number LIKE '$term')";
}

$sql .= " ORDER BY t.date_pawned DESC";
$result = $conn->query($sql);

// 3. GET STATS (Counts for Top Cards)
$stats_sql = "SELECT 
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_count,
                COUNT(CASE WHEN status = 'redeemed' THEN 1 END) as redeemed_count,
                COUNT(CASE WHEN status = 'auctioned' THEN 1 END) as auctioned_count
              FROM transactions";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Inventory Masterlist</h3>
            <p class="text-muted small mb-0">Manage items in vault and foreclosed assets.</p>
        </div>
        <button class="btn btn-white border shadow-sm fw-bold text-dark px-3" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> Print List
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success h-100 rounded-4 hover-lift">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Active Collateral</small>
                        <h2 class="fw-bold mb-0 text-success"><?php echo $stats['active_count']; ?></h2>
                        <small class="text-success fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-vault me-1"></i> Currently in Vault</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center"><i class="fa-solid fa-lock fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-danger h-100 rounded-4 hover-lift">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Foreclosed / Expired</small>
                        <h2 class="fw-bold mb-0 text-danger"><?php echo $stats['expired_count']; ?></h2>
                        <small class="text-danger fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-gavel me-1"></i> Ready for Liquidation</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle d-flex align-items-center justify-content-center"><i class="fa-solid fa-triangle-exclamation fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-secondary h-100 rounded-4 hover-lift">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Redeemed History</small>
                        <h2 class="fw-bold mb-0 text-secondary"><?php echo $stats['redeemed_count']; ?></h2>
                        <small class="text-muted fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-rotate-left me-1"></i> Returned to Owner</small>
                    </div>
                    <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-circle d-flex align-items-center justify-content-center"><i class="fa-solid fa-box-open fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary h-100 rounded-4 hover-lift">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Auctioned / Sold</small>
                        <h2 class="fw-bold mb-0 text-primary"><?php echo $stats['auctioned_count']; ?></h2>
                        <small class="text-primary fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-gavel me-1"></i> Sold to Public</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center"><i class="fa-solid fa-hand-holding-dollar fs-4"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 bg-white rounded-4 overflow-hidden no-print">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Item Status</label>
                    <select name="status" class="form-select bg-light border-0 shadow-none" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>Active (In Vault)</option>
                        <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>Expired (Foreclosed)</option>
                        <option value="auctioned" <?php echo ($filter_status == 'auctioned') ? 'selected' : ''; ?>>Auctioned (Sold)</option>
                        <option value="redeemed" <?php echo ($filter_status == 'redeemed') ? 'selected' : ''; ?>>Redeemed (Returned)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Category</label>
                    <select name="type" class="form-select bg-light border-0 shadow-none" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_type == 'all') ? 'selected' : ''; ?>>All Types</option>
                        <option value="Smartphone" <?php echo ($filter_type == 'Smartphone') ? 'selected' : ''; ?>>Smartphone</option>
                        <option value="Laptop" <?php echo ($filter_type == 'Laptop') ? 'selected' : ''; ?>>Laptop</option>
                        <option value="Tablet" <?php echo ($filter_type == 'Tablet') ? 'selected' : ''; ?>>Tablet</option>
                        <option value="Smartwatch" <?php echo ($filter_type == 'Smartwatch') ? 'selected' : ''; ?>>Smartwatch</option>
                        <option value="Gaming Console" <?php echo ($filter_type == 'Gaming Console') ? 'selected' : ''; ?>>Gaming Console</option>
                        <option value="Camera" <?php echo ($filter_type == 'Camera') ? 'selected' : ''; ?>>Camera</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Search Inventory</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0 shadow-none" placeholder="Search name, brand, or serial..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="inventory.php" class="btn btn-light border w-100 fw-bold shadow-sm">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Item Details</th>
                            <th class="py-3 fw-bold">Category</th>
                            <th class="py-3 fw-bold">Serial / ID</th>
                            <th class="py-3 fw-bold">Loan Status</th>
                            <th class="py-3 fw-bold">Date In</th>
                            <th class="text-end pe-4 py-3 fw-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center me-3 shadow-sm flex-shrink-0" style="width: 45px; height: 45px;">
                                                <?php 
                                                    $dt = $row['device_type'];
                                                    if ($dt == 'Smartphone' || $dt == 'Tablet') echo '<i class="fa-solid fa-mobile-screen"></i>';
                                                    elseif ($dt == 'Laptop') echo '<i class="fa-solid fa-laptop"></i>';
                                                    elseif ($dt == 'Camera') echo '<i class="fa-solid fa-camera"></i>';
                                                    elseif ($dt == 'Gaming Console') echo '<i class="fa-solid fa-gamepad"></i>';
                                                    elseif ($dt == 'Smartwatch') echo '<i class="fa-solid fa-clock"></i>';
                                                    else echo '<i class="fa-solid fa-box"></i>';
                                                ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo $row['device_type']; ?></h6>
                                                <small class="text-muted"><?php echo $row['brand'] . ' ' . $row['model']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-1 rounded-pill"><?php echo $row['device_type']; ?></span>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex flex-column">
                                            <small class="font-monospace text-dark"><?php echo $row['serial_number'] ?: 'N/A'; ?></small>
                                            <small class="text-muted" style="font-size: 0.7em;">PT#: <?php echo $row['pt_number']; ?></small>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <?php if($row['status'] == 'active'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-lock me-1"></i> In Vault</span>
                                        <?php elseif($row['status'] == 'expired'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Foreclosed</span>
                                        <?php elseif($row['status'] == 'auctioned'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-gavel me-1"></i> Auctioned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-rotate-left me-1"></i> Redeemed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></span>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <a href="view_transaction_details.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm" title="View Details">
                                            View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-box-open fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Items Found</h5>
                                        <p class="text-muted small">We couldn't find any inventory matching your current filters.</p>
                                        <a href="inventory.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 mt-2">Clear Filters</a>
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
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
    .custom-table tbody tr { transition: background-color 0.2s; }
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>