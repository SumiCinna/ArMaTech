<?php
// modules/admin/manage_staff.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// Fetch all staff (Tellers & Admins)
// We join accounts + profiles + addresses to get full info
$sql = "SELECT p.*, a.account_id, a.username, a.role, a.status 
        FROM accounts a
        JOIN profiles p ON a.profile_id = p.profile_id
        LEFT JOIN addresses ad ON p.profile_id = ad.profile_id
        WHERE a.role IN ('admin', 'teller')
        ORDER BY p.date_hired DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Staff Management</h3>
            <small class="text-muted">Manage system access for Tellers and Admins</small>
        </div>
        <a href="add_staff.php" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-user-plus me-2"></i> Add New Employee
        </a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 py-3">Employee Name</th>
                            <th>Role</th>
                            <th>Contact Info</th>
                            <th>Date Hired</th>
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
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight:bold;">
                                                <?php echo substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                                <small class="text-muted"><?php echo $row['public_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['role'] == 'admin'): ?>
                                            <span class="badge bg-dark text-uppercase">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark text-uppercase">Teller</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="d-block"><i class="fa-solid fa-envelope me-1 text-muted"></i> <?php echo $row['email']; ?></small>
                                        <small class="d-block"><i class="fa-solid fa-phone me-1 text-muted"></i> <?php echo $row['contact_number']; ?></small>
                                    </td>
                                    <td><?php echo ($row['date_hired']) ? date('M d, Y', strtotime($row['date_hired'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="view_staff_profile.php?id=<?php echo $row['account_id']; ?>" class="btn btn-sm btn-light text-primary border" title="View Full Profile">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <?php if ($row['status'] === 'active'): ?>
                                                <a href="../../core/toggle_status.php?id=<?php echo $row['account_id']; ?>&current=active" class="btn btn-sm btn-light text-danger" title="Disable Account">
                                                    <i class="fa-solid fa-user-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="../../core/toggle_status.php?id=<?php echo $row['account_id']; ?>&current=inactive" class="btn btn-sm btn-light text-success" title="Reactivate Account">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fa-2x mb-3 opacity-25"></i><br>
                                    No staff members found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>