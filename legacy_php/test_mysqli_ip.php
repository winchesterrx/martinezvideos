<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '200.147.61.74';
$user = 'winchester123';
$pass = '@Saopaulop45';
$db   = 'martinezvideo';

echo "Attempting mysqli connection to $host...\n";
$start = microtime(true);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    echo "SUCCESS! Connected via mysqli in " . round(microtime(true) - $start, 4) . "s\n";
    echo "Server Info: " . $conn->server_info . "\n";
    $conn->close();
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
?>
