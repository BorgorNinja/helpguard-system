<?php
// db_connect.php – SenTri database connection
// Change credentials to match your MySQL setup

$servername = "localhost";
$db_user    = "root";       // change if needed
$db_pass    = "";           // change if you set one
$dbname     = "sentri";

$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

if ($conn->connect_error) {
    // Never expose raw MySQL errors in production
    error_log("SenTri DB Error: " . $conn->connect_error);
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

$conn->set_charset("utf8mb4");
?>
