<?php
// modules/teller/print_receipt.php
session_start();
require_once '../../config/database.php';

// 1. SECURITY CHECK
if (!isset($_GET['payment_id'])) {
    die("Error: No Payment ID provided.");
}

$payment_id = $_GET['payment_id'];

// 2. FETCH DATA (Join Payment + Transaction + Customer + Teller)
// We join 4 tables here to get the complete picture
$sql = "SELECT p.*, 
               t.pt_number, t.maturity_date AS current_maturity, t.status AS transaction_status,
               c.first_name AS cust_fname, c.last_name AS cust_lname, c.public_id AS cust_id,
               tp.public_id AS teller_public_id,
               i.brand, i.model, i.device_type, i.serial_number,
               b.first_name AS buyer_fname, b.last_name AS buyer_lname, b.public_id AS buyer_id
        FROM payments p
        JOIN transactions t ON p.transaction_id = t.transaction_id
        JOIN items i ON t.transaction_id = i.transaction_id
        JOIN profiles c ON t.customer_id = c.profile_id
        JOIN accounts a_teller ON p.teller_id = a_teller.account_id
        JOIN profiles tp ON a_teller.profile_id = tp.profile_id
        LEFT JOIN shop_items si ON t.transaction_id = si.transaction_id
        LEFT JOIN shop_reservations sr ON si.shop_id = sr.shop_id AND sr.status = 'claimed'
        LEFT JOIN profiles b ON sr.customer_profile_id = b.profile_id
        WHERE p.payment_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) die("Receipt not found.");

// 3. FORMATTING
// Format Invoice Number: INV-0001
$invoice_no = "INV-" . str_pad($data['payment_id'], 3, '0', STR_PAD_LEFT);
$date_paid = date('M d, Y h:i A', strtotime($data['date_paid']));

// Readable Payment Type
$type_label = "";
$type_class = "";
switch ($data['payment_type']) {
    case 'interest_only': 
        $type_label = "RENEWAL (INTEREST)"; 
        $type_class = "bg-info text-dark";
        break;
    case 'partial_payment': 
        $type_label = "PARTIAL PAYMENT"; 
        $type_class = "bg-warning text-dark";
        break;
    case 'full_redemption': 
        $type_label = "FULL REDEMPTION"; 
        $type_class = "bg-success text-white";
        break;
}

// Default: Show Original Customer
$display_fname = $data['cust_fname'];
$display_lname = $data['cust_lname'];
$display_id    = $data['cust_id'];

// Logic: If item was SOLD (Auctioned) and this is the sale receipt, show BUYER
if ($data['transaction_status'] == 'auctioned' && !empty($data['buyer_id']) && $data['payment_type'] == 'full_redemption') {
    $display_fname = $data['buyer_fname'];
    $display_lname = $data['buyer_lname'];
    $display_id    = $data['buyer_id'];
    $type_label    = "AUCTION SALE";
    $type_class    = "bg-dark text-white";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo $invoice_no; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            color: #1e293b;
        }
        
        .receipt-card {
            max-width: 420px;
            margin: 3rem auto;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            position: relative;
        }

        /* Receipt Header */
        .receipt-header {
            background: #0f172a;
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
        }
        
        .receipt-header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background-image: radial-gradient(circle, transparent 70%, #ffffff 75%);
            background-size: 20px 20px;
            background-position: center bottom;
        }

        .brand-icon { font-size: 2.5rem; margin-bottom: 0.5rem; display: block; }
        .brand-name { font-weight: 800; letter-spacing: -0.5px; font-size: 1.25rem; }
        .receipt-title { 
            font-family: 'JetBrains Mono', monospace; 
            font-size: 0.85rem; 
            opacity: 0.8; 
            margin-top: 0.5rem; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }

        /* Receipt Body */
        .receipt-body { padding: 2rem 1.5rem; }

        .info-group { margin-bottom: 1.25rem; }
        .info-label { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: #64748b; 
            font-weight: 600; 
            margin-bottom: 0.25rem;
        }
        .info-value { 
            font-weight: 600; 
            font-size: 1rem; 
            color: #0f172a;
        }
        .mono-value { font-family: 'JetBrains Mono', monospace; }

        .divider { 
            border-top: 2px dashed #e2e8f0; 
            margin: 1.5rem 0; 
        }

        /* Total Section */
        .total-section {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: center;
            margin-top: 1.5rem;
        }
        .total-amount { 
            font-size: 2rem; 
            font-weight: 800; 
            color: #0f172a; 
            line-height: 1;
            margin: 0.5rem 0;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            padding: 1.5rem;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Print Specifics */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .receipt-card { 
                box-shadow: none; 
                margin: 0; 
                width: 100%; 
                max-width: 100%; 
                border-radius: 0; 
                border: none;
            }
            .receipt-header { background: white; color: black; border-bottom: 2px solid black; padding-bottom: 1rem; }
            .receipt-header::after { display: none; }
            .brand-icon { color: black; }
            .no-print { display: none !important; }
            .total-section { border-color: black; background: none; }
            .receipt-footer { background: none; border-top: 1px solid black; }
        }
    </style>
