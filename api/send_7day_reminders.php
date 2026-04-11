<?php
// api/send_7day_reminders.php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php';     // Your PHPMailer setup
require_once '../core/functions.php';  // For calculatePawnInterest

// Optional: Security check to ensure only Admins or Managers can trigger this
if (!isset($_SESSION['account_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

date_default_timezone_set('Asia/Manila');

// Look for active transactions maturing starting today up to the next 7 days
$sql = "SELECT t.transaction_id, t.pt_number, t.maturity_date, t.principal_amount, t.date_pawned, t.last_renewed_date,
               p.first_name, p.last_name, p.email
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        WHERE t.status = 'active' 
        AND t.maturity_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 7 DAY)
        AND p.email IS NOT NULL AND p.email != ''";

$result = $conn->query($sql);

$sent_count = 0;
$fail_count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customer_name = $row['first_name'] . ' ' . $row['last_name'];
        $formatted_date = date('F d, Y', strtotime($row['maturity_date']));
        
        $start_date = isset($row['last_renewed_date']) ? $row['last_renewed_date'] : $row['date_pawned'];
        $calc = calculatePawnInterest($row['principal_amount'], $start_date);
        
        // Use your PHPMailer function
        $is_sent = sendPawnReminderEmail($row['email'], $customer_name, $row['pt_number'], $formatted_date, $calc['total']);

        if ($is_sent) {
            $sent_count++;
        } else {
            $fail_count++;
        }
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Successfully sent $sent_count reminder(s). Failed: $fail_count."
    ]);
} else {
    echo json_encode([
        'status' => 'info', 
        'message' => 'No accounts are due in the next 7 days. No emails sent.'
    ]);
}
?>