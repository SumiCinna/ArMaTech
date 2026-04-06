<?php
require_once 'config/database.php';
$res = $conn->query("SELECT a.username, p.full_name FROM accounts a JOIN profiles p ON a.profile_id = p.profile_id WHERE a.role='teller' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    echo "Username: " . $row['username'] . "\nFull Name: " . $row['full_name'];
} else {
    echo "No teller found.";
}
?>
