<?php
// core/process_transaction.php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_save_pawn'])) {
    
    // 1. Get Inputs
    $customer_id = $_POST['customer_id'];
    $teller_id   = $_POST['teller_id'];
    $pt_number   = $_POST['pt_number'];
    $principal   = $_POST['principal']; // No deductions!
    $term_months = (int)$_POST['term_months'];

    // New Gadget Inputs
    $device_type = $_POST['device_type'];
    $brand       = $_POST['brand'];
    $model       = $_POST['model'];
    $serial      = $_POST['serial_number'];
    $storage     = $_POST['storage'] ?? 'N/A';
    $ram         = $_POST['ram'] ?? 'N/A';
    $color       = $_POST['color'] ?? 'N/A';
    $condition   = $_POST['condition_notes'];
    
    // Handle Inclusions (Array to String)
    $inclusions = isset($_POST['inclusions']) ? implode(", ", $_POST['inclusions']) : "Unit Only";

    // 2. Date Calculations
    $date_pawned = date('Y-m-d H:i:s');
    
    // Maturity = Date Pawned + Term Months
    $maturity_date = date('Y-m-d', strtotime("+$term_months months"));
    
    // Expiry = Maturity + 30 Days (Grace Period)
    $expiry_date = date('Y-m-d', strtotime("$maturity_date +30 days"));

    try {
        $conn->begin_transaction();

        // 3. Insert Transaction
        // Note: interest_rate is fixed at 3.00 in the database default, or we explicitly send it
        $sql = "INSERT INTO transactions 
                (pt_number, customer_id, teller_id, principal_amount, interest_rate, date_pawned, maturity_date, expiry_date) 
                VALUES (?, ?, ?, ?, 3.00, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidsss", $pt_number, $customer_id, $teller_id, $principal, $date_pawned, $maturity_date, $expiry_date);
        $stmt->execute();
        
        $trans_id = $conn->insert_id;

        // 4. Insert Item
        $sql_item = "INSERT INTO items (transaction_id, device_type, brand, model, serial_number, storage_capacity, ram, color, inclusions, condition_notes, appraised_value) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_item);
        $stmt->bind_param("isssssssssd", $trans_id, $device_type, $brand, $model, $serial, $storage, $ram, $color, $inclusions, $condition, $principal);
        $stmt->execute();

        $conn->commit();

        // 5. Success
        header("Location: ../modules/teller/print_ticket.php?id=" . $trans_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>