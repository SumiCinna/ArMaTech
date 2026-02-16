<?php
// core/functions.php

/**
 * Calculates Pawn Interest based on the "Fraction of a Month" Rule.
 * * Rules:
 * 1. Interest Rate is fixed at 3% per month.
 * 2. 1 to 30 days = 1 Month Interest.
 * 3. 31 to 60 days = 2 Months Interest.
 * 4. Any fraction of a month is counted as a full month.
 *
 * @param float $principal The loan amount (e.g. 10000)
 * @param string $date_pawned The date string (Y-m-d H:i:s)
 * @return array The calculation breakdown
 */
function calculatePawnInterest($principal, $date_pawned) {
    
    // 1. SETTINGS
    $monthly_rate = 0.03; // 3% Interest
    
    // 2. CALCULATE DAYS ELAPSED
    // We use DateTime objects for accurate calculation
    $start_date = new DateTime($date_pawned);
    $current_date = new DateTime(); // NOW
    
    // Calculate the difference
    $interval = $start_date->diff($current_date);
    $days_elapsed = $interval->days;

    // 3. THE LOGIC: "Fraction of a month is a full month"
    if ($days_elapsed == 0) {
        // If they redeem on the SAME DAY, charge 1 month minimum
        $months_to_pay = 1;
    } else {
        // Divide days by 30 and round UP (ceil)
        // Example: 31 days / 30 = 1.03 -> ceil -> 2 Months
        // Example: 25 days / 30 = 0.83 -> ceil -> 1 Month
        $months_to_pay = ceil($days_elapsed / 30);
    }

    // 4. CALCULATE AMOUNT
    $interest_amount = $principal * $monthly_rate * $months_to_pay;
    $total_due = $principal + $interest_amount;

    // 5. PENALTY CHECK (Optional - Logic for Expired Items)
    // If item is expired (over 120 days), some shops add a penalty. 
    // For now, we'll keep it simple (No Penalty).
    $penalty = 0;

    // 6. RETURN DATA
    return [
        'principal' => $principal,
        'days'      => $days_elapsed,
        'months'    => $months_to_pay,
        'rate'      => '3%',
        'interest'  => $interest_amount,
        'penalty'   => $penalty,
        'total'     => $total_due
    ];
}
?>