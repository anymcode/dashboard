<?php
require_once '../config/db.php';

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = $_POST['transaction_id'] ?? $_POST['id']; // Handle input from both index and detail pages
    $new_status = $_POST['status'];
    
    // 1. Get Old Status
    $stmt = $pdo->prepare("SELECT status FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    $old_status = $stmt->fetchColumn();

    if ($old_status !== $new_status) {
        // 2. Define Deducted States (Stock is gone from inventory)
        $deducted_statuses = ['shipped', 'completed'];
        
        $was_deducted = in_array($old_status, $deducted_statuses);
        $will_be_deducted = in_array($new_status, $deducted_statuses);

        // 3. Fetch Transaction Items
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM transaction_items WHERE transaction_id = ?");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Stock Logic
        if (!$was_deducted && $will_be_deducted) {
            // Case: Pending/Paid -> Shipped/Completed (DECREASE STOCK)
            foreach ($items as $item) {
                $update = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update->execute([$item['quantity'], $item['product_id']]);
            }
        } elseif ($was_deducted && !$will_be_deducted) {
            // Case: Shipped/Completed -> Cancelled/Pending (RESTOCK/INCREASE)
            foreach ($items as $item) {
                $update = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $update->execute([$item['quantity'], $item['product_id']]);
            }
        }

        // 5. Update Transaction Status
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
    }

    // Redirect back to the referring page (index or detail)
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $referer . (strpos($referer, '?') ? '&' : '?') . "msg=updated");
    exit();
}

// Handle Search & Filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base Query Conditions
$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(t.transaction_code LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total Rows
$count_query = "SELECT COUNT(*) 
                FROM transactions t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Transactions with Limit
$query = "SELECT t.*, u.name as user_name, u.email as user_email 
          FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE $where_sql 
          ORDER BY t.created_at DESC 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Transactions</h1>
        <p class="text-slate-400">Monitor orders and payments.</p>
    </div>
    <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="Search ID or Customer..." 
                class="w-full sm:w-64 bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 placeholder-slate-600">
        </div>
        
        <select name="status" onchange="this.form.submit()" 
            class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 cursor-pointer">
            <option value="">All Status</option>
            <?php 
            $statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
            foreach ($statuses as $s): 
            ?>
                <option value="<?php echo $s; ?>" <?php echo $status_filter == $s ? 'selected' : ''; ?>>
                    <?php echo ucfirst($s); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <a href="export.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </form>
</div>

<div class="glass-panel rounded-xl overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">Order ID</th>
                    <th class="p-4 font-medium">Customer</th>
                    <th class="p-4 font-medium">Amount</th>
                    <th class="p-4 font-medium">Payment</th>
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium">Date</th>
                    <th class="p-4 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($transactions as $t): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4 text-white font-mono">#<?php echo htmlspecialchars($t['transaction_code']); ?></td>
                    <td class="p-4">
                        <div class="text-white font-medium"><?php echo htmlspecialchars($t['user_name'] ?? 'Guest'); ?></div>
                        <div class="text-xs text-slate-500"><?php echo htmlspecialchars($t['user_email'] ?? '-'); ?></div>
                    </td>
                    <td class="p-4 text-white font-bold">Rp <?php echo number_format($t['total_amount'], 0, ',', '.'); ?></td>
                    <td class="p-4">
                        <div class="text-slate-400 mb-1"><?php echo htmlspecialchars($t['payment_method']); ?></div>
                        <?php if (!empty($t['payment_proof'])): ?>
                            <a href="../uploads/proofs/<?php echo htmlspecialchars($t['payment_proof']); ?>" target="_blank" class="text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-1 rounded hover:bg-indigo-500/20 transition-colors inline-flex items-center gap-1">
                                <i class="fas fa-receipt"></i> View Proof
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" onchange="this.form.submit()" 
                                class="bg-slate-900 border border-slate-700 text-xs rounded px-2 py-1 text-slate-300 focus:outline-none focus:border-indigo-500">
                                <option value="pending" <?php echo $t['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $t['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="shipped" <?php echo $t['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="completed" <?php echo $t['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $t['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td class="p-4 text-slate-500 text-xs"><?php echo date('d M Y H:i', strtotime($t['created_at'])); ?></td>
                    <td class="p-4 text-right">
                        <a href="detail.php?id=<?php echo $t['id']; ?>" class="text-slate-400 hover:text-white transition-colors inline-block" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" class="p-8 text-center text-slate-500">No transactions found.</td>
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
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Prev</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 rounded text-xs <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-xs">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
