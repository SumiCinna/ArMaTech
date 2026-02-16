<?php
// modules/teller/redeem.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$error = "";
$results = [];

if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    // Search by Ticket Number OR Customer Public ID
    $sql = "SELECT t.*, 
                   p.first_name, p.last_name, p.public_id,
                   i.brand, i.model, i.device_type
            FROM transactions t
            JOIN profiles p ON t.customer_id = p.profile_id
            JOIN items i ON t.transaction_id = i.transaction_id
            WHERE (t.pt_number = ? OR p.public_id = ?) 
            AND t.status IN ('active', 'expired')"; // Only active items can be redeemed
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    if (count($results) == 0) {
        $error = "No active transaction found for '$q'.";
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-search"></i> Redeem Item</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control" placeholder="Scan Pawn Ticket (PT-...) or Customer ID (CUS-...)" autofocus required>
                        <button class="btn btn-primary" type="submit">Search</button>
                    </form>
                </div>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (count($results) > 0): ?>
                <div class="list-group">
                    <?php foreach ($results as $row): ?>
                        <a href="redeem_process.php?id=<?php echo $row['transaction_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 text-primary fw-bold"><?php echo $row['pt_number']; ?></h5>
                                <p class="mb-1 fw-bold"><?php echo $row['brand'] . ' ' . $row['model']; ?> (<?php echo $row['device_type']; ?>)</p>
                                <small class="text-muted">
                                    Owner: <span class="badge bg-secondary"><?php echo $row['public_id']; ?></span> 
                                    <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark mb-1">Status: <?php echo strtoupper($row['status']); ?></span><br>
                                <button class="btn btn-sm btn-success fw-bold">Select <i class="bi bi-arrow-right"></i></button>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>