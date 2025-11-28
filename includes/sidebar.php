<?php
// Fetch Current User (Simulated for now, usually from Session)
// We'll just fetch the first admin user found
$current_user = ['name' => 'Guest', 'email' => 'guest@example.com', 'initial' => 'G'];
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_user = [
            'name' => $user['name'],
            'email' => $user['email'],
            'initial' => strtoupper(substr($user['name'], 0, 1))
        ];
    }
} catch (PDOException $e) {
    // Ignore error if table doesn't exist yet
}

// Fetch Pending Transactions for Notifications
$pending_count = 0;
$latest_pending = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
    $pending_count = $stmt->fetchColumn();

    if ($pending_count > 0) {
        $stmt = $pdo->query("SELECT * FROM transactions WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
        $latest_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Ignore
}
?>
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/80 z-40 md:hidden" style="display: none;"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-300 md:translate-x-0 md:static md:inset-0">
            <div class="h-16 flex items-center px-6 border-b border-slate-800 justify-between">
                <div class="text-2xl font-bold text-indigo-500 tracking-wider">
                    <i class="fas fa-cube mr-2"></i>AnymCode
                </div>
                <!-- Close button for mobile -->
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <div class="px-4 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Overview</div>
                <a href="/admin/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <div class="px-4 mt-6 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Management</div>
                <a href="/admin/categories/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-tags w-6"></i>
                    <span class="font-medium">Categories</span>
                </a>
                <a href="/admin/products/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-box w-6"></i>
                    <span class="font-medium">Products</span>
                </a>
                <a href="/admin/transactions/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-shopping-cart w-6"></i>
                    <span class="font-medium">Transactions</span>
                </a>
                <a href="/admin/users/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-users w-6"></i>
                    <span class="font-medium">Users</span>
                </a>
                <a href="/admin/sliders/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-images w-6"></i>
                    <span class="font-medium">Hero Sliders</span>
                </a>

                <div class="px-4 mt-6 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Settings</div>
                <a href="/admin/settings/payment.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-wallet w-6"></i>
                    <span class="font-medium">Payment Methods</span>
                </a>
                <a href="/admin/settings/midtrans.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-credit-card w-6"></i>
                    <span class="font-medium">Midtrans API</span>
                </a>
                <a href="/admin/settings/web.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-cog w-6"></i>
                    <span class="font-medium">Web Settings</span>
                </a>
                <a href="/admin/vouchers/index.php" class="sidebar-link flex items-center px-6 py-3 text-slate-400 transition-colors duration-200 mb-1">
                    <i class="fas fa-ticket-alt w-6"></i>
                    <span class="font-medium">Vouchers</span>
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <a href="/admin/logout.php" class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                        <?php echo $current_user['initial']; ?>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($current_user['name']); ?></div>
                        <div class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($current_user['email']); ?></div>
                    </div>
                    <i class="fas fa-sign-out-alt" title="Logout"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-900">
            <!-- Top Header -->
            <header class="h-16 bg-slate-900/50 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-6 z-10">
                <button @click="sidebarOpen = true" class="md:hidden text-slate-400 hover:text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="flex items-center gap-4 ml-auto relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" class="relative text-slate-400 hover:text-white transition-colors">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($pending_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] flex items-center justify-center text-white font-bold animate-pulse">
                                <?php echo $pending_count > 9 ? '9+' : $pending_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <!-- Notification Dropdown -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-80 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden z-50"
                         style="display: none;">
                        <div class="p-4 border-b border-slate-700 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-white">Notifications</h3>
                            <?php if ($pending_count > 0): ?>
                                <span class="text-xs text-indigo-400"><?php echo $pending_count; ?> Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <?php if ($pending_count > 0): ?>
                                <?php foreach ($latest_pending as $t): ?>
                                    <a href="/admin/transactions/index.php" class="block p-4 hover:bg-slate-700/50 transition-colors border-b border-slate-700/50 last:border-0">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-yellow-500/20 text-yellow-500 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-shopping-cart text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-white font-medium">New Order #<?php echo htmlspecialchars($t['transaction_code']); ?></p>
                                                <p class="text-xs text-slate-400 mt-1">Rp <?php echo number_format($t['total_amount']); ?></p>
                                                <p class="text-[10px] text-slate-500 mt-1"><?php echo date('d M H:i', strtotime($t['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-500">
                                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                    <p class="text-sm">No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($pending_count > 0): ?>
                            <a href="/admin/transactions/index.php" class="block p-3 text-center text-xs text-indigo-400 hover:text-indigo-300 bg-slate-900/50">
                                View All Transactions
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-900 p-6">
                <!-- Alpine.js for interactions -->
                <script src="//unpkg.com/alpinejs" defer></script>
