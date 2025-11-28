<?php
require_once '../config/db.php';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count Total Rows
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Users with Limit
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">User Management</h1>
        <p class="text-slate-400">Manage system users and customers.</p>
    </div>
    <a href="create.php" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
        <i class="fas fa-plus"></i> Add User
    </a>
</div>

<div class="glass-panel rounded-xl overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">ID</th>
                    <th class="p-4 font-medium">Name</th>
                    <th class="p-4 font-medium">Email</th>
                    <th class="p-4 font-medium">Role</th>
                    <th class="p-4 font-medium">Joined</th>
                    <th class="p-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4 text-slate-500">#<?php echo $user['id']; ?></td>
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-500 flex items-center justify-center font-bold">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                    </td>
                    <td class="p-4 text-slate-400"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="p-4">
                        <span class="<?php echo $user['role'] === 'admin' ? 'bg-purple-500/10 text-purple-500 border-purple-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'; ?> px-2 py-1 rounded text-xs border uppercase">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    </td>
                    <td class="p-4 text-slate-500 text-xs"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="p-2 text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != 1): // Prevent deleting main admin ?>
                            <form method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
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
                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Prev</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-3 py-1 rounded text-xs <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