</head>
<body>

    <!-- Navigation / Actions (Hidden on Print) -->
    <div class="container text-center mt-4 mb-2 no-print">
        <div class="d-flex justify-content-center gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-printer-fill"></i> Print Receipt
            </button>
            <a href="redeem.php" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                New Transaction <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <p class="text-muted small mt-3"><i class="bi bi-info-circle"></i> Use a thermal printer for best results.</p>
    </div>

    <!-- Receipt Card -->
    <div class="receipt-card">
        
        <div class="receipt-header">
            <i class="bi bi-shield-lock-fill brand-icon"></i>
            <div class="brand-name">ArMaTech Pawnshop</div>
            <div class="receipt-title">Official Receipt</div>
        </div>

        <div class="receipt-body">
            
            <!-- Transaction Info -->
            <div class="row mb-2">
                <div class="col-6">
                    <div class="info-label">Invoice No.</div>
                    <div class="info-value mono-value"><?php echo $invoice_no; ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="info-label">Date & Time</div>
                    <div class="info-value small"><?php echo $date_paid; ?></div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="info-label">Reference PT#</div>
                    <div class="info-value mono-value text-primary"><?php echo $data['pt_number']; ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="info-label">Teller</div>
                    <div class="info-value"><?php echo strtoupper($data['teller_public_id']); ?></div>
                </div>
            </div>

            <div class="mt-3">
                <div class="info-label">Item Details</div>
                <div class="info-value small"><?php echo $data['brand'] . ' ' . $data['model']; ?></div>
                <div class="small text-muted" style="font-size: 0.7rem;"><?php echo $data['device_type']; ?> | SN: <?php echo $data['serial_number']; ?></div>
            </div>

            <div class="divider"></div>

            <!-- Customer Info -->
            <div class="info-group">
                <div class="info-label">Customer</div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="info-value"><?php echo $display_fname . ' ' . $display_lname; ?></div>
                    <span class="badge bg-light text-dark border"><?php echo $display_id; ?></span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Payment Details -->
            <div class="text-center mb-3">
                <span class="badge <?php echo $type_class; ?> px-3 py-2 rounded-pill text-uppercase" style="letter-spacing: 1px;">
                    <?php echo $type_label; ?>
                </span>
            </div>

            <?php if ($data['payment_type'] != 'full_redemption'): ?>
                <div class="bg-light p-3 rounded border mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Previous Principal</span>
                        <span class="fw-bold">₱<?php echo number_format($data['old_principal'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">New Principal Balance</span>
                        <span class="fw-bold text-primary">₱<?php echo number_format($data['new_principal'], 2); ?></span>
                    </div>
                    <div class="border-top my-2"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger small fw-bold text-uppercase">New Maturity Date</span>
                        <span class="fw-bold text-danger"><?php echo date('M d, Y', strtotime($data['current_maturity'])); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="total-section">
                <div class="info-label">Total Amount Paid</div>
                <div class="total-amount">₱<?php echo number_format($data['amount_paid'], 2); ?></div>
                <div class="small text-muted">Cash Payment</div>
            </div>

        </div>

        <div class="receipt-footer">
            <p class="mb-1 fw-bold">Thank you for your business!</p>
            <p class="mb-0">123 Rizal Ave, Caloocan City | TIN: 000-123-456-000</p>
            <p class="mb-0 mt-2 text-muted fst-italic" style="font-size: 0.65rem;">System Generated Receipt</p>
        </div>

    </div>

</body>
</html>