<?php
// modules/admin/manage_customers.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// SEARCH LOGIC
$search = $_GET['search'] ?? '';
$sql = "SELECT p.*, a.account_id, a.username, a.status,
        CONCAT(ad.house_no_street, ', ', ad.barangay, ', ', ad.city, ', ', ad.province) AS full_address 
        FROM accounts a
        JOIN profiles p ON a.profile_id = p.profile_id
        LEFT JOIN addresses ad ON p.profile_id = ad.profile_id
        WHERE a.role = 'customer'";

if (!empty($search)) {
    $term = "%$search%";
    $sql .= " AND (p.first_name LIKE '$term' OR p.last_name LIKE '$term' OR a.username LIKE '$term' OR p.public_id LIKE '$term')";
}

$sql .= " ORDER BY p.created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Database Error: " . $conn->error);
}
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Customer Directory</h3>
            <small class="text-muted">View and manage registered client accounts.</small>
        </div>
        </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-white rounded">
            <form method="GET" class="row g-2">
                <div class="col-md-11">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by Name, Username, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 py-3">Customer Profile</th>
                            <th>Contact Info</th>
                            <th>Address</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; font-weight:bold; font-size: 1.1em;">
                                                <?php echo substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">ID: <?php echo $row['public_id']; ?></small>
                                                <span class="badge bg-light text-secondary border">@<?php echo $row['username']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="mb-1"><i class="fa-solid fa-phone me-2 text-muted" style="width:15px;"></i> <?php echo $row['contact_number']; ?></small>
                                            <small><i class="fa-solid fa-envelope me-2 text-muted" style="width:15px;"></i> <?php echo $row['email']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted text-wrap" style="max-width: 200px; display:block;">
                                            <i class="fa-solid fa-map-pin me-1"></i> <?php echo $row['full_address'] ?? 'No Address'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted fw-bold"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 rounded-pill">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm">
                                            <a href="view_customer_history.php?id=<?php echo $row['account_id']; ?>" class="btn btn-sm btn-light border text-primary" title="Transaction History">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light border text-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $row['account_id']; ?>" title="Reset Password">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                            <?php if ($row['status'] === 'active'): ?>
                                                <a href="../../core/toggle_status.php?id=<?php echo $row['account_id']; ?>&current=active" class="btn btn-sm btn-light border text-danger" title="Disable Account">
                                                    <i class="fa-solid fa-user-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="../../core/toggle_status.php?id=<?php echo $row['account_id']; ?>&current=inactive" class="btn btn-sm btn-light border text-success" title="Reactivate Account">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="modal fade" id="resetModal<?php echo $row['account_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Reset Password?</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to reset the password for <strong><?php echo $row['username']; ?></strong>?</p>
                                                        <p class="text-muted small">The new password will be set to: <span class="fw-bold text-dark font-monospace">Armatech123</span></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="../../core/admin_reset_password.php" method="POST">
                                                            <input type="hidden" name="account_id" value="<?php echo $row['account_id']; ?>">
                                                            <button type="submit" name="btn_reset" class="btn btn-warning fw-bold">Confirm Reset</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-viewfinder fa-3x mb-3 opacity-25"></i><br>
                                    No customers found matching your search.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <small class="text-muted">Showing <?php echo $result->num_rows; ?> registered customers</small>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>