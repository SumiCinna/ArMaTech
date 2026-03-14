<?php
// modules/teller/transactions.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

// SECURITY: Get logged-in teller's ID
$teller_id = $_SESSION['account_id'];

// SEARCH LOGIC
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';

$sql = "SELECT t.*, 
               p.first_name, p.last_name, p.public_id,
               i.device_type, i.brand, i.model,
               b.first_name as buyer_fname, b.last_name as buyer_lname, b.public_id as buyer_public_id
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        JOIN items i ON t.transaction_id = i.transaction_id
        LEFT JOIN shop_items si ON t.transaction_id = si.transaction_id
        LEFT JOIN shop_reservations sr ON si.shop_id = sr.shop_id AND sr.status = 'claimed'
        LEFT JOIN profiles b ON sr.customer_profile_id = b.profile_id
        WHERE t.teller_id = ? AND (t.pt_number LIKE ? 
           OR p.last_name LIKE ? 
           OR p.public_id LIKE ?)";

if ($filter_status != 'all') {
    $sql .= " AND t.status = ?";
}

$sql .= " ORDER BY t.date_pawned DESC";

$stmt = $conn->prepare($sql);
$term = "%$search%";

// Bind the teller_id (integer 'i') as the first parameter
if ($filter_status != 'all') {
    $stmt->bind_param("issss", $teller_id, $term, $term, $term, $filter_status);
} else {
    $stmt->bind_param("isss", $teller_id, $term, $term, $term);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-4 pb-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-4 mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-folder-open me-2 text-primary"></i> My Ledger</h3>
            <p class="text-muted small mb-0">View, search, and manage the pawn contracts you have processed.</p>
        </div>
        <a href="new_pawn.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i> New Transaction
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-pill overflow-hidden bg-white p-2">
        <form method="GET" class="d-flex flex-column flex-md-row m-0 g-0">
            <div class="flex-grow-1 position-relative border-md-end mb-2 mb-md-0">
                <span class="position-absolute top-50 translate-middle-y text-muted ms-3"><i class="fa-solid fa-search"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none ps-5 py-2 bg-transparent" placeholder="Search PT Number, Customer Name, or ID..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="d-flex" style="min-width: 250px;">
                <select name="status" class="form-select border-0 shadow-none bg-transparent py-2 fw-bold text-secondary border-start" onchange="this.form.submit()">
                    <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>🟢 Active</option>
                    <option value="redeemed" <?php echo ($filter_status == 'redeemed') ? 'selected' : ''; ?>>⚫ Redeemed</option>
                    <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>🔴 Expired</option>
                    <option value="auctioned" <?php echo ($filter_status == 'auctioned') ? 'selected' : ''; ?>>🟣 Auctioned</option>
                </select>
                
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold ms-2 shadow-sm">Search</button>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Pawn Ticket</th>
                            <th class="py-3 fw-bold">Customer Info</th>
                            <th class="py-3 fw-bold">Collateral</th>
                            <th class="py-3 fw-bold">Date Issued</th>
                            <th class="py-3 fw-bold">Status</th>
                            <th class="text-end pe-4 py-3 fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    
                                    <td class="ps-4 py-3">
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1 mb-1 d-inline-block" style="font-size: 0.8rem;"><?php echo $row['pt_number']; ?></span>
                                        <div class="fw-bold text-success" style="font-size: 0.85rem;">Prin: ₱<?php echo number_format($row['principal_amount'], 2); ?></div>
                                    </td>
                                    
                                    <td class="py-3">
                                        <?php 
                                            // Determine which name to show (Original Owner vs Buyer)
                                            $d_fname = $row['first_name'];
                                            $d_lname = $row['last_name'];
                                            $d_pid   = $row['public_id'];
                                            $is_sold = false;

                                            if ($row['status'] == 'auctioned' && !empty($row['buyer_fname'])) {
                                                $d_fname = $row['buyer_fname'];
                                                $d_lname = $row['buyer_lname'];
                                                $d_pid   = $row['buyer_public_id'];
                                                $is_sold = true;
                                            }
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle <?php echo $is_sold ? 'bg-success text-success' : 'bg-primary text-primary'; ?> bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px; font-weight:bold;">
                                                <?php echo substr($d_fname, 0, 1) . substr($d_lname, 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center" style="font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($d_fname . ' ' . $d_lname); ?>
                                                    <?php if($is_sold): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-2 px-2 py-0 rounded" style="font-size: 0.6rem;">BUYER</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <small class="text-muted font-monospace" style="font-size: 0.7rem;">ID: <?php echo $d_pid; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-3">
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h6>
                                        <small class="text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo $row['device_type']; ?></small>
                                    </td>
                                    
                                    <td class="py-3">
                                        <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', strtotime($row['date_pawned'])); ?></span>
                                    </td>
                                    
                                    <td class="py-3">
                                        <?php 
                                            $status = strtolower($row['status']);
                                            $badge_class = 'bg-secondary text-secondary';
                                            $icon = 'fa-circle-question';
                                            
                                            if ($status == 'active') {
                                                $badge_class = 'bg-success text-success';
                                                $icon = 'fa-shield-check';
                                            } elseif ($status == 'redeemed') {
                                                $badge_class = 'bg-dark text-dark';
                                                $icon = 'fa-hand-holding-hand';
                                            } elseif ($status == 'expired') {
                                                $badge_class = 'bg-danger text-danger';
                                                $icon = 'fa-gavel';
                                                $status = 'Foreclosed';
                                            } elseif ($status == 'auctioned') {
                                                $badge_class = 'bg-primary text-primary';
                                                $icon = 'fa-store';
                                            }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 border border-<?php echo str_replace('bg-', '', $badge_class); ?> border-opacity-25 px-3 py-2 rounded-pill text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                            <i class="fa-solid <?php echo $icon; ?> me-1"></i> <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-end pe-4 py-3">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="print_ticket.php?id=<?php echo $row['transaction_id']; ?>" target="_blank" class="btn btn-sm btn-light border text-secondary hover-primary" data-bs-toggle="tooltip" title="Reprint Contract">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            <a href="view_history.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-light border text-dark fw-bold hover-dark shadow-sm px-3">
                                                Ledger <i class="fa-solid fa-arrow-right ms-1 small"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-folder-open fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
                                        <p class="text-muted small">No transactions matched your search criteria.</p>
                                        <?php if(!empty($search) || $filter_status != 'all'): ?>
                                            <a href="transactions.php" class="btn btn-sm btn-outline-primary rounded-pill px-4 mt-2 fw-bold">Clear Filters</a>
                                        <?php endif; ?>
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
    .custom-table tbody tr { transition: background-color 0.2s; }
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    .form-control:focus, .form-select:focus { box-shadow: none !important; }
    .input-group:focus-within { box-shadow: none !important; }
    
    .hover-primary:hover { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }
    .hover-dark:hover { background-color: #212529 !important; color: white !important; border-color: #212529 !important; }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function(){
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

<?php include_once '../../includes/teller_footer.php'; ?>