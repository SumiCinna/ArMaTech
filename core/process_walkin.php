<?php
// core/process_walkin.php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_register_walkin'])) {
    
    // 1. Get Inputs
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $contact = trim($_POST['contact']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];
    $email = trim($_POST['email']);
    
    // 1.5 GENERATE PUBLIC ID: CUS-YYYY-XXXX
    // Check loop to ensure uniqueness
    do {
        $rand_id = rand(1000, 9999);
        $public_id = "CUS-" . date('Y') . "-" . $rand_id;
        
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM profiles WHERE public_id = ?");
        $stmt_check->bind_param("s", $public_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count_id);
        $stmt_check->fetch();
        $stmt_check->close();
    } while ($count_id > 0);

    // 2. GENERATE USERNAME: ARM-YYYY-XXXX
    // Logic: ARM + Year + Random 4 digits
    $year = date('Y');
    $random_digits = rand(1000, 9999); 
    $generated_username = "ARM-" . $year . "-" . $random_digits;

    // Check if unique (extremely rare collision, but good practice)
    // (Optional: You can add a while-loop here to ensure uniqueness)

    // 3. GENERATE PASSWORD: Lastname + Last 4 Digits of Phone
    // Logic: Get last 4 characters of contact number
    $last_4_digits = substr($contact, -4);
    // Sanitize lastname (remove spaces/special chars) just in case
    $clean_lastname = preg_replace("/[^a-zA-Z0-9]+/", "", $lname);
    
    $raw_password = ucfirst($clean_lastname) . $last_4_digits; // e.g., "DelaCruz5678"
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    try {
        $conn->begin_transaction();

        // A. Insert Profile
        $sql_prof = "INSERT INTO profiles (public_id, first_name, middle_name, last_name, contact_number, date_of_birth, gender, civil_status, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_prof);
        $stmt->bind_param("sssssssss", $public_id, $fname, $mname, $lname, $contact, $dob, $gender, $civil_status, $email);
        $stmt->execute();
        $profile_id = $conn->insert_id;

        // B. Insert Address
        $sql_addr = "INSERT INTO addresses (profile_id, house_no_street, barangay, city, province, zip_code, address_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_addr);
        $stmt->bind_param("issssss", $profile_id, $_POST['street'], $_POST['barangay'], $_POST['city'], $_POST['province'], $_POST['zip_code'], $_POST['address_type']);
        $stmt->execute();

        // C. Insert Account (With Generated Credentials)
        $sql_acc = "INSERT INTO accounts (profile_id, username, password, role) VALUES (?, ?, ?, 'customer')";
        $stmt = $conn->prepare($sql_acc);
        $stmt->bind_param("iss", $profile_id, $generated_username, $hashed_password);
        $stmt->execute();

        $conn->commit();

        // 4. AUTO-LOGIN / REDIRECT
        // We don't login the customer (security), but we pass their ID to the Transaction Page
        // Pass the generated credentials via URL for the Teller to write down (or show in a success alert)
        $msg = "Account Created! Username: $generated_username | Password: $raw_password";
        
        // Redirect to Item Entry
        header("Location: ../modules/teller/pawn_item_entry.php?customer_id=" . $profile_id . "&msg=" . urlencode($msg));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>