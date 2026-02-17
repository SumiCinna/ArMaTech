<?php
// core/process_add_staff.php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_add_staff'])) {
    
    // 1. Collect Data from Form
    $uname = $_POST['username'];
    $role  = $_POST['role'];
    $pass  = "Armatech123"; // Temp password
    // $pass_hash = password_hash($pass, PASSWORD_DEFAULT); // Use this if you are hashing

    $fname = $_POST['first_name'];
    $mname = $_POST['middle_name'];
    $lname = $_POST['last_name'];
    $dob   = $_POST['dob'];
    $gender = $_POST['gender'];
    $civil = $_POST['civil_status'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    // Emergency Contact
    $emerg_name = $_POST['emergency_contact_name']; // Make sure your form has this field
    $emerg_num  = $_POST['emergency_contact_phone']; // Make sure your form has this field

    // Address (For separate table)
    $street   = $_POST['street'];
    $barangay = $_POST['barangay'];
    $city     = $_POST['city'];
    $province = $_POST['province'];
    $zip      = $_POST['zip_code'];

    // 2. GENERATE SEQUENTIAL ID (EMP-YYYY-0001)
    $year = date('Y');
    
    // Find the last ID created this year
    $search_pattern = "EMP-" . $year . "-%";
    $sql_check = "SELECT public_id FROM profiles WHERE public_id LIKE ? ORDER BY public_id DESC LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $search_pattern);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $last_row = $result_check->fetch_assoc();
        $last_id_str = $last_row['public_id']; 
        $parts = explode('-', $last_id_str);
        $last_num = intval(end($parts)); 
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }

    $public_id = "EMP-" . $year . "-" . str_pad($new_num, 4, '0', STR_PAD_LEFT);

    try {
        $conn->begin_transaction();

        // 3. Insert Profile
        // Columns: public_id, first_name, middle_name, last_name, date_of_birth, gender, civil_status, contact_number, email, emergency_contact_name, emergency_contact_phone, date_hired
        $sql_prof = "INSERT INTO profiles 
            (public_id, first_name, middle_name, last_name, date_of_birth, gender, civil_status, contact_number, email, emergency_contact_name, emergency_contact_phone, date_hired) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
        
        $stmt = $conn->prepare($sql_prof);
        $stmt->bind_param("sssssssssss", $public_id, $fname, $mname, $lname, $dob, $gender, $civil, $phone, $email, $emerg_name, $emerg_num);
        $stmt->execute();
        $profile_id = $conn->insert_id;

        // 4. Insert Address (Separate Table)
        $sql_addr = "INSERT INTO addresses (profile_id, house_no_street, barangay, city, province, zip_code, address_type) VALUES (?, ?, ?, ?, ?, ?, 'Present')";
        $stmt = $conn->prepare($sql_addr);
        $stmt->bind_param("isssss", $profile_id, $street, $barangay, $city, $province, $zip);
        $stmt->execute();

        // 5. Insert Account
        // force_change = 1 means they MUST change password on login
        $sql_acc = "INSERT INTO accounts (profile_id, username, password, role, force_change) 
                    VALUES (?, ?, ?, ?, 1)";
        
        $stmt = $conn->prepare($sql_acc);
        $stmt->bind_param("isss", $profile_id, $uname, $pass, $role);
        $stmt->execute();

        $conn->commit();

        header("Location: ../modules/admin/manage_staff.php?msg=Employee added successfully: " . $public_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error adding staff: " . $e->getMessage());
    }
}
?>