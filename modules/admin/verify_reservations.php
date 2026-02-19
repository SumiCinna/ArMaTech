<?php
// modules/admin/verify_reservations.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Fetch all Pending and Approved Reservations
$sql = "SELECT sr.*, 
               p.first_name, p.last_name, p.contact_number, 
               si.selling_price, 
               i.device_type AS item_name, i.brand, i.model 
        FROM shop_reservations sr
        JOIN shop_items si ON sr.shop_id = si.shop_id
        JOIN items i ON si.item_id = i.item_id
        JOIN profiles p ON sr.customer_profile_id = p.profile_id
        ORDER BY sr.created_at DESC";

$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Reservation Verification</h3>
            <small class="text-muted">Review GCash/Bank receipts submitted by customers for online shop items.</small>
        </div>
        <a href="shop_management.php" class="btn btn-outline-secondary fw-bold">
            <i class="fa-solid fa-store me-2"></i> Back to Shop
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
                            <th class="ps-4">Date Submitted</th>
                            <th>Customer Info</th>
                            <th>Reserved Item</th>
                            <th>Payment Details</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <small class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i> <?php echo $row['contact_number']; ?></small>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['item_name']; ?></h6>
                                        <small class="text-muted">Price: ₱<?php echo number_format($row['selling_price'], 2); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success mb-1">
                                            DP: ₱<?php echo number_format($row['reservation_amount'], 2); ?>
                                        </span><br>
                                        <small class="text-muted font-monospace">Ref: <?php echo $row['reference_number']; ?></small>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark px-3 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Pending Review</span>
                                        <?php elseif($row['status'] == 'approved'): ?>
                                            <span class="badge bg-primary px-3 rounded-pill"><i class="fa-solid fa-check me-1"></i> Approved</span>
                                        <?php elseif($row['status'] == 'rejected'): ?>
                                            <span class="badge bg-danger px-3 rounded-pill"><i class="fa-solid fa-xmark me-1"></i> Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 rounded-pill"><?php echo ucfirst($row['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $row['reservation_id']; ?>">
                                            <i class="fa-solid fa-image me-1"></i> View Receipt
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="receiptModal<?php echo $row['reservation_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <div class="modal-header bg-light border-0">
                                                <h5 class="modal-title fw-bold">Verification Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-0 text-center bg-dark">
                                                <img src="../../assets/receipts/<?php echo $row['receipt_image']; ?>" class="img-fluid" alt="Proof of Payment" style="max-height: 500px; object-fit: contain;">
                                            </div>
                                            <div class="modal-footer border-0 bg-light d-flex justify-content-between">
                                                <div>
                                                    <small class="text-muted d-block">Reference Number:</small>
                                                    <span class="fw-bold font-monospace fs-5 text-dark"><?php echo $row['reference_number']; ?></span>
                                                </div>
                                                
                                                <?php if($row['status'] == 'pending'): ?>
                                                    <div>
                                                        <form action="../../core/process_verify_reservation.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                            <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                                            <button type="submit" name="action" value="reject" class="btn btn-outline-danger fw-bold me-2">Reject</button>
                                                            <button type="submit" name="action" value="approve" class="btn btn-success fw-bold">Approve</button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Processed</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No reservations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>