<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found.";
    exit();
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $product['image']; // keep old image by default

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../../images/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $fileName;
        }
    }

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $price, $description, $image, $id]);

    header("Location: manage_products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fa; }
        .container { width: 50%; margin: 50px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin: 10px 0 5px; }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: #fff; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #218838; }
        .btn-back { display: inline-block; margin-top: 10px; background: #007bff; padding: 8px 12px; color: #fff; border-radius: 4px; text-decoration: none; }
        .btn-back:hover { background: #0056b3; }
        img { margin: 10px 0; max-width: 150px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Product</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Product Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required>

            <label>Price:</label>
            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']); ?>" required>

            <label>Description:</label>
            <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea>

            <label>Current Image:</label><br>
            <?php if (!empty($product['image'])): ?>
                <img src="../../images/<?= htmlspecialchars($product['image']); ?>" alt="Product Image">
            <?php else: ?>
                <p>No image available</p>
            <?php endif; ?>

            <label>Upload New Image (optional):</label>
            <input type="file" name="image" accept="image/*">

            <button type="submit">Save Changes</button>
        </form>
        <a href="manage_products.php" class="btn-back">Back</a>
    </div>
</body>
</html>
