<?php
// modules/teller/transactions.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

// SEARCH LOGIC
$search = $_GET['search'] ?? '';
$sql = "SELECT t.*, 
               p.first_name, p.last_name, p.public_id,
               i.device_type, i.brand, i.model
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        JOIN items i ON t.transaction_id = i.transaction_id
        WHERE t.pt_number LIKE ? 
           OR p.last_name LIKE ? 
           OR p.public_id LIKE ?
        ORDER BY t.date_pawned DESC";

$stmt = $conn->prepare($sql);
$term = "%$search%";
$stmt->bind_param("sss", $term, $term, $term);
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
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by PT Number, Customer Name, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Search Record</button>
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
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight:bold;">
                                                <?php echo substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                                <small class="text-muted" style="font-size: 0.75rem;">ID: <?php echo $row['public_id']; ?></small>
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