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

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-list-check"></i> Transaction History</h2>
        <a href="new_pawn.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Pawn</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search by PT Number, Customer Name, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-lg">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>PT Number</th>
                        <th>Customer</th>
                        <th>Item</th>
                        <th>Date Pawned</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo $row['pt_number']; ?></td>
                                <td>
                                    <?php echo $row['first_name'] . ' ' . $row['last_name']; ?><br>
                                    <small class="text-muted"><?php echo $row['public_id']; ?></small>
                                </td>
                                <td><?php echo $row['brand'] . ' ' . $row['model']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></td>
                                <td>
                                    <?php 
                                    $status = $row['status'];
                                    $badge = 'bg-secondary';
                                    if ($status == 'active') $badge = 'bg-success';
                                    if ($status == 'redeemed') $badge = 'bg-primary';
                                    if ($status == 'expired') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> text-uppercase"><?php echo $status; ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="print_ticket.php?id=<?php echo $row['transaction_id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Reprint Ticket">
                                        <i class="bi bi-file-earmark-text"></i> Ticket
                                    </a>
                                    
                                    <a href="view_history.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-info text-white" title="View Payments">
                                        <i class="bi bi-receipt"></i> Payments
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center p-4">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>