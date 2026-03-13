<?php
// modules/teller/search_records.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = null;
$total_found = 0;

if (!empty($search_query)) {
    // We search across PT Number, Customer First/Last Name, Contact Number, Item Brand, and Item Model
    $sql = "SELECT t.transaction_id, t.pt_number, t.status, t.date_pawned, t.principal_amount,
                   p.first_name, p.last_name, p.contact_number, p.profile_id,
                   i.device_type, i.brand, i.model 
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE t.pt_number LIKE ? 
               OR p.first_name LIKE ? 
               OR p.last_name LIKE ? 
               OR p.contact_number LIKE ? 
               OR i.brand LIKE ? 
               OR i.model LIKE ?
            ORDER BY t.date_pawned DESC 
            LIMIT 50"; // Limit to prevent massive loads

    $stmt = $conn->prepare($sql);
    $term = "%" . $search_query . "%";
    
    // Bind the term 6 times for the 6 LIKE clauses
    $stmt->bind_param("ssssss", $term, $term, $term, $term, $term, $term);
    $stmt->execute();
    $results = $stmt->get_result();
    $total_found = $results->num_rows;
}
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="row justify-content-center mt-5 mb-4">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="fa-solid fa-magnifying-glass fs-2"></i>
            </div>
            <h2 class="fw-bold text-dark">Global Record Search</h2>
            <p class="text-muted mb-4">Search by Customer Name, PT Number, Phone Number, or Item Details.</p>

            <form method="GET" action="search_records.php">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white border">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-4">
                        <i class="fa-solid fa-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent py-3" placeholder="Type here to search..." value="<?php echo htmlspecialchars($search_query); ?>" autofocus>
                    <?php if(!empty($search_query)): ?>
                        <a href="search_records.php" class="btn btn-light text-muted border-0 d-flex align-items-center"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-primary px-4 fw-bold" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($search_query)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <h6 class="text-muted fw-bold text-uppercase letter-spacing-1">Search Results (<?php echo $total_found; ?> found)</h6>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle mb-0 custom-table">
                        <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                            <tr>
                                <th class="ps-4 py-3 fw-bold">PT Number</th>
                                <th class="py-3 fw-bold">Customer Info</th>
                                <th class="py-3 fw-bold">Item Details</th>
                                <th class="py-3 fw-bold">Status</th>
                                <th class="text-end pe-4 py-3 fw-bold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($results && $total_found > 0): ?>
                                <?php while ($row = $results->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f8f9fa;">
                                        <td class="ps-4 py-3">
                                            <span class="font-monospace fw-bold text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">
                                                <?php echo $row['pt_number']; ?>
                                            </span>
                                            <div class="small text-muted mt-1"><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></div>
                                        </td>
                                        
                                        <td class="py-3">
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h6>
                                            <small class="text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i> <?php echo $row['contact_number']; ?></small>
                                        </td>
                                        
                                        <td class="py-3">
                                            <h6 class="mb-0 text-dark" style="font-size: 0.9rem;"><?php echo $row['brand'] . ' ' . $row['model']; ?></h6>
                                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Principal: ₱<?php echo number_format($row['principal_amount'], 2); ?></small>
                                        </td>
                                        
                                        <td class="py-3">
                                            <?php 
                                                $status = strtolower($row['status']);
                                                $badge_class = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                                                
                                                if ($status == 'active') $badge_class = 'bg-success bg-opacity-10 text-success border-success';
                                                elseif ($status == 'redeemed') $badge_class = 'bg-primary bg-opacity-10 text-primary border-primary';
                                                elseif ($status == 'expired') $badge_class = 'bg-danger bg-opacity-10 text-danger border-danger';
                                                elseif ($status == 'auctioned') $badge_class = 'bg-dark bg-opacity-10 text-dark border-dark';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> border rounded-pill px-3 py-2 text-uppercase" style="font-size: 0.7rem;">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="text-end pe-4 py-3">
                                            <a href="view_history.php?id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3 shadow-sm">
                                                View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                                <i class="fa-solid fa-folder-open fs-1 opacity-50"></i>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1">No Results Found</h5>
                                            <p class="text-muted small">We couldn't find anything matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>".</p>
                                            <a href="search_records.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 mt-2">Clear Search</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif(isset($_GET['q'])): ?>
        <div class="text-center py-5">
            <p class="text-muted">Please enter a keyword to start searching.</p>
        </div>
    <?php endif; ?>

</div>

<style>
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
    .input-group .form-control:focus { border-color: inherit; -webkit-box-shadow: none; box-shadow: none; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>