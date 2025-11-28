<?php
require_once '../config/db.php';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bank'])) {
        $bank_name = $_POST['bank_name'];
        $account_number = $_POST['account_number'];
        $account_holder = $_POST['account_holder'];
        
        $stmt = $pdo->prepare("INSERT INTO payment_methods (bank_name, account_number, account_holder) VALUES (?, ?, ?)");
        $stmt->execute([$bank_name, $account_number, $account_holder]);
        header("Location: payment.php?msg=created");
        exit();
    } elseif (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: payment.php?msg=deleted");
        exit();
    }
    header("Location: payment.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM payment_methods");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-white mb-2">Payment Methods</h1>
    <p class="text-slate-400">Manage manual bank transfer accounts.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Add New Form -->
    <div class="lg:col-span-1">
        <div class="glass-panel p-6 rounded-xl">
            <h3 class="text-lg font-bold text-white mb-4">Add New Bank</h3>
            <form method="POST">
                <input type="hidden" name="add_bank" value="1">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" required placeholder="e.g. BCA"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Account Number</label>
                    <input type="text" name="account_number" required placeholder="1234567890"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Account Holder</label>
                    <input type="text" name="account_holder" required placeholder="John Doe"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Add Account
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-2">
        <div class="glass-panel rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                        <th class="p-4 font-medium">Bank</th>
                        <th class="p-4 font-medium">Details</th>
                        <th class="p-4 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-800">
                    <?php foreach ($methods as $m): ?>
                    <tr class="hover:bg-slate-800/50 transition-colors">
                        <td class="p-4 text-white font-bold"><?php echo htmlspecialchars($m['bank_name']); ?></td>
                        <td class="p-4">
                            <div class="text-slate-300 font-mono"><?php echo htmlspecialchars($m['account_number']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($m['account_holder']); ?></div>
                        </td>
                        <td class="p-4 text-right">
                            <form method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="delete_id" value="<?php echo $m['id']; ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($methods)): ?>
                    <tr>
                        <td colspan="3" class="p-8 text-center text-slate-500">No payment methods added.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
