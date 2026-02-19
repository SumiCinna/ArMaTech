<?php
// modules/customer/shop.php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// Fetch AVAILABLE items from the shop
$sql = "SELECT s.shop_id, s.selling_price, s.date_published,
               i.item_id, i.device_type AS item_name, i.brand, i.model, i.device_type AS item_type, i.inclusions AS item_description 
        FROM shop_items s
        JOIN items i ON s.item_id = i.item_id
        WHERE s.shop_status = 'available'
        ORDER BY s.date_published DESC";

$result = $conn->query($sql);
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="p-5 text-white rounded-4 shadow-sm" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
                <h1 class="fw-bold"><i class="fa-solid fa-store me-2"></i> ArMaTech Deals</h1>
                <p class="lead mb-0">Browse our collection of highly discounted, quality foreclosed items. Reserve online and claim in-store!</p>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3">
            <i class="fa-solid fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-0">Recently Added</h5>
            <span class="text-muted small"><?php echo $result->num_rows; ?> Items Available</span>
        </div>
        <a href="my_reservations.php" class="btn btn-outline-success fw-bold rounded-pill">
            <i class="fa-solid fa-bag-shopping me-2"></i> My Reservations
        </a>
    </div>

    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($item = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden product-card">
                        
                        <div class="bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 180px;">
                            <?php 
                                if ($item['item_type'] == 'Gadget') echo '<i class="fa-solid fa-laptop text-secondary opacity-50" style="font-size: 4rem;"></i>';
                                elseif ($item['item_type'] == 'Jewelry') echo '<i class="fa-regular fa-gem text-secondary opacity-50" style="font-size: 4rem;"></i>';
                                elseif ($item['item_type'] == 'Watch') echo '<i class="fa-regular fa-clock text-secondary opacity-50" style="font-size: 4rem;"></i>';
                                else echo '<i class="fa-solid fa-box-open text-secondary opacity-50" style="font-size: 4rem;"></i>';
                            ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary mb-2 align-self-start border"><?php echo $item['item_type']; ?></span>
                            
                            <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo $item['item_name']; ?></h6>
                            <small class="text-muted mb-3 text-truncate d-block"><?php echo $item['brand'] . ' ' . $item['model']; ?></small>
                            
                            <h4 class="fw-bold text-success mt-auto mb-3">₱<?php echo number_format($item['selling_price'], 2); ?></h4>
                            
                            <button class="btn btn-primary fw-bold rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#reserveModal<?php echo $item['shop_id']; ?>">
                                Reserve Item
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="reserveModal<?php echo $item['shop_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow rounded-4">
                            <div class="modal-header border-0 bg-light">
                                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar me-2 text-primary"></i> Secure Reservation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            
                            <form action="../../core/process_reservation.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body p-4 text-start">
                                    <input type="hidden" name="shop_id" value="<?php echo $item['shop_id']; ?>">
                                    
                                    <?php 
                                        // Calculate 10% Downpayment
                                        $downpayment = $item['selling_price'] * 0.10; 
                                    ?>
                                    <input type="hidden" name="reservation_amount" value="<?php echo $downpayment; ?>">

                                    <div class="text-center mb-4">
                                        <h5 class="fw-bold"><?php echo $item['item_name']; ?></h5>
                                        <p class="text-muted mb-1">Total Price: ₱<?php echo number_format($item['selling_price'], 2); ?></p>
                                        <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded mt-2">
                                            <small class="text-success text-uppercase fw-bold d-block">Required 10% Downpayment</small>
                                            <h3 class="fw-bold text-success mb-0">₱<?php echo number_format($downpayment, 2); ?></h3>
                                        </div>
                                    </div>

                                    <div class="alert alert-info border-0 bg-info bg-opacity-10 small">
                                        <strong><i class="fa-solid fa-building-columns me-1"></i> Payment Instructions:</strong><br>
                                        Send the downpayment amount to our official GCash/Maya number: <strong>0912-345-6789</strong>. Upload the screenshot below to secure your item.
                                    </div>
                                    
                                    <div class="mb-3 mt-3">
                                        <label class="form-label small fw-bold text-muted">GCash / Bank Reference Number</label>
                                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. 10023948572" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Upload Screenshot of Receipt</label>
                                        <input type="file" name="receipt_image" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
                                        <div class="form-text text-muted" style="font-size: 0.7rem;">Accepted formats: JPG, PNG. Max size: 2MB.</div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="btn_reserve" class="btn btn-primary fw-bold px-4">Submit Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-store-slash fa-4x text-muted opacity-25 mb-3"></i>
                <h4 class="fw-bold text-secondary">No items available yet</h4>
                <p class="text-muted">Check back later for great deals on foreclosed items!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .product-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>