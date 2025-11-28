<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Simple slug generation
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($name)) {
        $error = 'Name is required';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            header("Location: index.php?msg=created");
            exit();
        } catch (PDOException $e) {
            $error = 'Error adding category: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="index.php" class="text-slate-400 hover:text-white mb-4 inline-block transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Categories
        </a>
        <h1 class="text-2xl font-bold text-white">Add New Category</h1>
    </div>

    <div class="glass-panel p-8 rounded-xl">
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Category Name</label>
                <input type="text" name="name" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                    placeholder="e.g. Electronics">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Description</label>
                <textarea name="description" rows="4"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                    placeholder="Category description..."></textarea>
            </div>

            <div class="flex justify-end gap-4">
                <a href="index.php" class="px-6 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
