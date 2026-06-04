<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "clinic_db";
$port = 3307;

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("Connection Failed : " . $conn->connect_error);
}

