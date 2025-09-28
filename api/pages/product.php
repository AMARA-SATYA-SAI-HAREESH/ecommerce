<?php
session_start();
include '../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 🔹 Handle review delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
        $stmt->execute([$review_id, $user_id]);

        header("Location: product.php?id=$id&deleted=1");
        exit;
    }
}

// 🔹 Handle review edit
if (isset($_POST['edit_review_submit'])) {
    $reviewId = (int)$_POST['review_id'];
    $rating = (int)$_POST['edit_rating'];
    $reviewText = trim($_POST['edit_review']);
    $userId = $_SESSION['user_id'];

    $imagePath = null;
    if (!empty($_FILES['edit_review_image']['name'])) {
        $targetDir = "uploads/reviews/";
        if (!is_dir("../" . $targetDir)) mkdir("../" . $targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['edit_review_image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['edit_review_image']['tmp_name'], "../" . $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE reviews SET rating=?, review=?, image_path=? WHERE id=? AND user_id=?");
        $stmt->execute([$rating, $reviewText, $imagePath, $reviewId, $userId]);
    } else {
        $stmt = $conn->prepare("UPDATE reviews SET rating=?, review=? WHERE id=? AND user_id=?");
        $stmt->execute([$rating, $reviewText, $reviewId, $userId]);
    }

    header("Location: product.php?id=" . $id . "&edited=1");
    exit;
}

// 🔹 Handle review submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating_submit'])) {
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);
    $user_id = $_SESSION['user_id'] ?? null;
    $image_path = null;

    if (!empty($_FILES['review_image']['name'])) {
        $targetDir = "../uploads/reviews/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['review_image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['review_image']['tmp_name'], $targetFile)) {
            $image_path = "uploads/reviews/" . $fileName;
        }
    }

    if ($user_id) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, review, image_path, created_at) 
                                VALUES (?,?,?,?,?,NOW())");
        $stmt->execute([$user_id, $id, $rating, $review, $image_path]);

        header("Location: product.php?id=$id&success=1");
        exit;
    }
}

// 🔹 Fetch product with avg rating
$stmt = $conn->prepare("
    SELECT p.*, IFNULL(ROUND(AVG(r.rating),1), 0) AS avg_rating
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product){
    http_response_code(404);
    echo "Product not found";
    exit;
}

// 🔹 NEW: Check if product_images table exists and fetch images
$productImages = [];
$tableExists = false;

