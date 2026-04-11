<?php
// cron/daily_reminders.php
date_default_timezone_set('Asia/Manila');
// This script is meant to be run automatically by the server, not accessed via browser.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/core/functions.php'; // Assuming your calculatePawnInterest is here

// 1. Find all ACTIVE transactions that mature in exactly 3 days
$sql = "SELECT t.transaction_id, t.pt_number, t.maturity_date, t.principal_amount, t.date_pawned, t.last_renewed_date,
               p.first_name, p.last_name, p.email
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        WHERE t.status = 'active' 
        AND t.maturity_date = CURDATE() + INTERVAL 2 DAY
        AND p.email IS NOT NULL AND p.email != ''";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " customers to remind today.\n";

    while ($row = $result->fetch_assoc()) {
        
        $customer_name = $row['first_name'] . ' ' . $row['last_name'];
        $formatted_date = date('F d, Y', strtotime($row['maturity_date']));
        
        // Calculate the current total due (Principal + Interest)
        $start_date = isset($row['last_renewed_date']) ? $row['last_renewed_date'] : $row['date_pawned'];
        $calc = calculatePawnInterest($row['principal_amount'], $start_date);
        $amount_due = $calc['total'];

        // Send the email
        $is_sent = sendPawnReminderEmail($row['email'], $customer_name, $row['pt_number'], $formatted_date, $amount_due);

        if ($is_sent) {
            echo "SUCCESS: Reminder sent to {$row['email']} for PT {$row['pt_number']}\n";
            
            // Optional: Insert a log into an 'email_logs' table here so you know it was sent
        } else {
            echo "FAILED: Could not send to {$row['email']} for PT {$row['pt_number']}\n";
        }
    }
} else {
    echo "No reminders to send today.\n";
}
?>