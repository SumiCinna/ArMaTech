<?php
// modules/teller/transactions.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

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
        WHERE (t.pt_number LIKE ? 
           OR p.last_name LIKE ? 
           OR p.public_id LIKE ?)";

if ($filter_status != 'all') {
    $sql .= " AND t.status = ?";
}

$sql .= " ORDER BY t.date_pawned DESC";

$stmt = $conn->prepare($sql);
$term = "%$search%";

if ($filter_status != 'all') {
    $stmt->bind_param("ssss", $term, $term, $term, $filter_status);
} else {
    $stmt->bind_param("sss", $term, $term, $term);
}
$stmt->execute();
$result = $stmt->get_result();
?>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Transaction History</h3>
            <small class="text-muted">View and manage all pawn transactions and records.</small>
        </div>
        <a href="new_pawn.php" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-2"></i> New Pawn
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by PT Number, Customer Name, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="redeemed" <?php echo ($filter_status == 'redeemed') ? 'selected' : ''; ?>>Redeemed</option>
                        <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="auctioned" <?php echo ($filter_status == 'auctioned') ? 'selected' : ''; ?>>Auctioned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                            <th class="ps-4 py-3">PT Number</th>
                            <th>Customer Details</th>
                            <th>Item Information</th>
                        <th>Date Pawned</th>
                        <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-dark border font-monospace"><?php echo $row['pt_number']; ?></span>
                                    </td>
                                <td>
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
                                            <div class="rounded-circle <?php echo $is_sold ? 'bg-success text-success' : 'bg-primary text-primary'; ?> bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight:bold;">
                                                <?php echo substr($d_fname, 0, 1) . substr($d_lname, 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <?php echo $d_fname . ' ' . $d_lname; ?>
                                                    <?php if($is_sold): ?>
                                                        <span class="badge bg-success text-white ms-1" style="font-size: 0.6em;">BUYER</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <small class="text-muted" style="font-size: 0.75rem;">ID: <?php echo $d_pid; ?></small>
                                            </div>
                                        </div>
                                </td>
                                    <td>
                                        <h6 class="mb-0 fw-bold text-dark"><?php echo $row['brand'] . ' ' . $row['model']; ?></h6>
                                        <small class="text-muted"><?php echo $row['device_type']; ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></span>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($row['date_pawned'])); ?></small>
                                        </div>
                                    </td>
                                <td>
                                    <?php 
                                    $status = $row['status'];
                                        $badge_class = 'bg-secondary text-secondary';
                                        $icon = 'fa-circle-question';
                                        
                                        if ($status == 'active') {
                                            $badge_class = 'bg-success text-success';
                                            $icon = 'fa-circle-check';
                                        } elseif ($status == 'redeemed') {
                                            $badge_class = 'bg-primary text-primary';
                                            $icon = 'fa-hand-holding-hand';
                                        } elseif ($status == 'expired') {
                                            $badge_class = 'bg-danger text-danger';
                                            $icon = 'fa-triangle-exclamation';
                                        } elseif ($status == 'auctioned') {
                                            $badge_class = 'bg-dark text-dark';
                                            $icon = 'fa-gavel';
                                        }
                                    ?>
                                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 px-3 py-2 rounded-pill text-uppercase">
                                            <i class="fa-solid <?php echo $icon; ?> me-1"></i> <?php echo $status; ?>
                                        </span>
                                </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="print_ticket.php?id=<?php echo $row['transaction_id']; ?>" target="_blank" class="btn btn-sm btn-light border text-dark fw-bold" title="Reprint Ticket">
                                                <i class="fa-solid fa-print"></i>
                                    </a>
                                            <a href="view_history.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-light border text-primary fw-bold" title="View Payments">
                                                <i class="fa-solid fa-receipt"></i>
                                    </a>
                                        </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i><br>
                                    No transactions found matching your search.
                                </td>
                            </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include_once '../../includes/teller_footer.php'; ?>