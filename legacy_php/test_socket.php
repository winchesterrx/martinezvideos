<?php
$host = '200.147.61.74';
$port = 3306;
$timeout = 10;

echo "Testing TCP connection to $host:$port (Timeout: {$timeout}s)...\n";

$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
$end = microtime(true);

if ($fp) {
    echo "SUCCESS! Connection established in " . round($end - $start, 4) . " seconds.\n";
    fwrite($fp, "HEAD / HTTP/1.0\r\n\r\n"); // Send garbage to provoke a response (even error)
    echo "Packet sent. Waiting for response...\n";
    $response = fread($fp, 1024);
    echo "Response received (" . strlen($response) . " bytes)\n";
    // MySQL handshake usually starts with a packet length, sequence id, protocol version...
    if (strlen($response) > 0) {
        echo "Dump: " . bin2hex($response) . "\n";
    }
    fclose($fp);
} else {
    echo "FAILURE! Error $errno: $errstr\n";
    echo "Time taken: " . round($end - $start, 4) . " seconds.\n";
}
?>
