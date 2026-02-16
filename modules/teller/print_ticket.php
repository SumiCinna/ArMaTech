<?php
// modules/teller/print_ticket.php
session_start();
require_once '../../config/database.php';

// 1. Get Transaction ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trans_id = $_GET['id'];

// 2. Fetch All Data (Transaction + Customer + Item + Teller)
$sql = "SELECT t.*, 
               p.public_id, p.first_name, p.last_name, p.contact_number, 
               a.house_no_street, a.barangay, a.city,
               i.device_type, i.brand, i.model, i.serial_number, i.storage_capacity, i.ram, i.color, i.inclusions, i.condition_notes
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        JOIN addresses a ON p.profile_id = a.profile_id
        JOIN items i ON t.transaction_id = i.transaction_id
        WHERE t.transaction_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) die("Transaction not found.");

// Format Dates
$date_pawned = date('M d, Y', strtotime($data['date_pawned']));
$date_maturity = date('M d, Y', strtotime($data['maturity_date']));
$date_expiry = date('M d, Y', strtotime($data['expiry_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pawn Ticket - <?php echo $data['pt_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #525659; font-family: 'Inter', sans-serif; } /* Dark grey background like PDF viewer */
        .ticket-container {
            background: white;
            width: 816px; /* Approx Letter size width */
            min-height: 1056px; /* Approx Letter size height */
            margin: 30px auto;
            padding: 48px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .header-logo { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px; }
        .pt-number { font-family: 'Courier Prime', monospace; font-size: 1.4rem; color: #dc3545; font-weight: bold; letter-spacing: 1px; }
        .legal-text { font-size: 0.7rem; text-align: justify; color: #555; line-height: 1.4; }
        .signature-line { border-top: 1px solid #000; margin-top: 50px; width: 90%; margin-left: auto; margin-right: auto; }
        .table-bordered, .table-bordered td, .table-bordered th { border-color: #000 !important; }
        
        /* PRINT STYLES: Hide buttons, remove shadows, fit to page */
        @media print {
            body { background: white; -webkit-print-color-adjust: exact; }
            .ticket-container { box-shadow: none; margin: 0; width: 100%; padding: 20px; }
            .no-print { display: none !important; }
            .btn { display: none; }
        }
    </style>
</head>
<body>

<div class="container mt-4 mb-4 no-print text-center">
    <div class="d-flex justify-content-center gap-2">
        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <button onclick="window.print()" class="btn btn-primary fw-bold px-4"><i class="bi bi-printer-fill"></i> Print Ticket</button>
        <a href="new_pawn.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> New Transaction</a>
    </div>
</div>

<div class="ticket-container">
    
    <!-- Header -->
    <div class="row align-items-center mb-4 border-bottom border-2 border-dark pb-3">
        <div class="col-8">
            <div class="header-logo text-uppercase"><i class="bi bi-shield-lock-fill"></i> ArMaTech Gadgets</div>
            <p class="mb-0 small fw-bold">123 Rizal Avenue, Caloocan City, Metro Manila</p>
            <p class="mb-0 small text-muted">TIN: 000-123-456-000 | Tel: (02) 8123-4567 | Email: support@armatech.com</p>
        </div>
        <div class="col-4 text-end">
            <small class="text-uppercase text-muted fw-bold d-block">Pawn Ticket Number</small>
            <div class="pt-number"><?php echo $data['pt_number']; ?></div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12 text-center">
            <h5 class="fw-bold text-decoration-underline">ORIGINAL PAWN TICKET</h5>
            <p class="small text-muted fst-italic">Please present this ticket for redemption or renewal.</p>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="row g-0 mb-4 border border-dark">
        <!-- Customer Info -->
        <div class="col-6 p-4 border-end border-dark">
            <h6 class="fw-bold text-uppercase text-secondary small mb-3">Pawnee Information</h6>
            <h4 class="fw-bold mb-1"><?php echo strtoupper($data['first_name'] . ' ' . $data['last_name']); ?></h4>
            <p class="mb-1 small text-muted">Customer ID: <span class="fw-bold text-dark"><?php echo $data['public_id']; ?></span></p>
            <p class="mb-1 small text-muted"><i class="bi bi-geo-alt-fill"></i> <?php echo $data['house_no_street'] . ', ' . $data['barangay'] . ', ' . $data['city']; ?></p>
            <p class="mb-0 small text-muted"><i class="bi bi-telephone-fill"></i> <?php echo $data['contact_number']; ?></p>
        </div>
        
        <!-- Loan Info -->
        <div class="col-6 p-4 bg-light">
             <h6 class="fw-bold text-uppercase text-secondary small mb-3">Loan Details</h6>
             
             <div class="row mb-2">
                 <div class="col-6 small">Date Loan Granted:</div>
                 <div class="col-6 fw-bold text-end"><?php echo $date_pawned; ?></div>
             </div>
             <div class="row mb-2">
                 <div class="col-6 small">Maturity Date:</div>
                 <div class="col-6 fw-bold text-end text-danger"><?php echo $date_maturity; ?></div>
             </div>
             <div class="row mb-3">
                 <div class="col-6 small">Expiry Date:</div>
                 <div class="col-6 fw-bold text-end text-danger"><?php echo $date_expiry; ?></div>
             </div>

             <div class="border-top border-secondary pt-2">
                 <div class="d-flex justify-content-between align-items-center">
                     <span class="fw-bold">Principal Amount:</span>
                     <span class="fw-bold fs-3">₱ <?php echo number_format($data['principal_amount'], 2); ?></span>
                 </div>
             </div>
        </div>
    </div>

    <!-- Item Details -->
    <div class="mb-4">
        <h6 class="fw-bold text-uppercase small mb-2">Item Description</h6>
        <table class="table table-bordered border-dark">
            <thead class="bg-light">
                <tr>
                    <th width="25%">Category</th>
                    <th>Description (Model, Serial, Condition, Inclusions)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold text-uppercase align-middle"><?php echo $data['device_type']; ?></td>
                    <td class="py-3" style="font-family: 'Courier Prime', monospace;">
                        <strong><?php echo $data['brand'] . ' ' . $data['model']; ?></strong><br>
                        <small class="text-muted">Serial: <?php echo $data['serial_number']; ?></small><br>
                        <span class="small">Specs: <?php echo $data['storage_capacity'] . ' | ' . $data['ram'] . ' | ' . $data['color']; ?></span><br>
                        <span class="small">Inclusions: <?php echo $data['inclusions']; ?></span><br>
                        <span class="small fst-italic">Condition: <?php echo $data['condition_notes']; ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Terms -->
    <div class="legal-text mb-5 p-3 border border-secondary bg-light">
        <strong>TERMS AND CONDITIONS:</strong><br>
        1. The pawner hereby accepts the pawn ticket and agrees to all terms and conditions stated herein.<br>
        2. The pawn loan period is fixed as per the dates indicated above.<br>
        3. The interest rate is fixed at <strong>3% per month</strong>. A fraction of a month is considered as one full month.<br>
        4. <strong>Redemption:</strong> The pawner may redeem the item by paying the principal plus interest within the loan period.<br>
        5. <strong>Expiry:</strong> In case of failure to redeem the pawned item within the expiry date (Maturity + 30 Days Grace Period), the pawner agrees that the pawnee (ArMaTech) has the right to sell or auction the item to recover the loan amount.<br>
        6. This ticket must be presented upon redemption or renewal. If lost, an affidavit of loss must be submitted immediately.
    </div>

    <!-- Signatures -->
    <div class="row text-center mt-5">
        <div class="col-6">
            <div class="signature-line"></div>
            <p class="small fw-bold text-uppercase">Signature of Appraiser</p>
        </div>
        <div class="col-6">
            <div class="signature-line"></div>
            <p class="small fw-bold text-uppercase">Signature of Pawner</p>
        </div>
    </div>

    <div class="text-center mt-5 pt-4">
        <small class="text-muted">System Generated Ticket | ArMaTech v1.0</small>
    </div>

</div>

</body>
</html>