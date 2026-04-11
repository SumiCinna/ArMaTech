<?php
// modules/customer/my_reservations.php
require_once '../../config/database.php';
require_once '../../core/reservation_expiry.php';
include_once '../../includes/customer_header.php';

// Run auto-forfeiture before fetching reservation list.
run_reservation_expiry($conn);


// ═══════════════════════════════════════════════════════════════
//  GET CUSTOMER PROFILE ID
// ═══════════════════════════════════════════════════════════════
$stmt_prof = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt_prof->bind_param("i", $_SESSION['account_id']);
$stmt_prof->execute();
$profile_id = $stmt_prof->get_result()->fetch_assoc()['profile_id'];


// ═══════════════════════════════════════════════════════════════
//  FETCH ALL RESERVATIONS (including forfeited)
// ═══════════════════════════════════════════════════════════════
$sql = "SELECT sr.*, si.selling_price,
               i.device_type AS item_name, i.brand, i.model,
               i.device_type AS item_type
        FROM   shop_reservations sr
        JOIN   shop_items si ON sr.shop_id  = si.shop_id
        JOIN   items i       ON si.item_id  = i.item_id
        WHERE  sr.customer_profile_id = ?
        ORDER  BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$reservations = $stmt->get_result();
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-bag-shopping me-2 text-primary"></i> My Reservations
                </h2>
                <p class="text-muted small mb-0">Track the status of your online store orders.</p>
            </div>
            <a href="shop.php" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm hover-lift">
                <i class="fa-solid fa-store me-2"></i> Browse Shop
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($reservations && $reservations->num_rows > 0): ?>
            <?php while ($res = $reservations->fetch_assoc()): ?>

                <?php
                    // ── Financial ────────────────────────────────────────────
                    $remaining_balance = $res['selling_price'] - $res['reservation_amount'];
                    $deadline          = date('F d, Y', strtotime($res['created_at'] . ' + 3 days'));

                    // ── Dynamic device icon ───────────────────────────────────
                    $icon      = 'fa-box-open';
                    $cat_lower = strtolower($res['item_type']);
                    if (strpos($cat_lower, 'phone') !== false || strpos($cat_lower, 'smartphone') !== false)
                        $icon = 'fa-mobile-screen-button';
                    elseif (strpos($cat_lower, 'laptop') !== false)
                        $icon = 'fa-laptop';
                    elseif (strpos($cat_lower, 'tablet') !== false)
                        $icon = 'fa-tablet-screen-button';
                    elseif (strpos($cat_lower, 'watch') !== false)
                        $icon = 'fa-stopwatch';

                    // ── Status UI ─────────────────────────────────────────────
                    $s       = $res['status'];
                    $s_color = 'secondary';
                    $s_icon  = 'fa-circle-info';
                    $s_text  = ucfirst($s);

                    if ($s === 'pending_payment') {
                        $s_color = 'secondary';
                        $s_icon  = 'fa-clock';
                        $s_text  = 'Awaiting Payment';
                    } elseif ($s === 'pending_verification' || $s === 'pending') {
                        $s_color = 'warning';
                        $s_icon  = 'fa-circle-notch fa-spin';
                        $s_text  = 'Verifying Payment';
                    } elseif ($s === 'approved') {
                        $s_color = 'success';
                        $s_icon  = 'fa-check-circle';
                        $s_text  = 'Ready for Pickup';
                    } elseif ($s === 'rejected') {
                        $s_color = 'danger';
                        $s_icon  = 'fa-circle-xmark';
                        $s_text  = 'Reservation Rejected';
                    } elseif ($s === 'claimed') {
                        $s_color = 'primary';
                        $s_icon  = 'fa-bag-shopping';
                        $s_text  = 'Item Claimed';
                    } elseif ($s === 'forfeited') {
                        $s_color = 'dark';
                        $s_icon  = 'fa-ban';
                        $s_text  = 'Reservation Forfeited';
                    }
                ?>

                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden hover-lift transaction-card
                                <?php echo ($s === 'forfeited') ? 'opacity-75' : ''; ?>">

                        <div class="bg-<?php echo $s_color; ?>" style="height: 5px;"></div>

                        <div class="card-header bg-white border-bottom pt-3 pb-2 px-4
                                    d-flex justify-content-between align-items-center">
                            <span class="text-muted font-monospace small fw-bold">
                                Order #<?php echo str_pad($res['reservation_id'], 6, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span class="badge bg-<?php echo $s_color; ?> bg-opacity-10
                                         text-<?php echo $s_color; ?>
                                         border border-<?php echo $s_color; ?>
                                         rounded-pill px-3 py-2">
                                <i class="fa-solid <?php echo $s_icon; ?> me-1"></i> <?php echo $s_text; ?>
                            </span>
                        </div>

                        <div class="card-body p-4">
                            <div class="row align-items-center">

                                <div class="col-md-7 mb-4 mb-md-0 border-md-end pe-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light rounded-4 d-flex align-items-center
                                                    justify-content-center me-3 border"
                                             style="width: 70px; height: 70px;">
                                            <i class="fa-solid <?php echo $icon; ?> fa-2x text-secondary opacity-75"></i>
                                        </div>
                                        <div>
                                            <small class="text-uppercase text-muted fw-bold"
                                                   style="font-size: 0.7rem; letter-spacing: 1px;">
                                                <?php echo $res['item_type']; ?>
                                            </small>
                                            <h5 class="fw-bold text-dark mb-0">
                                                <?php echo htmlspecialchars($res['brand'] . ' ' . $res['model']); ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-1 small text-muted">
                                        <span>
                                            <i class="fa-regular fa-calendar me-2"></i>
                                            Reserved:
                                            <strong class="text-dark">
                                                <?php echo date('M d, Y h:i A', strtotime($res['created_at'])); ?>
                                            </strong>
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-receipt me-2"></i>
                                            Ref Number:
                                            <strong class="text-dark font-monospace">
                                                <?php echo !empty($res['receipt_number'])
                                                    ? htmlspecialchars($res['receipt_number']) : '—'; ?>
                                            </strong>
                                        </span>
                                        <?php if ($s === 'forfeited' && !empty($res['forfeited_at'])): ?>
                                            <span>
                                                <i class="fa-solid fa-ban me-2 text-danger"></i>
                                                Forfeited on:
                                                <strong class="text-danger">
                                                    <?php echo date('M d, Y', strtotime($res['forfeited_at'])); ?>
                                                </strong>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-5 ps-md-4">
                                    <div class="bg-light rounded-4 p-3 border
                                                <?php echo ($s === 'forfeited') ? 'border-danger border-opacity-25' : ''; ?>">
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Total Price</span>
                                            <span class="fw-bold text-dark">
                                                ₱<?php echo number_format($res['selling_price'], 2); ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">
                                                Deposit (10%)
                                                <?php if ($s === 'forfeited'): ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger
                                                                 border border-danger ms-1"
                                                          style="font-size: 0.6rem;">FORFEITED</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="fw-bold <?php echo ($s === 'forfeited')
                                                ? 'text-danger text-decoration-line-through' : 'text-success'; ?>">
                                                <?php echo ($s === 'forfeited') ? '' : '- '; ?>
                                                ₱<?php echo number_format($res['reservation_amount'], 2); ?>
                                            </span>
                                        </div>
                                        <hr class="my-2 border-secondary opacity-25">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-uppercase fw-bold text-dark"
                                                  style="font-size: 0.8rem;">Balance Due</span>
                                            <h5 class="fw-bold <?php echo ($s === 'forfeited')
                                                ? 'text-muted text-decoration-line-through' : 'text-primary'; ?> mb-0">
                                                ₱<?php echo number_format($remaining_balance, 2); ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ── Footer / Status Banner ────────────────────────── -->
                        <div class="card-footer border-top-0 bg-white px-4 pb-4 pt-0">

                            <?php if ($s === 'pending_payment'): ?>
                                <div class="alert alert-secondary border-0 bg-secondary bg-opacity-10
                                            d-flex align-items-center justify-content-between mb-0 py-2
                                            rounded-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-clock text-secondary fs-4 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark small">Payment not yet completed.</strong>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                This item is <strong>NOT</strong> secured until you pay.
                                                You have until <strong class="text-danger"><?php echo $deadline; ?></strong>
                                                — after that your reservation is automatically forfeited per the
                                                payment policy you agreed to.
                                            </span>
                                        </div>
                                    </div>
                                    <a href="pay.php?reservation_id=<?php echo $res['reservation_id']; ?>"
                                       class="btn btn-primary btn-sm fw-bold rounded-pill px-4 shadow-sm">
                                        <i class="fa-solid fa-credit-card me-1"></i> Continue Payment
                                    </a>
                                </div>

                            <?php elseif ($s === 'pending_verification' || $s === 'pending'): ?>
                                <div class="alert alert-warning border-0 bg-warning bg-opacity-10
                                            d-flex align-items-center mb-0 py-2 rounded-3">
                                    <i class="fa-solid fa-magnifying-glass text-warning fs-4 me-3"></i>
                                    <div>
                                        <strong class="d-block text-dark small">Payment received — pending admin verification.</strong>
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                            Our team is reviewing your payment. You'll be notified once approved.
                                        </span>
                                    </div>
                                </div>

                            <?php elseif ($s === 'approved'): ?>
                                <div class="alert alert-success border-0 bg-success bg-opacity-10
                                            d-flex align-items-center justify-content-between mb-0 py-2
                                            rounded-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-location-dot text-success fs-4 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark small">Item Secured! Visit branch to claim.</strong>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                Bring a valid ID to pay the balance.
                                                Claim by <strong class="text-danger"><?php echo $deadline; ?></strong>.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($s === 'rejected'): ?>
                                <div class="alert alert-danger border-0 bg-danger bg-opacity-10
                                            d-flex align-items-center mb-0 py-2 rounded-3">
                                    <i class="fa-solid fa-triangle-exclamation text-danger fs-4 me-3"></i>
                                    <div>
                                        <strong class="d-block text-dark small">Reservation Cancelled.</strong>
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                            This reservation was rejected. Please contact support if you were charged.
                                        </span>
                                    </div>
                                </div>

                            <?php elseif ($s === 'claimed'): ?>
                                <div class="alert border-0 bg-light d-flex align-items-center mb-0 py-2 rounded-3">
                                    <i class="fa-solid fa-box-open text-muted fs-4 me-3"></i>
                                    <div>
                                        <strong class="d-block text-dark small">Transaction Complete.</strong>
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                            Thank you for purchasing this item from ArMaTech!
                                        </span>
                                    </div>
                                </div>

                            <?php elseif ($s === 'forfeited'): ?>
                                <!-- ══════════════════════════════════════════════════
                                     FORFEITED BANNER
                                     Cites the exact policy the customer ticked in
                                     pay.php (Art. 1226, Civil Code of the Philippines).
                                     ══════════════════════════════════════════════════ -->
                                <div class="rounded-3 overflow-hidden border border-danger border-opacity-25"
                                     style="background: #fff5f5;">
                                    <div class="d-flex align-items-center gap-3 px-3 py-2"
                                         style="background: #fde8e8;">
                                        <i class="fa-solid fa-ban text-danger fs-5"></i>
                                        <strong class="text-danger small">
                                            Reservation Automatically Forfeited — Payment Deadline Missed
                                        </strong>
                                    </div>
                                    <div class="p-3">
                                        <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.6;">
                                            Your 3-day payment window expired without a completed payment.
                                            Per the <strong>Reservation &amp; Payment Policy</strong> you
                                            agreed to before paying your downpayment:
                                        </p>
                                        <ul class="mb-2 ps-3" style="font-size: 0.78rem; color: #6b7280; line-height: 1.8;">
                                            <li>
                                                Your downpayment of
                                                <strong class="text-dark">
                                                    ₱<?php echo number_format($res['reservation_amount'], 2); ?>
                                                </strong>
                                                has been <strong class="text-danger">forfeited</strong> as a penalty
                                                clause (Civil Code Art. 1226).
                                            </li>
                                            <li>
                                                This reservation has been <strong class="text-dark">cancelled</strong>
                                                and the item has been returned to the shop (Art. 1191 — rescission
                                                for non-compliance).
                                            </li>
                                            <li>
                                                No refund will be issued for the forfeited downpayment.
                                            </li>
                                        </ul>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <a href="shop.php"
                                               class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                                <i class="fa-solid fa-store me-1"></i> Browse Items Again
                                            </a>
                                            <span class="text-muted" style="font-size: 0.72rem;">
                                                Contact support if you believe this was an error.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border-0 mt-3"
                     style="min-height: 400px; display: flex; flex-direction: column;
                            justify-content: center; align-items: center;">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center
                                justify-content-center mb-4"
                         style="width: 100px; height: 100px;">
                        <i class="fa-solid fa-receipt fa-3x text-muted opacity-50"></i>
                    </div>
                    <h4 class="fw-bold text-dark">No Reservations Yet</h4>
                    <p class="text-muted mb-4">You haven't reserved any foreclosed items from our store.</p>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        Explore Great Deals
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
    .transaction-card { transition: all 0.2s ease; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>