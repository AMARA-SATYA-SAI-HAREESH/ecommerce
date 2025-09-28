<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['confirm_order'])) {
    $fullname = $_POST['fullname'];
    $address = $_POST['address'];
    $payment = $_POST['payment_method'];

    // fetch cart items
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit();
    }

    $order_ids = []; // store new order IDs

    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $item['product_id'], $item['quantity']]);

        $order_ids[] = $conn->lastInsertId(); // capture inserted order ID
    }

    // clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // save order IDs & customer details in session
    $_SESSION['last_order_ids'] = $order_ids;
    $_SESSION['order_name'] = $fullname;
    $_SESSION['order_address'] = $address;
    $_SESSION['order_payment'] = $payment;

    header("Location: order_success.php");
    exit();
} else {
    header("Location: checkout.php");
    exit();
}
?>
