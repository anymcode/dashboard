<?php
require_once '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit();
}

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($name) || empty($price)) {
        $error = 'Name and Price are required';
    } else {
        // Handle Image Upload
        $image = $product['image']; // Default to existing image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = '../uploads/products/' . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Optional: Delete old image if exists
                    if ($product['image'] && file_exists('../uploads/products/' . $product['image'])) {
                        unlink('../uploads/products/' . $product['image']);
                    }
                    $image = $new_filename;
                }
            }
        }

        // Ensure unique slug by appending timestamp (or keep existing if name hasn't changed, but simpler to just update)
        // To avoid changing slug unnecessarily if name is same, we could check. But user asked to fix duplicate error.
        // If name matches existing, we might keep slug. But let's just append timestamp to be safe on updates too if name changes.
        // Actually, for edit, if name is same, slug should probably stay.
        if ($name !== $product['name']) {
             $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . time();
        } else {
             $slug = $product['slug'];
        }

        try {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $slug, $description, $price, $stock, $image, $id]);
            header("Location: index.php?msg=updated");
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating product: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="index.php" class="text-slate-400 hover:text-white mb-4 inline-block transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Products
        </a>
        <h1 class="text-2xl font-bold text-white">Edit Product</h1>
    </div>

    <div class="glass-panel p-8 rounded-xl">
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Category</label>
                    <select name="category_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Price (Rp)</label>
                    <input type="number" name="price" value="<?php echo $product['price']; ?>" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Stock</label>
                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Product Image</label>
                <?php if ($product['image']): ?>
                    <div class="mb-2">
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" class="h-20 w-20 object-cover rounded-lg border border-slate-700">
                    </div>
                <?php endif; ?>
                <div class="border-2 border-dashed border-slate-700 rounded-lg p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer relative">
                    <input type="file" name="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*">
                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2"></i>
                    <p class="text-xs text-slate-400">Drag & drop or click to replace</p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Description</label>
                <textarea name="description" rows="4"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="flex justify-end gap-4">
                <a href="index.php" class="px-6 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
