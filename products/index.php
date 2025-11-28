<?php
require_once '../config/db.php';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Fetch Categories for Filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Search & Filter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base Query Conditions
$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE ? OR p.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total Rows (for Pagination)
$count_query = "SELECT COUNT(*) FROM products p WHERE $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Products with Limit
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $where_sql 
          ORDER BY p.created_at DESC 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Products</h1>
        <p class="text-slate-400">Manage your inventory.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="Search products..." 
                    class="w-full sm:w-64 bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 placeholder-slate-600">
            </div>
            
            <select name="category" onchange="this.form.submit()" 
                class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 cursor-pointer">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="create.php" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
            <i class="fas fa-plus"></i> Add Product
        </a>
    </div>
</div>

<div class="glass-panel rounded-xl overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">Product</th>
                    <th class="p-4 font-medium">Category</th>
                    <th class="p-4 font-medium">Price</th>
                    <th class="p-4 font-medium">Stock</th>
                    <th class="p-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($products as $product): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded bg-slate-800 flex items-center justify-center text-slate-500 overflow-hidden">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Product" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-box"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="text-white font-medium"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($product['slug']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-slate-400">
                        <span class="bg-slate-800 px-2 py-1 rounded text-xs">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </span>
                    </td>
                    <td class="p-4 text-white font-bold">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                    <td class="p-4">
                        <span class="<?php echo $product['stock'] > 0 ? 'text-green-400' : 'text-red-400'; ?>">
                            <?php echo $product['stock']; ?> units
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="p-2 text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500">No products found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="p-4 border-t border-slate-800 flex justify-between items-center">
        <div class="text-xs text-slate-500">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> results
        </div>
        <div class="flex gap-1">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Prev</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 rounded text-xs <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
