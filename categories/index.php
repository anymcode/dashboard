<?php
require_once '../config/db.php';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Categories</h1>
        <p class="text-slate-400">Manage your product categories.</p>
    </div>
    <a href="create.php" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
        <i class="fas fa-plus"></i> Add Category
    </a>
</div>

<div class="glass-panel rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">ID</th>
                    <th class="p-4 font-medium">Name</th>
                    <th class="p-4 font-medium">Slug</th>
                    <th class="p-4 font-medium">Description</th>
                    <th class="p-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($categories as $category): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4 text-slate-500">#<?php echo $category['id']; ?></td>
                    <td class="p-4 text-white font-medium"><?php echo htmlspecialchars($category['name']); ?></td>
                    <td class="p-4 text-slate-400 font-mono text-xs"><?php echo htmlspecialchars($category['slug']); ?></td>
                    <td class="p-4 text-slate-400 truncate max-w-xs"><?php echo htmlspecialchars($category['description']); ?></td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="edit.php?id=<?php echo $category['id']; ?>" class="p-2 text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                <input type="hidden" name="delete_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500">No categories found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
