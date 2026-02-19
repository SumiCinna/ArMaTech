<?php
// modules/admin/ready_for_sale.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Fetch items that are 'expired' but NOT YET in the shop_items table
$sql = "SELECT t.transaction_id, t.pt_number, t.principal_amount, 
               i.item_id, i.device_type AS item_name, i.brand, i.model, i.device_type AS item_type 
        FROM transactions t
        JOIN items i ON t.transaction_id = i.transaction_id
        LEFT JOIN shop_items s ON t.transaction_id = s.transaction_id
        WHERE t.status = 'expired' AND s.shop_id IS NULL
        ORDER BY t.expiry_date ASC"; // Assuming you have expiry_date based on your schema

$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-tags me-2 text-danger"></i> Ready for Sale</h3>
            <small class="text-muted">Foreclosed items pending pricing and publication to the online shop.</small>
        </div>
        <a href="shop_management.php" class="btn btn-primary fw-bold">
            <i class="fa-solid fa-store me-2"></i> View Live Shop
        </a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Item Details</th>
                            <th>Category</th>
                            <th>Original Principal</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['item_name']; ?></h6>
                                        <small class="text-muted"><?php echo $row['brand'] . ' ' . $row['model']; ?> (PT#: <?php echo $row['pt_number']; ?>)</small>
                                    </td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo $row['item_type']; ?></span></td>
                                    <td class="fw-bold text-danger">₱<?php echo number_format($row['principal_amount'], 2); ?></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-success fw-bold px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#publishModal<?php echo $row['transaction_id']; ?>">
                                            <i class="fa-solid fa-upload me-1"></i> Publish
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="publishModal<?php echo $row['transaction_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-light border-0">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-store me-2 text-primary"></i> Publish to Shop</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="../../core/publish_shop_item.php" method="POST">
                                                <div class="modal-body p-4 text-start">
                                                    <p class="text-muted mb-4">You are publishing <strong><?php echo $row['item_name']; ?></strong> to the public storefront.</p>
                                                    
                                                    <input type="hidden" name="transaction_id" value="<?php echo $row['transaction_id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small text-muted text-uppercase">Original Principal (Debt)</label>
                                                        <input type="text" class="form-control bg-light text-muted" value="₱<?php echo number_format($row['principal_amount'], 2); ?>" readonly>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small text-primary text-uppercase">Set Selling Price</label>
                                                        <div class="input-group input-group-lg">
                                                            <span class="input-group-text bg-light fw-bold text-dark">₱</span>
                                                            <input type="number" name="selling_price" class="form-control fw-bold" placeholder="0.00" required min="<?php echo $row['principal_amount']; ?>" step="0.01">
                                                        </div>
                                                        <div class="form-text">Recommended to set higher than the principal to ensure profit.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 bg-light">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btn_publish" class="btn btn-primary fw-bold px-4">Publish Item</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No expired items waiting to be published.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>