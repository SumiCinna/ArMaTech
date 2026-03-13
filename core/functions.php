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
 * @param string $last_renewed_date The date string (Y-m-d H:i:s)
 * @return array The calculation breakdown
 */
function calculatePawnInterest($principal, $last_renewed_date) {
    
    // 1. SETTINGS
    $monthly_rate = 0.03; // 3% Interest
    
    // 2. CALCULATE DAYS ELAPSED
    $start_date = new DateTime($last_renewed_date);
    $current_date = new DateTime(); // NOW
    
    // Calculate the difference
    $interval = $start_date->diff($current_date);
    $days_elapsed = $interval->days;

    // 3. THE LOGIC: "Fraction of a month is a full month"
    if ($days_elapsed == 0) {
        $months_to_pay = 1;
    } else {
        $months_to_pay = ceil($days_elapsed / 30);
    }

    // 4. CALCULATE AMOUNT
    $interest_amount = $principal * $monthly_rate * $months_to_pay;
    $total_due = $principal + $interest_amount;

    // 5. PENALTY CHECK
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