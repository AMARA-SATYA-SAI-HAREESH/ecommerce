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

// 🔹 Base query (with avg rating)
$query = "SELECT p.*, IFNULL(ROUND(AVG(r.rating),1), 0) AS avg_rating 
          FROM products p 
          LEFT JOIN reviews r ON p.id = r.product_id 
          WHERE 1";
$params = [];

// Search
if (!empty($search)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%" . $search . "%";
}

// Category filter
if (!empty($categories_selected)) {
    $placeholders = implode(',', array_fill(0, count($categories_selected), '?'));
    $query .= " AND p.category IN ($placeholders)";
    foreach ($categories_selected as $cat) {
        $params[] = $cat;
    }
}

// Price range
if (!empty($min_price)) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}
if (!empty($max_price)) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}

// ✅ Group after filters
$query .= " GROUP BY p.id";

// Sorting
if ($sort === "low_high") {
    $query .= " ORDER BY p.price ASC";
} elseif ($sort === "high_low") {
    $query .= " ORDER BY p.price DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Top Rated Products
$topRatedStmt = $conn->query("
    SELECT p.*, IFNULL(ROUND(AVG(r.rating),1), 0) AS avg_rating
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    HAVING avg_rating >= 4
    ORDER BY avg_rating DESC
    LIMIT 10
");
$topRated = $topRatedStmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Trending Products (using orders table)
$trendingStmt = $conn->query("
    SELECT p.*, COUNT(o.id) AS order_count, IFNULL(ROUND(AVG(r.rating),1), 0) AS avg_rating
    FROM products p
    INNER JOIN orders o ON p.id = o.product_id
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY order_count DESC
    LIMIT 10
");
$trending = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- //login first start -->
<?php
 // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page in pages folder
    header("Location: pages/login.php");
    exit(); // Stop further execution
}
?>

<!-- //login first end -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ⭐ Trending & Top Rated Sections */
        .scroll-container, .trending-grid {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 15px;
        }
        .scroll-card, .trend-card {
            min-width: 250px;
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            text-align: center;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            position: relative;
        }
        .scroll-card:hover, .trend-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
        }
        .scroll-card img, .trend-card img {
            width: 100%;
            height: 200px;
            object-fit:contain;
            border-radius: 12px;
        }
        .stars span {
            color: #ccc;
            font-size: 1.2em;
            transition: color 0.3s;
        }
        .stars .filled {
            color: gold;
            text-shadow: 0 0 8px rgba(255,215,0,0.7);
        }
        .badge-top, .badge-trend {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff4757;
            color: #fff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            animation: pulse 1.5s infinite;
        }
        .badge-top { background: #ffa502; }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 0.9; }
        }
        /* Floating action buttons */
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .actions button {
            border: none;
            background: #007bff;
            color: #fff;
            padding: 10px 14px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        .actions button:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        /* Quick View Modal */
        .modal-img {
            max-width: 100%;
            border-radius: 10px;
        }
    </style>
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
            <a href="pages/profile.php">Profile</a>
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

                            <!-- ⭐ Real star rating -->
                            <div class="product-rating">
                                <?php
                                $rating = $product['avg_rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= round($rating) 
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

         <!-- your existing search/filter and product list (unchanged) -->
          

        <!-- ⭐ Top Rated Section -->
        <div class="top-rated my-5 container">
            <h2 class="text-center mb-4">⭐ Top Rated Products</h2>
            <?php if (empty($topRated)): ?>
                <p class="text-center">No top rated products yet.</p>
            <?php else: ?>
                <div class="scroll-container">
                    <?php foreach ($topRated as $product): ?>
                        <div class="scroll-card">
                            <span class="badge-top">⭐ Top Rated</span>
                            <img src="images/<?= htmlspecialchars($product['image']); ?>" 
                                 alt="<?= htmlspecialchars($product['name']); ?>">
                            <h4><?= htmlspecialchars($product['name']); ?></h4>
                            <div class="stars">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <span class="<?= $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p>$<?= number_format($product['price'], 2); ?></p>
                            <div class="actions">
                                <form method="POST" action="pages/wishlist.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <button type="submit" name="add_to_wishlist">❤️</button>
                                </form>
                                <form method="POST" action="pages/cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <button type="submit" name="add_to_cart">🛒</button>
                                </form>
                                <button type="button" onclick="quickView('<?= addslashes($product['name']); ?>','<?= addslashes($product['description']); ?>','images/<?= htmlspecialchars($product['image']); ?>','<?= number_format($product['price'],2); ?>')">👁</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 🔥 Trending Section -->
        <div class="trending my-5 container">
            <h2 class="text-center mb-4">🔥 Trending Products</h2>
            <?php if (empty($trending)): ?>
                <p class="text-center">No trending products yet.</p>
            <?php else: ?>
                <div class="scroll-container">
                    <?php foreach ($trending as $product): ?>
                        <div class="trend-card">
                            <span class="badge-trend">🔥 Trending</span>
                            <img src="images/<?= htmlspecialchars($product['image']); ?>" 
                                 alt="<?= htmlspecialchars($product['name']); ?>">
                            <h4><?= htmlspecialchars($product['name']); ?></h4>
                            <p>$<?= number_format($product['price'], 2); ?></p>
                            <small>Orders: <?= (int)($product['order_count'] ?? 0); ?></small>
                            <div class="stars">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <span class="<?= $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="actions">
                                <form method="POST" action="pages/wishlist.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <button type="submit" name="add_to_wishlist">❤️</button>
                                </form>
                                <form method="POST" action="pages/cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <button type="submit" name="add_to_cart">🛒</button>
                                </form>
                                <button type="button" onclick="quickView('<?= addslashes($product['name']); ?>','<?= addslashes($product['description']); ?>','images/<?= htmlspecialchars($product['image']); ?>','<?= number_format($product['price'],2); ?>')">👁</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title" id="qvTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qvImage" class="modal-img mb-3">
        <p id="qvDesc"></p>
        <h4 id="qvPrice" class="text-success"></h4>
      </div>
    </div>
  </div>
</div>

<footer>
    <p>&copy; <?= date('Y'); ?> Online Store. All rights reserved.</p>
</footer>

<!-- 🔹 Filter Drawer (unchanged) -->
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


 <!-- keep your drawer code -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function quickView(name, desc, img, price) {
    document.getElementById("qvTitle").innerText = name;
    document.getElementById("qvDesc").innerText = desc;
    document.getElementById("qvImage").src = img;
    document.getElementById("qvPrice").innerText = "$" + price;
    new bootstrap.Modal(document.getElementById("quickViewModal")).show();
}
</script>
</body>
</html>