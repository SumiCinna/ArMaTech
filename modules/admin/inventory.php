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

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Inventory Masterlist</h3>
            <small class="text-muted">Manage items in vault and foreclosed assets.</small>
        </div>
        <button class="btn btn-outline-dark btn-sm fw-bold" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> Print List
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Active Collateral</small>
                        <h2 class="fw-bold mb-0 text-success"><?php echo $stats['active_count']; ?></h2>
                        <small class="text-success"><i class="fa-solid fa-vault me-1"></i> Currently in Vault</small>
                    </div>
                    <i class="fa-solid fa-lock fa-2x text-success opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-danger h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Foreclosed / Expired</small>
                        <h2 class="fw-bold mb-0 text-danger"><?php echo $stats['expired_count']; ?></h2>
                        <small class="text-danger"><i class="fa-solid fa-gavel me-1"></i> Ready for Liquidation</small>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation fa-2x text-danger opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-secondary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Redeemed History</small>
                        <h2 class="fw-bold mb-0 text-secondary"><?php echo $stats['redeemed_count']; ?></h2>
                        <small class="text-muted"><i class="fa-solid fa-rotate-left me-1"></i> Returned to Owner</small>
                    </div>
                    <i class="fa-solid fa-box-open fa-2x text-secondary opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Auctioned / Sold</small>
                        <h2 class="fw-bold mb-0 text-primary"><?php echo $stats['auctioned_count']; ?></h2>
                        <small class="text-primary"><i class="fa-solid fa-gavel me-1"></i> Sold to Public</small>
                    </div>
                    <i class="fa-solid fa-hand-holding-dollar fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 bg-white">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Item Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>Active (In Vault)</option>
                        <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>Expired (Foreclosed)</option>
                        <option value="auctioned" <?php echo ($filter_status == 'auctioned') ? 'selected' : ''; ?>>Auctioned (Sold)</option>
                        <option value="redeemed" <?php echo ($filter_status == 'redeemed') ? 'selected' : ''; ?>>Redeemed (Returned)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Category</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
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
                    <label class="small fw-bold text-muted">Search Item</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name, brand, or serial..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="inventory.php" class="btn btn-sm btn-light border w-100 fw-bold mt-4">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Item Details</th>
                            <th>Category</th>
                            <th>Serial / ID</th>
                            <th>Loan Status</th>
                            <th>Date In</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center me-3 border" style="width: 40px; height: 40px;">
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
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo $row['device_type']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="font-monospace text-dark"><?php echo $row['serial_number'] ?: 'N/A'; ?></small>
                                            <small class="text-muted" style="font-size: 0.7em;">PT#: <?php echo $row['pt_number']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'active'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">In Vault</span>
                                        <?php elseif($row['status'] == 'expired'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Foreclosed</span>
                                        <?php elseif($row['status'] == 'auctioned'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Auctioned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Redeemed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="view_transaction_details.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-light border text-primary" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open fa-3x mb-3 opacity-25"></i><br>
                                    No items found matching your filters.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>