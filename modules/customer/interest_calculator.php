<?php
// modules/customer/interest_calculator.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// SECURITY
if (!isset($_SESSION['account_id'])) {
    header("Location: ../../customer_login.php");
    exit();
}

$customer_id = $_SESSION['profile_id'];

// Fetch Active Loans for the "Quick Select" Dropdown
$sql = "SELECT t.transaction_id, t.pt_number, i.device_type, i.brand, i.model, t.principal_amount, t.date_pawned 
        FROM transactions t
        JOIN items i ON t.transaction_id = i.transaction_id
        WHERE t.customer_id = ? AND t.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$loans = $stmt->get_result();
?>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fa-solid fa-calculator me-2 text-primary"></i> Interest Simulator</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="row">
        
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-primary text-white py-3 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-sliders me-2"></i> Calculator Settings</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Select an Active Loan</label>
                        <select id="loanSelector" class="form-select form-select-lg bg-light border-0 fw-bold text-primary">
                            <option value="" data-principal="0" data-date="">-- Manual Entry --</option>
                            <?php while($row = $loans->fetch_assoc()): ?>
                                <option value="<?php echo $row['transaction_id']; ?>" 
                                        data-principal="<?php echo $row['principal_amount']; ?>" 
                                        data-date="<?php echo date('Y-m-d', strtotime($row['date_pawned'])); ?>">
                                    <?php echo $row['brand'] . ' ' . $row['model']; ?> (<?php echo $row['device_type']; ?> - <?php echo $row['pt_number']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <hr class="text-muted opacity-25">

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Principal Amount (₱)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-peso-sign"></i></span>
                            <input type="number" id="principalInput" class="form-control border-start-0 fw-bold" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Date Pawned</label>
                        <input type="date" id="pawnDateInput" class="form-control form-control-lg">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-primary small fw-bold">Target Payment Date</label>
                        <input type="date" id="payDateInput" class="form-control form-control-lg border-primary" value="<?php echo date('Y-m-d'); ?>">
                        <div class="form-text text-primary"><i class="fa-solid fa-info-circle"></i> Change this to see future costs!</div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-lg rounded-4 bg-dark text-white h-100">
                <div class="card-body p-5 d-flex flex-column justify-content-center position-relative overflow-hidden">
                    
                    <i class="fa-solid fa-coins position-absolute opacity-10" style="font-size: 15rem; right: -50px; bottom: -50px; color: gold;"></i>

                    <h5 class="text-white-50 text-uppercase ls-2 mb-4">Estimated Breakdown</h5>

                    <div class="row g-4 mb-4">
                        <div class="col-6">
                            <small class="text-white-50 d-block">Days Elapsed</small>
                            <h2 class="fw-bold mb-0" id="daysDisplay">0</h2>
                            <small class="text-white-50">Days</small>
                        </div>
                        <div class="col-6">
                            <small class="text-white-50 d-block">Interest Period</small>
                            <h2 class="fw-bold mb-0 text-warning" id="monthsDisplay">0</h2>
                            <small class="text-warning">Months Charged</small>
                        </div>
                    </div>

                    <div class="p-3 bg-white bg-opacity-10 rounded-3 mb-4 border border-white border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-white-50">Principal Loan</span>
                            <span class="fw-bold">₱<span id="principalDisplay">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white-50">Interest Rate</span>
                            <span class="badge bg-warning text-dark">3% Monthly</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-4">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold">Interest Due</small>
                            <h1 class="display-4 fw-bold text-warning mb-0">₱<span id="interestDisplay">0.00</span></h1>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                         <small class="text-white-50 text-uppercase fw-bold">Total Redemption Amount</small>
                         <h3 class="fw-bold">₱<span id="totalDisplay">0.00</span></h3>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // 1. Get Elements
    const loanSelect = document.getElementById('loanSelector');
    const principalIn = document.getElementById('principalInput');
    const pawnDateIn = document.getElementById('pawnDateInput');
    const payDateIn = document.getElementById('payDateInput');

    const daysOut = document.getElementById('daysDisplay');
    const monthsOut = document.getElementById('monthsDisplay');
    const principalOut = document.getElementById('principalDisplay');
    const interestOut = document.getElementById('interestDisplay');
    const totalOut = document.getElementById('totalDisplay');

    // 2. Add Event Listeners
    loanSelect.addEventListener('change', fillFromSelect);
    principalIn.addEventListener('input', calculate);
    pawnDateIn.addEventListener('change', calculate);
    payDateIn.addEventListener('change', calculate);

    // 3. Auto-Fill Function
    function fillFromSelect() {
        const option = loanSelect.options[loanSelect.selectedIndex];
        const p = option.getAttribute('data-principal');
        const d = option.getAttribute('data-date');

        if (p && d) {
            principalIn.value = p;
            pawnDateIn.value = d;
            calculate(); // Run math immediately
        } else {
            principalIn.value = '';
            pawnDateIn.value = '';
            calculate();
        }
    }

    // 4. THE MATH FUNCTION (Mirrors your PHP Logic)
    function calculate() {
        // Get Values
        let principal = parseFloat(principalIn.value) || 0;
        let start = new Date(pawnDateIn.value);
        let end = new Date(payDateIn.value);

        // Validation
        if (!pawnDateIn.value || !payDateIn.value || principal <= 0) {
            resetDisplays();
            return;
        }

        // Calculate Days Difference
        // Time difference in milliseconds
        let timeDiff = end.getTime() - start.getTime();
        // Convert to days (1000ms * 60s * 60m * 24h)
        let daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

        if (daysDiff < 0) daysDiff = 0; // Prevent negative days

        // LOGIC: 1-30 days = 1 Month, 31-60 = 2 Months
        // Fraction of a month is a full month
        let months = 0;
        if (daysDiff === 0) {
            months = 1; // Same day payment = 1 month minimum
        } else {
            months = Math.ceil(daysDiff / 30);
        }

        // Calculate Cost
        let rate = 0.03; // 3%
        let interest = principal * rate * months;
        let total = principal + interest;

        // Update UI
        daysOut.textContent = daysDiff;
        monthsOut.textContent = months;
        
        // Format Currency
        principalOut.textContent = principal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        interestOut.textContent = interest.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        totalOut.textContent = total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function resetDisplays() {
        daysOut.textContent = '0';
        monthsOut.textContent = '0';
        principalOut.textContent = '0.00';
        interestOut.textContent = '0.00';
        totalOut.textContent = '0.00';
    }
</script>

<?php include_once '../../includes/customer_footer.php'; ?>