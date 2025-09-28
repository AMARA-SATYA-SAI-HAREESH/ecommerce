<?php
// Detect environment automatically
$server_name = $_SERVER['SERVER_NAME'];

if ($server_name == "localhost") {
    // Local XAMPP on PC
    $host     = "localhost";
    $dbname   = "ecommerce";        // your local DB name
    $user     = "root";             // XAMPP default
    $password = "";                 // XAMPP default
} else {
    // Online InfinityFree
    $host     = "sql303.infinityfree.com";
    $dbname   = "if0_39873518_ECOMMERCE";
    $user     = "if0_39873518";
    $password = "Hareesh1281"; // replace with InfinityFree password
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // optional for testing
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
