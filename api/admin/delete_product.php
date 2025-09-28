<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Delete product from DB
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back
    header("Location: manage_products.php");
    exit();
} else {
    header("Location: manage_products.php");
    exit();
}
?>
