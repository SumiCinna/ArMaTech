<?php
// modules/teller/view_history.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

if (!isset($_GET['id'])) header("Location: transactions.php");
$trans_id = $_GET['id'];

// 1. Fetch Transaction Info
$sql_t = "SELECT t.*, i.device_type, i.brand, i.model FROM transactions t JOIN items i ON t.transaction_id = i.transaction_id WHERE t.transaction_id = ?";
$stmt = $conn->prepare($sql_t);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

// 2. Fetch All Payments (Invoices) for this Transaction
$sql_p = "SELECT p.*, prof.public_id as teller_public_id 
          FROM payments p 
          JOIN accounts a ON p.teller_id = a.account_id 
          JOIN profiles prof ON a.profile_id = prof.profile_id
          WHERE p.transaction_id = ? 
          ORDER BY p.date_paid DESC";
$stmt = $conn->prepare($sql_p);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<div class="container mt-4">
    <a href="transactions.php" class="btn btn-secondary mb-3">&larr; Back to List</a>
    
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-body">
            <h4 class="text-primary mb-0">
                <i class="bi bi-folder2-open"></i> History for <?php echo $transaction['pt_number']; ?>
            </h4>
            <p class="text-muted"><?php echo $transaction['brand'] . ' ' . $transaction['model']; ?></p>
        </div>
    </div>

    <h5 class="mb-3">Payment Receipts (Invoices)</h5>
    
    <div class="list-group">
        <?php if ($payments->num_rows > 0): ?>
            <?php while ($row = $payments->fetch_assoc()): ?>
                <?php 
                    // Format Invoice ID: INV-0001
                    $inv_no = "INV-" . str_pad($row['payment_id'], 3, '0', STR_PAD_LEFT);
                ?>
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 fw-bold text-dark"><?php echo $inv_no; ?></h5>
                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($row['date_paid'])); ?></small>
                        </div>
                        <p class="mb-1">
                            Paid: <strong>₱<?php echo number_format($row['amount_paid'], 2); ?></strong> 
                            <span class="badge bg-secondary ms-2"><?php echo ucwords(str_replace('_', ' ', $row['payment_type'])); ?></span>
                        </p>
                        <small>Processed by Teller: <?php echo $row['teller_public_id']; ?></small>
                    </div>
                    
                    <a href="print_receipt.php?payment_id=<?php echo $row['payment_id']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-printer"></i> Reprint Receipt
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">No payments have been made for this transaction yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>