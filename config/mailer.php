<?php
// core/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

function sendPawnReminderEmail($to_email, $customer_name, $pt_number, $due_date, $amount_due) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'armatechpawnshop@gmail.com'; // Your Gmail address
        $mail->Password   = 'ocme udoz kies jsfe';     // The App Password from Step 2
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('armatechpawnshop@gmail.com', 'ArMaTech Pawnshop');
        $mail->addAddress($to_email, $customer_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Action Required: Pawn Loan Due Soon ($pt_number)";
        
        // HTML Email Body
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                <h2 style='color: #0d6efd;'>ArMaTech Gadgets</h2>
                <p>Hi <strong>{$customer_name}</strong>,</p>
                <p>This is a friendly reminder that your pawn loan for Ticket <strong>{$pt_number}</strong> is approaching its due date.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0 0 10px 0;'><strong>Due Date:</strong> {$due_date}</p>
                    <p style='margin: 0;'><strong>Amount Due:</strong> ₱" . number_format($amount_due, 2) . "</p>
                </div>
                
                <p>Please visit our branch to renew your loan or redeem your item to avoid foreclosure.</p>
                <p>If you have already settled this account today, please disregard this email.</p>
                <br>
                <p style='font-size: 12px; color: #6c757d;'>Thank you,<br>The ArMaTech Team</p>
            </div>
        ";

        // Plain text fallback
        $mail->AltBody = "Hi {$customer_name}, your pawn loan ({$pt_number}) is due on {$due_date}. Amount due: ₱" . number_format($amount_due, 2) . ". Please visit ArMaTech to settle your account.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error in a real production environment
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>