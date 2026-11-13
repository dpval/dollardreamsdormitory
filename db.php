<?php

$conn = new mysqli(
    "sql111.byetcluster.com",
    "if0_41594291",
    "1KC3PuXLWK",
    "if0_41594291_dorm_tracker"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>