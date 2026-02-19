<?php
if (isset($conn)) {
    $update_sql = "UPDATE transactions 
                   SET status = 'expired' 
                   WHERE status = 'active' 
                   AND expiry_date < CURDATE()";
                   
    $conn->query($update_sql);
}
?>