try {
    // Check if product_images table exists
    $checkTable = $conn->query("SELECT 1 FROM product_images LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    // Table doesn't exist, we'll use fallback approach
    $tableExists = false;
}

if ($tableExists) {
    // Fetch product images from product_images table
    $imageStmt = $conn->prepare("
        SELECT image_path FROM product_images 
        WHERE product_id = ? 
        ORDER BY is_primary DESC, id ASC
    ");
    $imageStmt->execute([$id]);
    $productImages = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
}

// If no images found in product_images or table doesn't exist, use fallback approach
if (empty($productImages) && !empty($product['image'])) {
    $productImages = [$product['image']];
    
    // Create additional demo images based on the main image for gallery effect
    $baseName = pathinfo($product['image'], PATHINFO_FILENAME);
    $extension = pathinfo($product['image'], PATHINFO_EXTENSION);
    
    // Add some demo variations (these won't actually exist but will show the gallery functionality)
    $productImages[] = $baseName . '_alt1.' . $extension;
    $productImages[] = $baseName . '_alt2.' . $extension;
    $productImages[] = $baseName . '_detail.' . $extension;
}

// 🔹 Fetch reviews
$reviewStmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/product.css?v=6"> <!-- cache bust -->
    <style>
        /* ⭐ Scoped styling for edit review stars */
        .edit-review-form .star-rating {
            display: inline-flex;
            gap: 6px;
            font-size: 22px;
            cursor: pointer;
        }
        .edit-review-form .star-rating .star {
            color: #ccc;
            transition: color 0.2s ease, transform 0.15s ease;
        }
        .edit-review-form .star-rating .star.selected {
            color: #f5b301;
        }
        .edit-review-form .star-rating .star.hover {
            color: #f5b301;
            transform: scale(1.1);
        }
        
        /* NEW: Image Gallery Styles */
        .product-image-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .main-product-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            cursor: zoom-in;
            transition: opacity 0.3s ease;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            overflow-x: auto;
            padding: 5px 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 4px;
            transition: border-color 0.2s ease;
        }
        
        .thumbnail.active {
            border-color: #4a6ee0;
        }
        
        .thumbnail:hover {
            border-color: #7a95e6;
        }
        
        .thumbnail-placeholder {
            width: 80px;
            height: 80px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid transparent;
            border-radius: 4px;
            color: #999;
            font-size: 12px;
            text-align: center;
            padding: 5px;
        }
        
        /* Fullscreen Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .image-modal.active {
            display: flex;
            opacity: 1;
        }
        
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-image.loaded {
            opacity: 1;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1001;
        }
        
        .modal-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            padding: 0 20px;
            z-index: 1001;
        }
        
        .nav-btn {
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .modal-thumbnails {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            overflow-x: auto;
        }
        
        .modal-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 4px;
            opacity: 0.6;
            transition: opacity 0.2s ease;
        }
        
        .modal-thumbnail.active {
            border-color: #4a6ee0;
            opacity: 1;
        }
        
        .modal-thumbnail:hover {
            opacity: 1;
        }
        
        .modal-counter {
            position: absolute;
            bottom: 90px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            font-size: 16px;
        }
        
        .demo-notice {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="page-top">
    <a href="../index.php" class="back-link">← Home</a>
</div>

<div class="product-container">
    <!-- NEW: Image Gallery Implementation -->
    <div class="product-image-container">
        <?php if (!empty($productImages)): ?>
            <img src="../../images/<?= htmlspecialchars($productImages[0]) ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="main-product-image" id="mainProductImage"
                 data-image-count="<?= count($productImages) ?>"
                 onerror="this.onerror=null; this.src='../../images/placeholder.jpg';">
                 

        <?php else: ?>
            <img src="../../images/placeholder.jpg" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="main-product-image">
        <?php endif; ?>
    </div>

    <div class="product-info">
        <h2><?= htmlspecialchars($product['name']) ?></h2>

        <!-- ⭐ Average rating -->
        <div class="average-rating">
            <?php
            $rating = $product['avg_rating'];
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= round($rating)
                    ? '<span class="star filled">★</span>'
                    : '<span class="star">★</span>';
            }
            ?>
            <span class="rating-text">(<?= $rating ?>/5)</span>
        </div>

        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
        <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>

        <!-- Add to cart & wishlist -->
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

<!-- NEW: Fullscreen Image Modal -->
<div class="image-modal" id="imageModal">
    <div class="modal-close" id="modalClose">×</div>
    <div class="modal-content">
        <img class="modal-image" id="modalImage" src="../" alt="">
        <div class="modal-counter" id="modalCounter"></div>
    </div>
    <div class="modal-nav">
        <button class="nav-btn" id="prevBtn">❮</button>
        <button class="nav-btn" id="nextBtn">❯</button>
    </div>
    <?php if (count($productImages) > 1): ?>
        <div class="modal-thumbnails">
            <?php foreach ($productImages as $index => $image): ?>
                <?php if ($tableExists || $index === 0): ?>
                    <img src="../../images/<?= htmlspecialchars($image) ?>" 
                         alt="Thumbnail <?= $index + 1 ?>"
                         class="modal-thumbnail"
                         data-index="<?= $index ?>"
                         onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="thumbnail-placeholder" data-index="<?= $index ?>">
                        <?= $index + 1 ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Review Section -->
<div class="product-container review-block">
    <div class="full-width">
        <h3>Rate & Review</h3>

        <?php if (isset($_GET['success'])): ?>
            <div id="review-success" class="success-message">✅ Your rating & review were submitted successfully!</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="review-form">
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
            <input type="file" name="review_image" accept="image/*">

            <button type="submit" name="rating_submit" class="btn submit-btn">Submit</button>
        </form>
    </div>
</div>
            
<!-- Show reviews -->
<div class="product-container review-block">
    <h3>Customer Reviews</h3>

    <?php if (isset($_GET['deleted'])): ?>
        <div id="delete-success" class="success-message">🗑️ Review deleted successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET['edited'])): ?>
        <div id="edit-success" class="success-message">✏️ Review updated successfully!</div>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
        <p>No reviews yet. Be the first!</p>
    <?php else: ?>
        <div class="review-list">
            <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                    <div class="review-header" style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                        <div class="review-avatar"><?= strtoupper(substr($rev['username'],0,1)) ?></div>
                        <strong><?= htmlspecialchars($rev['username']) ?></strong>
                    </div>

                    <div class="review-stars">
                        <?php for ($i=1; $i<=5; $i++): ?>
                            <span class="star <?= $i <= $rev['rating'] ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>

                    <p><?= htmlspecialchars($rev['review']) ?></p>

                    <?php if (!empty($rev['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($rev['image_path']) ?>" class="review-photo" alt="">
                    <?php endif; ?>

                    <small><?= $rev['created_at'] ?></small>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $rev['user_id']): ?>
                        <div class="review-actions" style="margin-top:6px;">
                            <button type="button" class="btn edit-btn" data-id="<?= $rev['id'] ?>">✏️ Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                <button type="submit" name="delete_review" class="btn delete-btn">🗑 Delete</button>
                            </form>
                        </div>

                        <!-- Hidden Edit Form -->
                        <form method="POST" enctype="multipart/form-data" class="edit-review-form" id="edit-form-<?= $rev['id'] ?>" style="display:none; margin-top:8px;">
                            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                            <label>Update Rating:</label>
                            <div class="star-rating" data-selected="<?= $rev['rating'] ?>">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span class="star <?= $i <= $rev['rating'] ? 'selected' : '' ?>" data-value="<?= $i ?>">★</span>
                                <?php endfor; ?>
                                </div>
                            <input type="hidden" name="edit_rating" id="edit-rating-<?= $rev['id'] ?>" value="<?= $rev['rating'] ?>">

                            <textarea name="edit_review" required><?= htmlspecialchars($rev['review']) ?></textarea>
                            <input type="file" name="edit_review_image" accept="image/*">
                            <button type="submit" name="edit_review_submit" class="btn submit-btn">Save</button>
                            <button type="button" class="btn cancel-btn" onclick="closeEditForm(<?= $rev['id'] ?>)">Cancel</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Function to create placeholder thumbnail when image fails to load
    function createPlaceholderThumbnail(index) {
        const placeholder = document.createElement('div');
        placeholder.className = 'thumbnail-placeholder';
        placeholder.setAttribute('data-index', index);
        placeholder.innerHTML = `Image ${index + 1}<br>(Demo)`;
        return placeholder;
    }

    // ⭐ Add Review Stars
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
        star.addEventListener('mouseout', () => highlight(current));
        star.addEventListener('click', () => {
            current = star.dataset.value;
            ratingValue.value = current;
            highlight(current);
        });
    });

    // ⭐ Edit Form Stars
    document.querySelectorAll('.edit-review-form .star-rating').forEach(function(ratingDiv) {
        const stars = ratingDiv.querySelectorAll('.star');
        const hiddenInput = ratingDiv.nextElementSibling;

        function setSelected(val) {
            stars.forEach(s => {
                const v = parseInt(s.dataset.value, 10);
                s.classList.toggle('selected', v <= val);
            });
        }
        function setHover(val) {
            stars.forEach(s => {
                const v = parseInt(s.dataset.value, 10);
                s.classList.toggle('hover', v <= val);
            });
        }
        function clearHover() {
            stars.forEach(s => s.classList.remove('hover'));
        }

        const initial = parseInt(hiddenInput.value || ratingDiv.getAttribute('data-selected') || '0', 10);
        setSelected(initial);

        stars.forEach(star => {
            star.addEventListener('mouseover', () => setHover(parseInt(star.dataset.value, 10)));
            star.addEventListener('mouseout', clearHover);
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value, 10);
                hiddenInput.value = val;
                setSelected(val);
            });
        });
    });

    // Edit form toggle
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const form = document.getElementById('edit-form-' + id);
            if (form) form.style.display = 'block';
        });
    });
    function closeEditForm(id) {
        const form = document.getElementById('edit-form-' + id);
        if(form) form.style.display = 'none';
    }

    // Hide success messages after 3 sec
    ['delete-success','edit-success','review-success'].forEach(id=>{
        const msg = document.getElementById(id);
        if(msg){ setTimeout(()=>{ msg.style.display='none'; }, 3000); }
    });

    // NEW: Image Gallery Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mainImage = document.getElementById('mainProductImage');
        const thumbnails = document.querySelectorAll('.thumbnail, .thumbnail-placeholder');
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalClose = document.getElementById('modalClose');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const modalThumbnails = document.querySelectorAll('.modal-thumbnail, .thumbnail-placeholder');
        const modalCounter = document.getElementById('modalCounter');
        
        let currentIndex = 0;
        let autoSlideInterval;
        let totalImages = mainImage ? parseInt(mainImage.getAttribute('data-image-count')) : 0;
        
        // Function to check if image exists
        function checkImageExists(url, callback) {
            const img = new Image();
            img.onload = function() { callback(true); };
            img.onerror = function() { callback(false); };
            img.src = url;
        }
        
        // Function to show image in modal
        function showImage(index) {
            if (totalImages === 0) return;
            
            currentIndex = index;
            const imagePath = thumbnails[index].getAttribute('data-src') || '../images/<?= htmlspecialchars($productImages[0]) ?>';
            
            // Check if image exists before trying to display it
            checkImageExists(imagePath, function(exists) {
                if (exists) {
                    // Preload image for smooth transition
                    const img = new Image();
                    img.onload = function() {
                        modalImage.src = imagePath;
                        modalImage.classList.add('loaded');
                        updateCounter();
                        updateActiveThumbnails();
                        updateNavButtons();
                    };
                    img.src = imagePath;
                } else {
                    // Show a placeholder or message
                    modalImage.src = '../images/placeholder.jpg';
                    modalImage.alt = 'Image not available';
                    modalImage.classList.add('loaded');
                    updateCounter();
                    updateActiveThumbnails();
                    updateNavButtons();
                }
            });
        }
        
        // Update counter text
        function updateCounter() {
            if (modalCounter && totalImages > 1) {
                modalCounter.textContent = `${currentIndex + 1} / ${totalImages}`;
            }
        }
        
        // Update active thumbnail indicators
        function updateActiveThumbnails() {
            thumbnails.forEach((thumb, idx) => {
                if (thumb.classList) {
                    thumb.classList.toggle('active', idx === currentIndex);
                }
            });
            
            modalThumbnails.forEach((thumb, idx) => {
                if (thumb.classList) {
                    thumb.classList.toggle('active', idx === currentIndex);
                }
            });
        }
        
        // Update navigation buttons state
        function updateNavButtons() {
            if (prevBtn) {
                prevBtn.disabled = currentIndex === 0;
            }
            if (nextBtn) {
                nextBtn.disabled = currentIndex === totalImages - 1;
            }
        }
        
        // Navigate to next image
        function nextImage() {
            clearInterval(autoSlideInterval);
            if (currentIndex < totalImages - 1) {
                currentIndex++;
                showImage(currentIndex);
                startAutoSlide();
            }
        }
        
        // Navigate to previous image
        function prevImage() {
            clearInterval(autoSlideInterval);
            if (currentIndex > 0) {
                currentIndex--;
                showImage(currentIndex);
                startAutoSlide();
            }
        }
        
        // Start auto slide
        function startAutoSlide() {
            clearInterval(autoSlideInterval);
            if (totalImages > 1) {
                autoSlideInterval = setInterval(nextImage, 3000);
            }
        }
        
        // Pause auto slide on modal hover
        function setupAutoSlidePause() {
            if (modal) {
                modal.addEventListener('mouseenter', () => {
                    clearInterval(autoSlideInterval);
                });
                
                modal.addEventListener('mouseleave', () => {
                    startAutoSlide();
                });
            }
        }
        
        // Set up thumbnail click events
        if (thumbnails.length > 0) {
            thumbnails.forEach((thumb, index) => {
                thumb.addEventListener('click', function() {
                    currentIndex = parseInt(this.getAttribute('data-index'));
                    showImage(currentIndex);
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                    startAutoSlide();
                });
            });
        }
        
        // Set up main image click to open modal
        if (mainImage && totalImages > 0) {
            mainImage.addEventListener('click', function() {
                showImage(0);
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
                startAutoSlide();
            });
        }
        
        // Modal navigation events
        if (modalClose) {
            modalClose.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Re-enable scrolling
                clearInterval(autoSlideInterval);
            });
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', prevImage);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', nextImage);
        }
        
        // Modal thumbnail navigation
        if (modalThumbnails.length > 0) {
            modalThumbnails.forEach((thumb, index) => {
                if (thumb.addEventListener) {
                    thumb.addEventListener('click', function() {
                        currentIndex = parseInt(this.getAttribute('data-index'));
                        showImage(currentIndex);
                        startAutoSlide();
                    });
                }
            });
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (modal.classList.contains('active')) {
                if (e.key === 'Escape') {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                    clearInterval(autoSlideInterval);
                } else if (e.key === 'ArrowLeft') {
                    prevImage();
                } else if (e.key === 'ArrowRight') {
                    nextImage();
                }
            }
        });
        
        // Initialize auto slide pause on hover
        setupAutoSlidePause();
    });
</script>
</body>
</html>
