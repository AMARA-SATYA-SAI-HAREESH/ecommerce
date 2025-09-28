<?php
session_start();
include '../includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT username AS name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch past orders
$orders = [];
if ($conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0) {
    $stmt = $conn->prepare("SELECT o.*, p.name, p.image, p.price 
                            FROM orders o 
                            JOIN products p ON o.product_id = p.id 
                            WHERE o.user_id = ? ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch wishlist
$stmt = $conn->prepare("SELECT w.*, p.name, p.image, p.price 
                        FROM wishlist w 
                        JOIN products p ON w.product_id = p.id 
                        WHERE w.user_id = ?");
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews
$reviews = [];
if ($conn->query("SHOW TABLES LIKE 'reviews'")->rowCount() > 0) {
    $stmt = $conn->prepare("SELECT r.*, p.name, p.image 
                            FROM reviews r 
                            JOIN products p ON r.product_id = p.id 
                            WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7fa; margin: 0; }
        header { background: linear-gradient(135deg, #007bff, #6610f2); color: white; padding: 15px; }
        header h1 { margin: 0; font-size: 26px; }
        header nav a { color: white; margin-left: 15px; text-decoration: none; font-weight: 500; }
        header nav a:hover { text-decoration: underline; }

        .profile-header { text-align: center; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); margin: 20px auto; width: 90%; }
        .profile-header h2 { font-size: 24px; margin-bottom: 10px; }

        /* Tabs */
        .nav-tabs .nav-link.active { background: #007bff; color: white; border-radius: 6px; }
        .tab-content { margin-top: 20px; }

        /* Cards */
        .card { transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .card img { height: 180px; object-fit: cover; border-top-left-radius: 10px; border-top-right-radius: 10px; }

        /* Search & Filters */
        .filter-bar { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px auto; width: 90%; display: flex; gap: 15px; align-items: center; }
        .filter-bar input, .filter-bar select { border-radius: 6px; padding: 8px; }

        /* Fade animation for filtering */
        .hidden { display: none !important; }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
<header>
    <div class="d-flex justify-content-between align-items-center">
        <h1>My Profile</h1>
        <nav>
            <a href="../index.php">Home</a>
            <a href="cart.php">Cart</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<div class="profile-header">
    <h2>Welcome, <?= htmlspecialchars($user['name']); ?> 👋</h2>
    <p>Email: <?= htmlspecialchars($user['email']); ?></p>
</div>

<!-- 🔎 Global Search & Filter -->
<div class="filter-bar">
    <input type="text" id="searchInput" class="form-control" placeholder="Search by product name...">
    <input type="number" id="minPrice" class="form-control" placeholder="Min Price">
    <input type="number" id="maxPrice" class="form-control" placeholder="Max Price">
    <select id="ratingFilter" class="form-select">
        <option value="">All Ratings</option>
        <option value="5">⭐⭐⭐⭐⭐ 5+</option>
        <option value="4">⭐⭐⭐⭐ 4+</option>
        <option value="3">⭐⭐⭐ 3+</option>
        <option value="2">⭐⭐ 2+</option>
        <option value="1">⭐ 1+</option>
    </select>
    <input type="date" id="startDate" class="form-control">
    <input type="date" id="endDate" class="form-control">
</div>

<!-- Tabs -->
<div class="container my-4">
    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#orders">📦 Orders</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#wishlist">❤️ Wishlist</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#reviews">⭐ Reviews</a></li>
    </ul>
    <div class="tab-content fade-in">
        
        <!-- Orders -->
        <div class="tab-pane fade show active" id="orders">
            <div class="row">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="col-md-3 profile-item" 
                             data-name="<?= strtolower($order['name']); ?>" 
                             data-price="<?= $order['price']; ?>"
                             data-date="<?= $order['created_at']; ?>">
                            <div class="card" onclick="window.location='product.php?id=<?= $order['product_id']; ?>'">
                                <img src="../../images/<?= htmlspecialchars($order['image']); ?>" class="card-img-top">
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($order['name']); ?></h5>
                                    <p>$<?= number_format($order['price'], 2); ?> × <?= $order['quantity']; ?></p>
                                    <small><?= $order['created_at']; ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p>No orders yet.</p><?php endif; ?>
            </div>
        </div>

        <!-- Wishlist -->
        <div class="tab-pane fade" id="wishlist">
            <div class="row">
                <?php if (!empty($wishlist)): ?>
                    <?php foreach ($wishlist as $item): ?>
                        <div class="col-md-3 profile-item" 
                             data-name="<?= strtolower($item['name']); ?>" 
                             data-price="<?= $item['price']; ?>">
                            <div class="card" onclick="window.location='product.php?id=<?= $item['product_id']; ?>'">
                                <img src="../../images/<?= htmlspecialchars($item['image']); ?>" class="card-img-top">
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($item['name']); ?></h5>
                                    <p>$<?= number_format($item['price'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p>No wishlist items.</p><?php endif; ?>
            </div>
        </div>

        <!-- Reviews -->
        <div class="tab-pane fade" id="reviews">
            <div class="row">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="col-md-4 profile-item" 
                             data-name="<?= strtolower($review['name']); ?>" 
                             data-rating="<?= $review['rating']; ?>"
                             data-date="<?= $review['created_at']; ?>">
                            <div class="card" onclick="window.location='product.php?id=<?= $review['product_id']; ?>'">
                                <img src="../../images/<?= htmlspecialchars($review['image']); ?>" class="card-img-top">
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($review['name']); ?></h5>
                                    <p>⭐ <?= $review['rating']; ?>/5</p>
                                    <p><?= htmlspecialchars($review['review']); ?></p>
                                    <small><?= $review['created_at']; ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p>No reviews yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('searchInput').addEventListener('input', filterItems);
document.getElementById('minPrice').addEventListener('input', filterItems);
document.getElementById('maxPrice').addEventListener('input', filterItems);
document.getElementById('ratingFilter').addEventListener('change', filterItems);
document.getElementById('startDate').addEventListener('change', filterItems);
document.getElementById('endDate').addEventListener('change', filterItems);

function filterItems() {
    let search = document.getElementById('searchInput').value.toLowerCase();
    let minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    let maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
    let rating = parseInt(document.getElementById('ratingFilter').value) || 0;
    let startDate = document.getElementById('startDate').value;
    let endDate = document.getElementById('endDate').value;

    document.querySelectorAll('.profile-item').forEach(item => {
        let name = item.dataset.name || "";
        let price = parseFloat(item.dataset.price) || 0;
        let itemRating = parseInt(item.dataset.rating) || 0;
        let date = item.dataset.date || "";

        let match = true;
        if (search && !name.includes(search)) match = false;
        if (price < minPrice || price > maxPrice) match = false;
        if (rating && itemRating < rating) match = false;
        if (startDate && date < startDate) match = false;
        if (endDate && date > endDate) match = false;

        item.classList.toggle('hidden', !match);
    });
}
</script>
</body>
</html>
