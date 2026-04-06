<?php
require_once 'config/database.php';
$tables = $conn->query("SHOW TABLES");
while ($row = $tables->fetch_array()) {
    $tableName = $row[0];
    echo "Table: $tableName\n";
    $columns = $conn->query("SHOW COLUMNS FROM $tableName");
    while ($col = $columns->fetch_assoc()) {
        echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "\n";
}
?>
