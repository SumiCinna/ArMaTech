<?php
// core/logout.php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect back to the Landing Page (or Teller Login)
header("Location: ../teller_login.php");
exit();
?>