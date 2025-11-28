<?php
require_once '../config/db.php';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_voucher'])) {
    $code = strtoupper($_POST['code']);
    $type = $_POST['type'];
    $value = $_POST['value'];
    $min_purchase = $_POST['min_purchase'] ?? 0;
    $usage_limit = $_POST['usage_limit'] ?? 0;
    $expires_at = $_POST['expires_at'];

    $stmt = $pdo->prepare("INSERT INTO vouchers (code, type, value, min_purchase, usage_limit, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$code, $type, $value, $min_purchase, $usage_limit, $expires_at]);
        header("Location: index.php?msg=created");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Vouchers
$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC");
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Vouchers & Promo Codes</h1>
        <p class="text-slate-400">Manage discounts for your customers.</p>
    </div>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
        <i class="fas fa-plus"></i> Create Voucher
    </button>
</div>

<!-- Vouchers List -->
<div class="glass-panel rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">Code</th>
                    <th class="p-4 font-medium">Discount</th>
                    <th class="p-4 font-medium">Min. Purchase</th>
                    <th class="p-4 font-medium">Usage</th>
                    <th class="p-4 font-medium">Expires</th>
                    <th class="p-4 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($vouchers as $v): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4">
                        <span class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-1 rounded font-mono font-bold">
                            <?php echo htmlspecialchars($v['code']); ?>
                        </span>
                    </td>
                    <td class="p-4 text-white">
                        <?php echo $v['type'] == 'percent' ? floatval($v['value']) . '%' : 'Rp ' . number_format($v['value'], 0, ',', '.'); ?>
                    </td>
                    <td class="p-4 text-slate-400">
                        Rp <?php echo number_format($v['min_purchase'], 0, ',', '.'); ?>
                    </td>
                    <td class="p-4 text-slate-400">
                        <?php echo $v['used_count']; ?> / <?php echo $v['usage_limit'] > 0 ? $v['usage_limit'] : '∞'; ?>
                    </td>
                    <td class="p-4">
                        <?php 
                        $is_expired = strtotime($v['expires_at']) < time();
                        echo $is_expired ? '<span class="text-red-400">Expired</span>' : '<span class="text-green-400">' . date('d M Y', strtotime($v['expires_at'])) . '</span>';
                        ?>
                    </td>
                    <td class="p-4 text-right">
                        <form method="POST" onsubmit="return confirm('Delete this voucher?');">
                            <input type="hidden" name="delete_id" value="<?php echo $v['id']; ?>">
                            <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vouchers)): ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-slate-500">No vouchers created yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
        <div class="glass-panel p-6 rounded-2xl shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-4">Create New Voucher</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_voucher" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Voucher Code</label>
                    <input type="text" name="code" required placeholder="e.g. SALE50" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500 uppercase">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Type</label>
                        <select name="type" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500">
                            <option value="fixed">Fixed Amount (Rp)</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Value</label>
                        <input type="number" name="value" required placeholder="e.g. 10000 or 10" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Min. Purchase (Rp)</label>
                    <input type="number" name="min_purchase" value="0" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Usage Limit</label>
                        <input type="number" name="usage_limit" value="0" placeholder="0 for unlimited" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Expires At</label>
                        <input type="date" name="expires_at" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
