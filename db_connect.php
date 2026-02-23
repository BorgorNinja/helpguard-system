<?php
// db_connect.php – HelpGuard database connection
// Change credentials to match your MySQL setup

$servername = "localhost";
$db_user    = "root";       // change if needed
$db_pass    = "root";           // change if you set one
$dbname     = "helpguard";

$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

if ($conn->connect_error) {
    // Never expose raw MySQL errors in production
    error_log("HelpGuard DB Error: " . $conn->connect_error);
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

$conn->set_charset("utf8mb4");
?>
