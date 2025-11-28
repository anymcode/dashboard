<?php
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch Transaction Details
$stmt = $pdo->prepare("
    SELECT t.*, u.name as user_name, u.email as user_email 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: index.php?msg=not_found");
    exit();
}

// Fetch Items
$stmt = $pdo->prepare("
    SELECT ti.*, p.name as product_name, p.image as product_image, p.slug 
    FROM transaction_items ti 
    LEFT JOIN products p ON ti.product_id = p.id 
    WHERE ti.transaction_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="index.php" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-white">Order Details</h1>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                    #<?php echo htmlspecialchars($transaction['transaction_code']); ?>
                </span>
            </div>
            <p class="text-slate-400 text-sm ml-7">Created on <?php echo date('d M Y, H:i', strtotime($transaction['created_at'])); ?></p>
        </div>
        
        <div class="flex gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium transition-colors border border-slate-700 flex items-center gap-2">
                <i class="fas fa-print"></i> Print
            </button>
            <?php if ($transaction['status'] !== 'cancelled'): ?>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                        <i class="fas fa-edit"></i> Update Status
                    </button>
                    <!-- Status Dropdown -->
                    <div x-show="open" class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden" style="display: none;">
                        <?php 
                        $statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
                        foreach ($statuses as $status): 
                            if ($status === $transaction['status']) continue;
                        ?>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $status; ?>">
                                <button type="submit" name="update_status" class="w-full text-left px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition-colors capitalize">
                                    Mark as <?php echo ucfirst($status); ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info (Left) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items List -->
            <div class="glass-panel rounded-xl overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-white">Order Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-900/50 text-xs uppercase text-slate-400 font-medium">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-6 py-4 text-center">Qty</th>
                                <th class="px-6 py-4 text-right">Price</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-700">
                                            <?php if (!empty($item['product_image'])): ?>
                                                <img src="../uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-slate-600">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></p>
                                            <p class="text-xs text-slate-500">Slug: <?php echo htmlspecialchars($item['slug'] ?? '-'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-300">x<?php echo $item['quantity']; ?></td>
                                <td class="px-6 py-4 text-right text-slate-300">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-right text-white font-medium">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-900/50 border-t border-slate-800">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-slate-400 font-medium">Subtotal</td>
                                <td class="px-6 py-4 text-right text-white font-bold">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-2 text-right text-slate-400 font-medium border-none">Shipping</td>
                                <td class="px-6 py-2 text-right text-green-400 font-medium border-none">Free</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-white font-bold text-lg">Total Amount</td>
                                <td class="px-6 py-4 text-right text-indigo-400 font-bold text-lg">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Info (Right) -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="glass-panel p-6 rounded-xl">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Customer Details</h3>
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl font-bold">
                        <?php echo strtoupper(substr($transaction['user_name'] ?? 'G', 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($transaction['user_name'] ?? 'Guest'); ?></p>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($transaction['user_email'] ?? '-'); ?></p>
                    </div>
                </div>
                
                <div class="space-y-3 pt-4 border-t border-slate-800">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Shipping Address</p>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($transaction['shipping_address'] ?? 'No address provided')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="glass-panel p-6 rounded-xl">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Payment Information</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Status</span>
                        <?php 
                            $statusClass = match($transaction['status']) {
                                'paid', 'completed' => 'bg-green-500/10 text-green-500 border-green-500/20',
                                'pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                'cancelled' => 'bg-red-500/10 text-red-500 border-red-500/20',
                                default => 'bg-slate-500/10 text-slate-500 border-slate-500/20'
                            };
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-medium border <?php echo $statusClass; ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Method</span>
                        <span class="text-sm text-white font-medium">
                            <i class="fas fa-credit-card mr-2 text-slate-400"></i>
                            <?php echo htmlspecialchars($transaction['payment_method'] ?? 'Manual Transfer'); ?>
                        </span>
                    </div>
                    <?php if (!empty($transaction['payment_proof'])): ?>
                    <div class="pt-4 border-t border-slate-800">
                        <p class="text-xs text-slate-500 mb-2">Payment Proof</p>
                        <a href="../uploads/proofs/<?php echo htmlspecialchars($transaction['payment_proof']); ?>" target="_blank" class="block w-full h-32 bg-slate-900 rounded-lg border border-slate-700 overflow-hidden relative group">
                            <img src="../uploads/proofs/<?php echo htmlspecialchars($transaction['payment_proof']); ?>" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 transition-opacity">
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="bg-black/50 text-white px-3 py-1 rounded text-xs">View Full</span>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
