<?php
session_start();
unset($_SESSION['live_link']);
echo json_encode(["success" => true]);
?>
