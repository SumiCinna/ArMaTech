<?php
// modules/admin/shop_management.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';


//  HANDLE EDIT PRICE SUBMISSION

if (isset($_POST['btn_edit_price'])) {
    $shop_id = intval($_POST['shop_id']);
    $new_price = floatval($_POST['new_price']);

    $update_sql = "UPDATE shop_items SET selling_price = ? WHERE shop_id = ?";
    $stmt_up = $conn->prepare($update_sql);
    $stmt_up->bind_param("di", $new_price, $shop_id);
    
    if ($stmt_up->execute()) {
        $msg = "Item price successfully updated!";
    } else {
        $error = "Failed to update price.";
    }
}


// 2. HANDLE REMOVE ITEM SUBMISSION

if (isset($_POST['btn_remove_item'])) {
    $shop_id = intval($_POST['shop_id']);

    // Deleting it from shop_items puts it back into the "Ready for Sale" page
    $delete_sql = "DELETE FROM shop_items WHERE shop_id = ?";
    $stmt_del = $conn->prepare($delete_sql);
    $stmt_del->bind_param("i", $shop_id);
    
    if ($stmt_del->execute()) {
        $msg = "Item successfully removed from the online shop.";
    } else {
        $error = "Failed to remove item. It might be tied to an existing reservation.";
    }
}


// 3. FETCH SHOP DATA

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

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-store me-2 text-primary"></i> Storefront Management</h3>
            <small class="text-muted">Manage items currently visible to customers in the Online Shop.</small>
        </div>
        <a href="ready_for_sale.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i> Add New Items
        </a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success">
                <small class="text-success text-uppercase fw-bold letter-spacing-1">Live / Available</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['available']; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning">
                <small class="text-warning text-uppercase fw-bold letter-spacing-1">Reserved</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['reserved']; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-secondary">
                <small class="text-secondary text-uppercase fw-bold letter-spacing-1">Sold</small>
                <h3 class="fw-bold mb-0"><?php echo $stats['sold']; ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Item (PT#)</th>
                            <th class="py-3 fw-bold">Date Published</th>
                            <th class="py-3 fw-bold">Selling Price</th>
                            <th class="py-3 fw-bold">Shop Status</th>
                            <th class="text-end pe-4 py-3 fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($shop_data) > 0): ?>
                            <?php foreach($shop_data as $row): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    <td class="ps-4 py-3">
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['item_name']; ?></h6>
                                        <small class="text-muted"><?php echo $row['brand'] . ' ' . $row['model']; ?> <span class="badge bg-light border text-dark ms-1">PT#<?php echo $row['pt_number']; ?></span></small>
                                    </td>
                                    <td class="py-3"><small class="text-muted"><?php echo date('M d, Y', strtotime($row['date_published'])); ?></small></td>
                                    <td class="py-3 fw-bold text-success fs-5">₱<?php echo number_format($row['selling_price'], 2); ?></td>
                                    <td class="py-3">
                                        <?php if($row['shop_status'] == 'available'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 rounded-pill">Available</span>
                                        <?php elseif($row['shop_status'] == 'reserved'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-3 rounded-pill">Reserved</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 rounded-pill">Sold</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        
                                        <?php if($row['shop_status'] == 'available'): ?>
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <button class="btn btn-sm btn-light border-end text-primary fw-bold px-3" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['shop_id']; ?>" title="Edit Price">
                                                    <i class="fa-solid fa-pen me-1"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-light text-danger fw-bold px-3" data-bs-toggle="modal" data-bs-target="#removeModal<?php echo $row['shop_id']; ?>" title="Remove from Shop">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill"><i class="fa-solid fa-lock me-1"></i> Locked</span>
                                        <?php endif; ?>

                                    </td>
                                </tr>

                                <div class="modal fade" id="editModal<?php echo $row['shop_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <div class="modal-header border-0 bg-light">
                                                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Selling Price</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body p-4 text-start">
                                                    <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                    
                                                    <p class="text-muted mb-4">Update the selling price for <strong><?php echo $row['brand'] . ' ' . $row['model']; ?></strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted text-uppercase">New Selling Price</label>
                                                        <div class="input-group input-group-lg">
                                                            <span class="input-group-text bg-light fw-bold text-dark">₱</span>
                                                            <input type="number" name="new_price" class="form-control fw-bold" required min="1" step="0.01" value="<?php echo $row['selling_price']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 bg-light">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btn_edit_price" class="btn btn-primary fw-bold px-4">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="removeModal<?php echo $row['shop_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <div class="modal-header border-0 bg-danger text-white">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Remove Item</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body p-4 text-center">
                                                    <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                    
                                                    <i class="fa-solid fa-store-slash fa-4x text-danger opacity-50 mb-3"></i>
                                                    <h5 class="fw-bold text-dark">Are you sure?</h5>
                                                    <p class="text-muted mb-0">You are about to remove <strong><?php echo $row['brand'] . ' ' . $row['model']; ?></strong> from the online shop.</p>
                                                    <small class="text-muted mt-2 d-block">This item will be returned to the <strong>Ready for Sale</strong> list.</small>
                                                </div>
                                                <div class="modal-footer border-0 bg-light d-flex justify-content-center">
                                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btn_remove_item" class="btn btn-danger fw-bold px-4">Yes, Remove it</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-store-slash fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">Store is Empty</h5>
                                        <p class="text-muted small">You haven't published any items to the online shop yet.</p>
                                        <a href="ready_for_sale.php" class="btn btn-sm btn-outline-primary rounded-pill px-4 mt-2">Go to Ready for Sale</a>
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
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>