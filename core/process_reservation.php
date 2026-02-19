<?php
// core/process_reservation.php
session_start();
require_once '../config/database.php';

// Security check
if (!isset($_SESSION['account_id'])) {
    header("Location: ../customer_login.php");
    exit();
}

if (isset($_POST['btn_reserve'])) {
    
    // 1. Get Customer Profile ID
    $stmt_prof = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
    $stmt_prof->bind_param("i", $_SESSION['account_id']);
    $stmt_prof->execute();
    $profile_id = $stmt_prof->get_result()->fetch_assoc()['profile_id'];

    // 2. Gather form data
    $shop_id = intval($_POST['shop_id']);
    $reservation_amount = floatval($_POST['reservation_amount']);
    $ref_num = trim($_POST['reference_number']);

    // 3. Handle File Upload
    // IMPORTANT: Ensure the 'assets/receipts/' folder exists in your project directory!
    $target_dir = "../assets/receipts/"; 
    
    // Create a unique file name to prevent overwriting
    $file_extension = pathinfo($_FILES["receipt_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = "receipt_" . time() . "_" . $profile_id . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Basic validation for images
    $imageFileType = strtolower($file_extension);
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        header("Location: ../modules/customer/shop.php?error=Only JPG, JPEG, and PNG files are allowed.");
        exit();
    }

    if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
        
        // 4. Insert into shop_reservations table
        $sql_res = "INSERT INTO shop_reservations (shop_id, customer_profile_id, reservation_amount, reference_number, receipt_image, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt_res = $conn->prepare($sql_res);
        $stmt_res->bind_param("iidss", $shop_id, $profile_id, $reservation_amount, $ref_num, $new_filename);
        
        if ($stmt_res->execute()) {
            
            // 5. Update shop_items status to 'reserved' so no one else can buy it
            $sql_update = "UPDATE shop_items SET shop_status = 'reserved' WHERE shop_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $shop_id);
            $stmt_update->execute();

            // Success Redirect
            header("Location: ../modules/customer/shop.php?msg=Reservation submitted! Pending admin verification.");
            exit();

        } else {
            header("Location: ../modules/customer/shop.php?error=Database error during reservation.");
            exit();
        }
    } else {
        header("Location: ../modules/customer/shop.php?error=Failed to upload receipt image.");
        exit();
    }
} else {
    header("Location: ../modules/customer/shop.php");
    exit();
}
?>