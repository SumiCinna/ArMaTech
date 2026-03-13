<?php
// modules/customer/shop.php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// 1. Handle Search and Filters safely
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Base Query
$sql = "SELECT s.shop_id, s.selling_price, s.date_published,
               i.item_id, i.device_type AS item_name, i.brand, i.model, i.device_type AS item_type, i.inclusions AS item_description 
        FROM shop_items s
        JOIN items i ON s.item_id = i.item_id
        WHERE s.shop_status = 'available'";

$params = [];
$types = "";

// Append Search Filter
if (!empty($search)) {
    $sql .= " AND (i.brand LIKE ? OR i.model LIKE ? OR i.device_type LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Append Category Filter
if (!empty($category)) {
    $sql .= " AND i.device_type = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY s.date_published DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique categories for the filter pills
$cat_sql = "SELECT DISTINCT i.device_type FROM shop_items s JOIN items i ON s.item_id = i.item_id WHERE s.shop_status = 'available'";
$cat_res = $conn->query($cat_sql);
$categories = [];
while($c = $cat_res->fetch_assoc()) {
    $categories[] = $c['device_type'];
}
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="p-4 p-md-5 text-white rounded-4 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);">
                <i class="fa-solid fa-tags position-absolute opacity-10" style="font-size: 15rem; right: -20px; top: -30px;"></i>
                <div class="position-relative z-index-1">
                    <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill fw-bold text-uppercase" style="letter-spacing: 1px;">Verified Authentic</span>
                    <h1 class="fw-bold display-5 mb-2">ArMaTech Deals</h1>
                    <p class="lead mb-0 opacity-75" style="max-width: 600px;">Browse our collection of high-quality foreclosed items. Reserve online with just a 10% downpayment and claim in-store!</p>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row align-items-center mb-4 g-3">
        <div class="col-lg-6">
            <form method="GET" action="shop.php" class="d-flex w-100">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-4">
                        <i class="fa-solid fa-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-0 shadow-none py-2" placeholder="Search brands, models..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($search) || !empty($category)): ?>
                        <a href="shop.php" class="btn btn-light text-muted border-0 d-flex align-items-center"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-primary px-4 fw-bold" type="submit">Search</button>
                </div>
                <?php if(!empty($category)): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <?php endif; ?>
            </form>
        </div>
        <div class="col-lg-6 d-flex justify-content-lg-end gap-2 overflow-auto custom-scrollbar pb-2 pb-lg-0">
            <a href="shop.php" class="btn rounded-pill px-3 fw-bold text-nowrap <?php echo empty($category) ? 'btn-dark' : 'btn-light border text-muted'; ?>">All Items</a>
            <?php foreach($categories as $cat): ?>
                <a href="shop.php?category=<?php echo urlencode($cat); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   class="btn rounded-pill px-3 fw-bold text-nowrap <?php echo ($category == $cat) ? 'btn-dark' : 'btn-light border text-muted'; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">
                <?php echo !empty($search) ? 'Search Results' : (!empty($category) ? htmlspecialchars($category) . 's' : 'Recently Added'); ?>
            </h5>
            <span class="text-muted small"><?php echo $result->num_rows; ?> Items Available</span>
        </div>
        <a href="my_reservations.php" class="btn btn-outline-success fw-bold rounded-pill shadow-sm">
            <i class="fa-solid fa-bag-shopping me-2"></i> My Reservations
        </a>
    </div>

    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($item = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden product-card">
                        
                        <div class="bg-light d-flex align-items-center justify-content-center position-relative" style="height: 200px;">
                            <span class="badge bg-danger position-absolute top-0 start-0 m-3 shadow-sm"><i class="fa-solid fa-fire me-1"></i> Hot Deal</span>
                            
                            <?php 
                                $icon = 'fa-box-open';
                                $cat_lower = strtolower($item['item_type']);
                                if (strpos($cat_lower, 'smartphone') !== false || strpos($cat_lower, 'phone') !== false) $icon = 'fa-mobile-screen-button';
                                elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
                                elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
                                elseif (strpos($cat_lower, 'watch') !== false) $icon = 'fa-stopwatch';
                                elseif (strpos($cat_lower, 'camera') !== false) $icon = 'fa-camera';
                                elseif (strpos($cat_lower, 'console') !== false) $icon = 'fa-gamepad';
                            ?>
                            <i class="fa-solid <?php echo $icon; ?> text-secondary opacity-25" style="font-size: 5rem;"></i>
                        </div>

                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;"><?php echo $item['item_type']; ?></span>
                                <span class="text-muted" style="font-size: 0.7rem;"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d', strtotime($item['date_published'])); ?></span>
                            </div>
                            
                            <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?php echo $item['brand'] . ' ' . $item['model']; ?>">
                                <?php echo $item['brand'] . ' ' . $item['model']; ?>
                            </h6>
                            
                            <p class="text-muted small mb-3 text-truncate">
                                <?php echo empty($item['item_description']) ? 'Unit Only' : htmlspecialchars($item['item_description']); ?>
                            </p>
                            
                            <div class="mt-auto mb-3 border-top pt-3">
                                <span class="text-muted small d-block mb-1">Full Price</span>
                                <h4 class="fw-bold text-success mb-0">₱<?php echo number_format($item['selling_price'], 2); ?></h4>
                            </div>
                            
                            <button class="btn btn-dark fw-bold rounded-pill w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#reserveModal<?php echo $item['shop_id']; ?>">
                                Reserve for 10% DP
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="reserveModal<?php echo $item['shop_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header bg-dark text-white border-0 p-4">
                                <div>
                                    <small class="text-uppercase text-white-50 fw-bold ls-1" style="font-size: 0.7rem;">Checkout Summary</small>
                                    <h5 class="modal-title fw-bold mb-0">Secure Your Item</h5>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            
                            <form action="../../core/process_reservation.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body p-4 text-start bg-light">
                                    <input type="hidden" name="shop_id" value="<?php echo $item['shop_id']; ?>">
                                    <?php $downpayment = $item['selling_price'] * 0.10; ?>
                                    <input type="hidden" name="reservation_amount" value="<?php echo $downpayment; ?>">

                                    <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fa-solid <?php echo $icon; ?> fa-2x text-secondary"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0"><?php echo $item['brand'] . ' ' . $item['model']; ?></h6>
                                                <small class="text-muted"><?php echo $item['item_type']; ?></small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Total Selling Price</span>
                                            <span class="fw-bold">₱<?php echo number_format($item['selling_price'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Required Deposit Rate</span>
                                            <span class="fw-bold">10%</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <span class="fw-bold text-dark">Amount to Pay Now</span>
                                            <h4 class="fw-bold text-primary mb-0">₱<?php echo number_format($downpayment, 2); ?></h4>
                                        </div>
                                    </div>

                                    <div class="alert alert-primary border-0 bg-primary bg-opacity-10 d-flex align-items-start mb-4">
                                        <i class="fa-solid fa-mobile-screen-button fs-4 me-3 text-primary mt-1"></i>
                                        <div class="small text-dark">
                                            <strong>Send Payment via GCash/Maya</strong><br>
                                            Send exactly <strong>₱<?php echo number_format($downpayment, 2); ?></strong> to <strong class="text-primary">0912-345-6789</strong>. Save the screenshot and reference number.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Reference Number</label>
                                        <input type="text" name="reference_number" class="form-control form-control-lg border-0 shadow-sm rounded-3 font-monospace" placeholder="e.g. 10023948572" required>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Proof of Payment</label>
                                        <input type="file" name="receipt_image" class="form-control form-control-lg border-0 shadow-sm rounded-3" accept="image/png, image/jpeg, image/jpg" required>
                                        <div class="form-text text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-circle-info me-1"></i>Accepted formats: JPG, PNG. Max size: 2MB.</div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-4 pt-0 bg-light">
                                    <button type="button" class="btn btn-light border fw-bold w-100 mb-2 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="btn_reserve" class="btn btn-primary fw-bold w-100 rounded-pill shadow-sm py-2">
                                        <i class="fa-solid fa-lock me-2"></i> Submit Reservation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 my-5">
                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-4" style="width: 100px; height: 100px;">
                    <i class="fa-solid fa-store-slash fa-3x text-muted opacity-50"></i>
                </div>
                <h4 class="fw-bold text-dark">No items found</h4>
                <p class="text-muted">
                    <?php echo (!empty($search) || !empty($category)) ? 'We couldn\'t find any items matching your filters. Try clearing them.' : 'Check back later for great deals on foreclosed items!'; ?>
                </p>
                <?php if(!empty($search) || !empty($category)): ?>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 fw-bold mt-2">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .product-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .input-group .form-control:focus { border-color: inherit; -webkit-box-shadow: none; box-shadow: none; }
    .custom-scrollbar::-webkit-scrollbar { height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>