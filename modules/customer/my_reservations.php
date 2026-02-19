<?php
// modules/customer/my_reservations.php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// 1. Get the Customer's Profile ID
$stmt_prof = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt_prof->bind_param("i", $_SESSION['account_id']);
$stmt_prof->execute();
$profile_id = $stmt_prof->get_result()->fetch_assoc()['profile_id'];

// 2. Fetch all their reservations
$sql = "SELECT sr.*, si.selling_price, i.device_type AS item_name, i.brand, i.model, i.device_type AS item_type 
        FROM shop_reservations sr
        JOIN shop_items si ON sr.shop_id = si.shop_id
        JOIN items i ON si.item_id = i.item_id
        WHERE sr.customer_profile_id = ?
        ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$reservations = $stmt->get_result();
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-bag-shopping me-2 text-primary"></i> My Shop Orders</h2>
                <p class="text-muted">Track the status of your reserved foreclosed items.</p>
            </div>
            <a href="shop.php" class="btn btn-outline-primary fw-bold rounded-pill">
                <i class="fa-solid fa-store me-2"></i> Browse More
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($reservations && $reservations->num_rows > 0): ?>
            <?php while($res = $reservations->fetch_assoc()): ?>
                
                <?php 
                    // Calculate remaining balance
                    $remaining_balance = $res['selling_price'] - $res['reservation_amount']; 
                    
                    // Deadline to claim (e.g., 3 days from approval)
                    // We use created_at here, but if you want to be exact, you'd add an 'approved_at' column later.
                    $deadline = date('M d, Y', strtotime($res['created_at'] . ' + 3 days'));
                ?>

                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        
                        <?php if($res['status'] == 'approved'): ?>
                            <div class="bg-success" style="height: 6px;"></div>
                        <?php elseif($res['status'] == 'pending'): ?>
                            <div class="bg-warning" style="height: 6px;"></div>
                        <?php elseif($res['status'] == 'rejected'): ?>
                            <div class="bg-danger" style="height: 6px;"></div>
                        <?php else: ?>
                            <div class="bg-secondary" style="height: 6px;"></div>
                        <?php endif; ?>

                        <div class="card-body p-4 row align-items-center">
                            
                            <div class="col-md-5 mb-3 mb-md-0 border-end">
                                <span class="badge bg-light text-dark border mb-2"><?php echo $res['item_type']; ?></span>
                                <h5 class="fw-bold text-dark mb-1"><?php echo $res['item_name']; ?></h5>
                                <small class="text-muted d-block mb-2"><?php echo $res['brand'] . ' ' . $res['model']; ?></small>
                                <small class="text-muted">Reserved on: <strong><?php echo date('M d, Y', strtotime($res['created_at'])); ?></strong></small>
                            </div>

                            <div class="col-md-4 mb-3 mb-md-0 border-end px-md-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Total Price:</small>
                                    <span class="fw-bold">₱<?php echo number_format($res['selling_price'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Downpayment Paid:</small>
                                    <span class="fw-bold text-success">- ₱<?php echo number_format($res['reservation_amount'], 2); ?></span>
                                </div>
                                <hr class="my-2 opacity-25">
                                <div class="d-flex justify-content-between">
                                    <small class="text-uppercase fw-bold text-dark">Balance to Pay:</small>
                                    <h5 class="fw-bold text-primary mb-0">₱<?php echo number_format($remaining_balance, 2); ?></h5>
                                </div>
                            </div>

                            <div class="col-md-3 text-md-center px-md-4">
                                
                                <?php if($res['status'] == 'pending'): ?>
                                    <h5 class="fw-bold text-warning mb-2"><i class="fa-solid fa-spinner fa-spin me-2"></i> Verifying</h5>
                                    <small class="text-muted d-block">We are currently verifying your GCash receipt (Ref: <?php echo $res['reference_number']; ?>). Please check back soon.</small>
                                
                                <?php elseif($res['status'] == 'approved'): ?>
                                    <h5 class="fw-bold text-success mb-2"><i class="fa-solid fa-circle-check me-2"></i> Approved!</h5>
                                    <small class="text-muted d-block mb-2">Your item is secured. Please visit the branch before <strong class="text-danger"><?php echo $deadline; ?></strong> to pay your balance.</small>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Ready to Claim</span>
                                
                                <?php elseif($res['status'] == 'rejected'): ?>
                                    <h5 class="fw-bold text-danger mb-2"><i class="fa-solid fa-circle-xmark me-2"></i> Rejected</h5>
                                    <small class="text-muted d-block">Your receipt was invalid or could not be verified. This reservation has been cancelled.</small>
                                
                                <?php elseif($res['status'] == 'claimed'): ?>
                                    <h5 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-bag-shopping me-2"></i> Claimed</h5>
                                    <small class="text-muted d-block">Thank you for your purchase!</small>
                                <?php endif; ?>

                            </div>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-receipt fa-4x text-muted opacity-25 mb-3"></i>
                <h4 class="fw-bold text-secondary">No Reservations Yet</h4>
                <p class="text-muted">You haven't reserved any items from our shop.</p>
                <a href="shop.php" class="btn btn-primary fw-bold mt-2">Go to Online Shop</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/customer_footer.php'; ?>