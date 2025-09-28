<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['last_order_ids'])) {
    header("Location: ../index.php");
    exit();
}

$order_ids = $_SESSION['last_order_ids'];
$name = $_SESSION['order_name'];
$address = $_SESSION['order_address'];
$payment = $_SESSION['order_payment'];

// fetch only new orders
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));
$stmt = $conn->prepare("
    SELECT o.*, p.name, p.price, p.image 
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.id IN ($placeholders)
");
$stmt->execute($order_ids);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Success</title>
    <style>
        body { font-family: Arial; background:#f4f4f9; }
        .container {
            max-width: 800px; margin: 30px auto; padding: 20px;
            background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: green; }
        .order { display:flex; align-items:center; margin-bottom:15px; }
        .order img { width:80px; margin-right:15px; border-radius:8px; }
        .total { text-align:right; font-size:18px; font-weight:bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>✅ Order Placed Successfully!</h2>
    <p><b>Name:</b> <?= htmlspecialchars($name) ?></p>
    <p><b>Address:</b> <?= htmlspecialchars($address) ?></p>
    <p><b>Payment:</b> <?= htmlspecialchars($payment) ?></p>
    <hr>

    <?php
    $total = 0;
    foreach ($orders as $order):
        $total += $order['price'] * $order['quantity'];
    ?>
        <div class="order">
            <img src="../images/<?= htmlspecialchars($order['image']); ?>" alt="">
            <div>
                <p><?= htmlspecialchars($order['name']); ?> (x<?= $order['quantity']; ?>)</p>
                <p>$<?= number_format($order['price'] * $order['quantity'], 2); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <hr>
    <p class="total">Grand Total: $<?= number_format($total, 2); ?></p>
    <p style="text-align:center;"><a href="../index.php">Continue Shopping</a></p>
</div>
</body>
</html>
