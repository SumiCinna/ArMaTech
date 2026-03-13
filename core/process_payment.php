<?php
// core/process_payment.php
session_start();

// 1. ENABLE ERROR REPORTING FOR SQL (Crucial for debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../config/database.php';

if (isset($_POST['btn_process_payment'])) {
    
    $trans_id = $_POST['trans_id'];
    $payment_type = $_POST['payment_type'];
    $teller_id = $_SESSION['account_id']; // Ensure this session exists!

    // Validate Teller ID
    if (empty($teller_id)) {
        die("Error: You are not logged in. Please relogin.");
    }
    
    try {
        $conn->begin_transaction();

        // 2. FETCH CURRENT DATA (To track history)
        $sql_get = "SELECT principal_amount FROM transactions WHERE transaction_id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("i", $trans_id);
        $stmt_get->execute();
        $current_data = $stmt_get->get_result()->fetch_assoc();
        
        $old_principal = $current_data['principal_amount'];
        $new_principal = $old_principal; // Default (if no change)
        $amount_paid = 0;

        // ... (inside core/process_payment.php) ...

        // 3. PROCESS UPDATES BASED ON TYPE
        if ($payment_type == 'interest_only') {
            // --- RENEWAL ---
            $amount_paid = $_POST['amount_paid'];
            
            // Only update last_renewed_date and push deadlines forward
            $sql = "UPDATE transactions 
                    SET last_renewed_date = CURRENT_TIMESTAMP,
                        maturity_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                        expiry_date = DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                    WHERE transaction_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $trans_id);
            $stmt->execute();

        } elseif ($payment_type == 'partial_payment') {
            // --- PARTIAL ---
            $interest = $_POST['interest_amount'];
            $deduction = $_POST['principal_deduction'];
            $amount_paid = $interest + $deduction;
            
            $new_principal = $old_principal - $deduction;

            // Shrink principal, update last_renewed_date, and push deadlines forward
            $sql = "UPDATE transactions 
                    SET principal_amount = ?,
                        last_renewed_date = CURRENT_TIMESTAMP,
                        maturity_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                        expiry_date = DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                    WHERE transaction_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_principal, $trans_id);
            $stmt->execute();
            
        // ... (rest of your code) ...

        } elseif ($payment_type == 'full_redemption') {
            // --- FULL REDEMPTION ---
            $amount_paid = $_POST['amount_paid'];
            $new_principal = 0.00; // Debt is cleared

            $sql = "UPDATE transactions SET status = 'redeemed' WHERE transaction_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $trans_id);
            $stmt->execute();
        }

        // 4. INSERT PAYMENT RECORD (With History!)
        // Make sure your database table 'payments' has these columns: old_principal, new_principal
        $sql_pay = "INSERT INTO payments 
                    (transaction_id, teller_id, payment_type, amount_paid, old_principal, new_principal, date_paid) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql_pay);
        // Types: i=int, i=int, s=string, d=double, d=double, d=double
        $stmt->bind_param("iisddd", $trans_id, $teller_id, $payment_type, $amount_paid, $old_principal, $new_principal);
        $stmt->execute();
        
        $payment_id = $conn->insert_id;
        $conn->commit();

        // 5. REDIRECT
        header("Location: ../modules/teller/print_receipt.php?payment_id=" . $payment_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Show error on the page if redirect fails
        echo "Error Processing Transaction: " . $e->getMessage();
        // Or redirect back
        // header("Location: ../modules/teller/redeem_process.php?id=$trans_id&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If accessed directly without clicking button
    header("Location: ../modules/teller/redeem.php");
    exit();
}
?>