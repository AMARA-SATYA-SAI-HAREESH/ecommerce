<?php
session_start();
include 'includes/db.php';

// 🔹 Fetch categories for filter
$catStmt = $conn->query("SELECT DISTINCT category FROM products");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Capture filter values
$search = $_GET['search'] ?? "";
$categories_selected = $_GET['categories'] ?? [];
$min_price = $_GET['min_price'] ?? "";
$max_price = $_GET['max_price'] ?? "";
$sort = $_GET['sort'] ?? "";

// 🔹 Base query
$query = "SELECT * FROM products WHERE 1";
$params = [];

// Search
if (!empty($search)) {
    $query .= " AND name LIKE ?";
    $params[] = "%" . $search . "%";
}

// Category filter
if (!empty($categories_selected)) {
    $placeholders = implode(',', array_fill(0, count($categories_selected), '?'));
    $query .= " AND category IN ($placeholders)";
    foreach ($categories_selected as $cat) {
        $params[] = $cat;
    }
}

// Price range
if (!empty($min_price)) {
    $query .= " AND price >= ?";
    $params[] = $min_price;
}
if (!empty($max_price)) {
    $query .= " AND price <= ?";
    $params[] = $max_price;
}

// Sorting
if ($sort === "low_high") {
    $query .= " ORDER BY price ASC";
} elseif ($sort === "high_low") {
    $query .= " ORDER BY price DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<header>
    <div class="header-container">
        <h1>Welcome to Our Store</h1>
        <nav>
            <a href="pages/login.php">Login</a>
            <a href="pages/register.php">Register</a>
            <a href="pages/cart.php" class="cart-link">
                <img src="images/cart-icon.png" alt="Cart" class="cart-icon"> Cart
            </a>
            <a href="pages/wishlist.php">Wishlist</a>
            <a href="pages/logout.php" class="logout-button">Logout</a>
        </nav>
    </div>
</header>

<div class="main-container">
    <main>
        <h2 style="text-align:center">Products</h2>

        <!-- 🔹 Search & Filter -->
        <div class="search-bar text-center my-3">
            <form method="GET" action="index.php" class="d-inline-block">
                <input type="text" name="search" placeholder="Search products..."
                       value="<?= htmlspecialchars($search); ?>"
                       class="form-control d-inline-block" style="width:250px; display:inline-block;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <!-- Filter Button -->
            <button class="btn btn-secondary ms-2" data-bs-toggle="offcanvas" data-bs-target="#filterDrawer">
                Filters
            </button>
        </div>

        <!-- 🔹 Product List -->
        <div class="product-list">
            <?php if (empty($products)) : ?>
                <p>No products available.</p>
            <?php else : ?>
                <?php foreach ($products as $product) : ?>
                     
                    <div class="product">
    <a href="pages/product.php?id=<?= $product['id']; ?>" style="text-decoration:none; color:inherit;">
        

<?php if (!empty($product['image'])) : ?>
    <img src="images/<?= htmlspecialchars($product['image']); ?>" 
         alt="<?= htmlspecialchars($product['name']); ?>" 
         class="product-image">
<?php endif; ?>

<!-- ⭐ Static/random star rating -->
<div class="product-rating">
    <?php
    // Simulate different ratings (1–5) for now
    $rating = rand(1,5);
    for ($i = 1; $i <= 5; $i++) {
        echo $i <= $rating 
            ? '<span class="star filled">★</span>'
            : '<span class="star">★</span>';
    }
    ?>
</div>

        <h3><?= htmlspecialchars($product['name']); ?></h3>
    </a>
    <p>Price: $<?= number_format($product['price'], 2); ?></p>
    <p><?= htmlspecialchars($product['description']); ?></p>

    <!-- Wishlist -->
    <form method="POST" action="pages/wishlist.php" style="display:inline;">
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
        <button type="submit" name="add_to_wishlist" class="wishlist-button">❤️ Wishlist</button>
    </form>

    <!-- Cart -->
    <form method="POST" action="pages/cart.php" style="display:inline;">
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
        <button type="submit" name="add_to_cart" class="add-to-cart-button">🛒 Add to Cart</button>
    </form>
</div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<footer>
    <p>&copy; <?= date('Y'); ?> Online Store. All rights reserved.</p>
</footer>

<!-- 🔹 Filter Drawer -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterDrawer">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filter Options</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <form method="GET" action="index.php">
        <div class="offcanvas-body">

            <!-- Categories -->
            <h6>Categories</h6>
            <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="categories[]"
                           value="<?= htmlspecialchars($cat['category']); ?>"
                        <?= in_array($cat['category'], $categories_selected) ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= htmlspecialchars($cat['category']); ?></label>
                </div>
            <?php endforeach; ?>
            <hr>

            <!-- Price Range -->
            <h6>Price Range</h6>
            <div class="d-flex">
                <input type="number" name="min_price" class="form-control me-2"
                       placeholder="Min" value="<?= htmlspecialchars($min_price) ?>">
                <input type="number" name="max_price" class="form-control"
                       placeholder="Max" value="<?= htmlspecialchars($max_price) ?>">
            </div>
            <hr>

            <!-- Sorting -->
            <h6>Sort By</h6>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sort" value="low_high"
                    <?= $sort === 'low_high' ? 'checked' : '' ?>>
                <label class="form-check-label">Price: Low → High</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="sort" value="high_low"
                    <?= $sort === 'high_low' ? 'checked' : '' ?>>
                <label class="form-check-label">Price: High → Low</label>
            </div>
        </div>

        <!-- Apply + Remove -->
        <div class="offcanvas-footer p-3">
            <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filters</button>
            <a href="index.php" class="btn btn-outline-danger w-100">Remove Filters</a>
        </div>
    </form>
</div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
