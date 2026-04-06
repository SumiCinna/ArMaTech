<?php
// core/process_transaction.php
session_start();
require_once '../config/database.php';

// Turn on error reporting so we never get stuck on a blank page again!
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Use REQUEST_METHOD to bypass the JavaScript disabled button issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    try {
        // 1. Get Core Inputs
        $customer_id = $_POST['customer_id'];
        $teller_id   = $_POST['teller_id'];
        $pt_number   = $_POST['pt_number'];
        $principal   = $_POST['principal']; 
        $term_months = (int)$_POST['term_months'];

        // 2. Get Gadget Inputs
        $device_type = $_POST['device_type'] ?? 'Unknown';
        $brand       = $_POST['brand'] ?? 'Unknown';
        $model       = $_POST['model'] ?? 'Unknown';
        $serial      = $_POST['serial_number'] ?? 'N/A';
        $color       = $_POST['color'] ?? 'N/A';
        $condition   = $_POST['condition_notes'] ?? 'None';
        
        // Handle Inclusions (Array to String)
        $inclusions = isset($_POST['inclusions']) ? implode(", ", $_POST['inclusions']) : "Unit Only";

        // ==========================================
        // 3. HANDLE DYNAMIC API SPECS
        // ==========================================
        $extra_specs_array = isset($_POST['extra_specs']) ? $_POST['extra_specs'] : [];
        
        // Extract storage and RAM to map to your existing legacy columns (Backwards compatibility)
        $storage = $extra_specs_array['storage'] ?? 'N/A';
        $ram     = $extra_specs_array['ram'] ?? 'N/A';

        // Convert the entire dynamic specs array into a JSON string for our new column
        $extra_specs_json = !empty($extra_specs_array) ? json_encode($extra_specs_array) : null;


        // 4. Date Calculations
        $date_pawned = date('Y-m-d H:i:s');
        $maturity_date = date('Y-m-d', strtotime("+$term_months months"));
        $expiry_date = date('Y-m-d', strtotime("$maturity_date +30 days"));

        // 5. Handle Image Uploads
        $upload_dir = '../uploads/pawn_items/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $img_front = null;
        $img_back = null;
        $img_serial = null;

        // Helper function to upload
        function uploadPawnImage($file_input_name, $pt_num, $suffix, $dir) {
            if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
                $ext = pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION);
                $new_filename = $pt_num . '_' . $suffix . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $dir . $new_filename)) {
                    return $new_filename;
                }
            }
            return null;
        }

        $img_front = uploadPawnImage('img_front', $pt_number, 'front', $upload_dir);
        $img_back = uploadPawnImage('img_back', $pt_number, 'back', $upload_dir);
        $img_serial = uploadPawnImage('img_serial', $pt_number, 'serial', $upload_dir);

        // Start Transaction
        $conn->begin_transaction();

        // 6. Insert Transaction
        $sql = "INSERT INTO transactions 
                (pt_number, customer_id, teller_id, principal_amount, interest_rate, date_pawned, maturity_date, expiry_date) 
                VALUES (?, ?, ?, ?, 3.00, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siidsss", $pt_number, $customer_id, $teller_id, $principal, $date_pawned, $maturity_date, $expiry_date);
        $stmt->execute();
        
        $trans_id = $conn->insert_id;

        // 7. Insert Item (Now utilizing the extra_specs JSON column!)
        $sql_item = "INSERT INTO items 
                     (transaction_id, device_type, brand, model, serial_number, storage_capacity, ram, color, inclusions, condition_notes, appraised_value, img_front, img_back, img_serial, extra_specs) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql_item);
        // bind_param map: i (1), s (9), d (1), s (4) = 15 total bindings
        $stmt->bind_param("isssssssssdssss", $trans_id, $device_type, $brand, $model, $serial, $storage, $ram, $color, $inclusions, $condition, $principal, $img_front, $img_back, $img_serial, $extra_specs_json);
        $stmt->execute();

        // Commit Data
        $conn->commit();

        // 8. Success -> Redirect to Print Ticket
        header("Location: ../modules/teller/print_ticket.php?id=" . $trans_id);
        exit();

    } catch (Exception $e) {
        // Rollback if anything fails
        $conn->rollback();
        
        // Output a clean error message
        die("
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #fff5f5; border: 2px solid #dc3545; border-radius: 10px; color: #842029;'>
            <h3 style='margin-top:0;'>Transaction Failed!</h3>
            <p>The system encountered a database error:</p>
            <div style='background: #f8d7da; padding: 15px; border-radius: 5px; font-family: monospace;'>
                " . $e->getMessage() . "
            </div>
            <br>
            <a href='javascript:history.back()' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go Back and Try Again</a>
        </div>");
    }
} else {
    // If not POST, kick back to new pawn
    header("Location: ../modules/teller/new_pawn.php");
    exit();
}
?>