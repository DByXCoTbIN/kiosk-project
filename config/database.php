<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schedule_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");
    
    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>