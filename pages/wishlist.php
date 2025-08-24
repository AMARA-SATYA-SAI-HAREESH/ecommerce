<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Add to wishlist
if (isset($_POST['add_to_wishlist'])) {
    $product_id = $_POST['product_id'];

    // Prevent duplicate entry
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
    }

    header("Location: wishlist.php");
    exit();
}

// Remove from wishlist
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    header("Location: wishlist.php");
    exit();
}

// Fetch wishlist products
$stmt = $conn->prepare("SELECT p.* FROM products p 
    INNER JOIN wishlist w ON p.id = w.product_id 
    WHERE w.user_id = ?");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .top-bar {
            margin-bottom: 20px;
        }
        .back-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 14px;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-button:hover {
            background: #0056b3;
        }

        .product-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px; /* same gap as before */
    justify-content: flex-start; /* align left */
}

.product {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    width: 220px; /* fixed width */
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.product img {
    margin: 10px 0;
    max-width: 100%;
    height: auto;
}

.remove-button {
    background: red;
    color: white;
    border: none;
    padding: 6px 10px;
    margin-top: auto;
    cursor: pointer;
    border-radius: 5px;
}
.remove-button:hover {
    background: darkred;
}

.button-group {
    display: flex;
    justify-content: space-between; /* keeps both buttons in one line */
    margin-top: 10px;
}

.cart-button {
    background: green;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
}
.cart-button:hover {
    background: darkgreen;
}

.remove-button {
    background: red;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
}
.remove-button:hover {
    background: darkred;
}
/* trail */
.wishlist-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
}

.wishlist-header .btn {
    background: #007bff;
    color: #fff;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
    transition: 0.3s;
    font-size: 14px;
}

.wishlist-header .btn:hover {
    background: #0056b3;
}

    </style>
</head>
<body>
    <h2>My Wishlist</h2>



    <?php if (empty($wishlist_items)) : ?>
        <p>No items in wishlist.</p>
    <?php else : ?>
        <div class="product-list">
<?php foreach ($wishlist_items as $item) : ?>
    <div class="product">
        <h3><?= htmlspecialchars($item['name']); ?></h3>
        <p>Price: $<?= number_format($item['price'], 2); ?></p>
        <img src="../images/<?= htmlspecialchars($item['image']); ?>" width="100">

        <div class="button-group">
            <!-- Add to Cart -->

<form method="POST" action="cart.php" style="display:inline;">
    <input type="hidden" name="product_id" value="<?= $item['id']; ?>">
    <input type="hidden" name="from_wishlist" value="1"> <!-- Important -->
    <button type="submit" name="add_to_cart" class="cart-button">🛒 Add to Cart</button>
</form>


            <!-- Remove from Wishlist -->
            <form method="POST" action="wishlist.php" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $item['id']; ?>">
                <button type="submit" name="remove_from_wishlist" class="remove-button">❌ Remove</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

        </div>
    <?php endif; ?>
        <div class="top-bar wishlist-header">
        <a href="https://localhost/ecommerce/index.php" class="back-button">⬅ Back to Shop</a>
        <a href="https://localhost/ecommerce/pages/cart.php" class="btn">🛒 Go to Cart</a>
         
    </div>

</body>
</html>
