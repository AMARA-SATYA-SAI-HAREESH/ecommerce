<?php
session_start();
include '../includes/db.php';  // <-- path is correct from /pages

// Get product
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){
    http_response_code(404);
    echo "Product not found";
    exit;
}

// Handle the (fake) rating+review submit
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating_submit'])) {
    // No DB write per your request — just show success
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Site base styles if you have them -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- Product page styles -->
    <link rel="stylesheet" href="../css/product.css?v=3">
</head>
<body>

    <div class="page-top">
        <a href="../index.php" class="back-link">← Home</a>
    </div>

    <div class="product-container">
        <img
            src="../images/<?= htmlspecialchars($product['image']) ?>"
            alt="<?= htmlspecialchars($product['name']) ?>"
            class="product-image"
        >

        <div class="product-info">
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
            <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>

            <!-- Static display rating (you can change the number of filled stars) -->
            <div class="static-stars" aria-label="Product rating">
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star">★</span>
                <span class="star">★</span>
            </div>

            <div class="action-buttons">
                <form method="POST" action="cart.php" class="inline">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" name="add_to_cart" class="btn add-to-cart-btn">Add to Cart</button>
                </form>

                <form method="POST" action="wishlist.php" class="inline">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" name="add_to_wishlist" class="btn wishlist-btn">❤️ Wishlist</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Rate & Review (single submit button) -->
    <div class="product-container review-block">
        <div class="full-width">
            <h3>Rate & Review</h3>

            <?php if ($submitted): ?>
                <div class="success-message">Your rating & review were submitted successfully!</div>
            <?php endif; ?>

            <form method="POST" id="ratingForm" class="review-form">
                <input type="hidden" name="rating" id="ratingValue" value="0">

                <!-- Interactive stars -->
                <div class="rating-stars" id="ratingStars" aria-label="Choose a rating">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>

                <textarea name="review" placeholder="Write your feedback..." required></textarea>

                <button type="submit" name="rating_submit" class="btn submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <script>
        // Simple interactive star rating (hover to preview, click to lock)
        const stars = document.querySelectorAll('#ratingStars .star');
        const ratingValue = document.getElementById('ratingValue');
        let current = 0;

        function highlight(val) {
            stars.forEach(s => {
                const on = Number(s.dataset.value) <= Number(val);
                s.classList.toggle('hover', on);
                s.classList.toggle('selected', on);
            });
        }

        stars.forEach(star => {
            star.addEventListener('mouseover', () => highlight(star.dataset.value));
            star.addEventListener('focus', () => highlight(star.dataset.value));
            star.addEventListener('mouseout', () => highlight(current));
            star.addEventListener('click', () => {
                current = star.dataset.value;
                ratingValue.value = current;
                highlight(current);
            });
        });
    </script>
</body>
</html>
