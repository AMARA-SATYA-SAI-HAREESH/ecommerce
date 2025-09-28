<!-- <?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f9f9f9; }
        .container {
            max-width: 700px; margin: 30px auto; padding: 20px;
            background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; }
        label { font-weight: bold; margin-top: 10px; display: block; }
        input, textarea, select {
            width: 100%; padding: 10px; margin: 5px 0 15px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            width: 100%; padding: 12px; background: #28a745;
            border: none; color: white; font-size: 16px;
            border-radius: 5px; cursor: pointer;
        }
        button:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>Checkout Details</h2>
    <form method="POST" action="place_order.php">
        <label>Full Name</label>
        <input type="text" name="fullname" required>

        <label>Shipping Address</label>
        <textarea name="address" required></textarea>

        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="COD">Cash on Delivery</option>
            <option value="Credit Card">Credit Card</option>
            <option value="UPI">UPI</option>
        </select>

        <button type="submit" name="confirm_order">Place Order</button>
    </form>
</div>
</body>
</html> -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: Arial; background:#f4f4f9; }
        .container { max-width: 600px; margin: 40px auto; background:white; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align:center; }
        label { display:block; margin-top:10px; }
        input, textarea, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:5px; }
        button { margin-top:20px; width:100%; padding:10px; background:#28a745; color:white; border:none; border-radius:5px; cursor:pointer; }
        button:hover { background:#218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>Checkout</h2>
    <form method="POST" action="place_order.php">
        <label>Full Name:</label>
        <input type="text" name="fullname" required>

        <label>Address:</label>
        <textarea name="address" required></textarea>

        <label>Payment Method:</label>
        <select name="payment_method" required>
            <option value="COD">Cash on Delivery</option>
            <option value="Card">Credit/Debit Card</option>
            <option value="UPI">UPI</option>
        </select>

        <button type="submit" name="confirm_order">Place Order</button>
    </form>
</div>
</body>
</html>
