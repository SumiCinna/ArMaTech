<?php
// modules/admin/shop_management.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Fetch all items currently in the shop_items table
$sql = "SELECT s.*, t.pt_number, i.device_type AS item_name, i.brand, i.model 
        FROM shop_items s
        JOIN transactions t ON s.transaction_id = t.transaction_id
        JOIN items i ON s.item_id = i.item_id
        ORDER BY s.date_published DESC";
$result = $conn->query($sql);

// Count Stats
$stats = ['available' => 0, 'reserved' => 0, 'sold' => 0];
$shop_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shop_data[] = $row;
        $stats[$row['shop_status']]++;
    }
}
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-store me-2 text-primary"></i> Storefront Management</h3>
            <small class="text-muted">Manage items currently visible to customers in the Online Shop.</small>
        </div>
        <a href="ready_for_sale.php" class="btn btn-outline-dark fw-bold">
            <i class="fa-solid fa-plus me-2"></i> Add New Items
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success">
                <small class="text-success text-uppercase fw-bold">Live / Available</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['available']; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning">
                <small class="text-warning text-uppercase fw-bold">Reserved</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['reserved']; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-secondary">
                <small class="text-secondary text-uppercase fw-bold">Sold</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['sold']; ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Item (PT#)</th>
                            <th>Date Published</th>
                            <th>Selling Price</th>
                            <th>Shop Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($shop_data) > 0): ?>
                            <?php foreach($shop_data as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['item_name']; ?></h6>
                                        <small class="text-muted"><?php echo $row['brand'] . ' ' . $row['model']; ?> (PT#<?php echo $row['pt_number']; ?>)</small>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($row['date_published'])); ?></small></td>
                                    <td class="fw-bold text-success fs-5">₱<?php echo number_format($row['selling_price'], 2); ?></td>
                                    <td>
                                        <?php if($row['shop_status'] == 'available'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">Available</span>
                                        <?php elseif($row['shop_status'] == 'reserved'): ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark px-3 rounded-pill">Reserved</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 rounded-pill">Sold</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if($row['shop_status'] !== 'sold'): ?>
                                            <button class="btn btn-sm btn-light border text-primary me-1" title="Edit Price"><i class="fa-solid fa-pen"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger" title="Remove from Shop"><i class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                            <span class="small text-muted fst-italic">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">The shop is currently empty.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>