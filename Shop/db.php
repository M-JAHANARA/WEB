<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "devmarket";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("خطا در اتصال به دیتابیس.");
}

$conn->set_charset("utf8mb4");