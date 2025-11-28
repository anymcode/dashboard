<?php
require_once 'config/db.php';

// Fetch Stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $total_transactions = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Total Revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE status IN ('paid', 'completed', 'shipped')");
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // Recent Transactions
    $stmt = $pdo->query("SELECT t.*, u.name as user_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5");
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chart Data (Last 7 Days) - Optimized Query
    $chart_labels = [];
    $chart_revenue = [];
    $chart_sales = [];

    // Initialize last 7 days with 0
    $last_7_days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last_7_days[$date] = ['revenue' => 0, 'sales' => 0];
    }

    // Single Query for Revenue and Sales
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date, 
            SUM(total_amount) as revenue, 
            COUNT(*) as sales 
        FROM transactions 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
          AND status IN ('paid', 'completed', 'shipped')
        GROUP BY DATE(created_at)
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($last_7_days[$row['date']])) {
            $last_7_days[$row['date']]['revenue'] = $row['revenue'];
            $last_7_days[$row['date']]['sales'] = $row['sales'];
        }
    }

    // Flatten for Chart
    foreach ($last_7_days as $date => $data) {
        $chart_labels[] = date('D', strtotime($date));
        $chart_revenue[] = $data['revenue'];
        $chart_sales[] = $data['sales'];
    }

    // Visitors/Users Chart Data (Monthly for Current Year)
    $visitor_labels = [];
    $visitor_data = [];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Initialize all months with 0
    $monthly_data = array_fill(1, 12, 0);

    $stmt = $pdo->query("SELECT MONTH(created_at) as m, COUNT(*) as c FROM users WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY MONTH(created_at)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_data[$row['m']] = $row['c'];
    }

    // Prepare data for chart
    $visitor_labels = $months;
    $visitor_data = array_values($monthly_data);

} catch (PDOException $e) {
    $total_categories = 0;
    $total_products = 0;
    $total_transactions = 0;
    $total_users = 0;
    $recent_transactions = [];
    // Default empty chart data on error
    $chart_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $chart_revenue = [0,0,0,0,0,0,0];
    $chart_sales = [0,0,0,0,0,0,0];
    $visitor_labels = ['Jan', 'Feb', 'Mar'];
    $visitor_data = [0, 0, 0];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-white mb-2">Dashboard Overview</h1>
    <p class="text-slate-400">Welcome back, here's what's happening with your store today.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
    <!-- Stat Card 1 -->
    <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fas fa-tags text-6xl text-indigo-500"></i>
        </div>
        <div class="relative z-10">
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Total Categories</p>
            <h3 class="text-3xl font-bold text-white mt-1"><?php echo number_format($total_categories); ?></h3>
        </div>
        <div class="mt-4 flex items-center text-sm text-green-400">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>Active</span>
        </div>
    </div>

    <!-- Stat Card 2 -->
    <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fas fa-box text-6xl text-purple-500"></i>
        </div>
        <div class="relative z-10">
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Total Products</p>
            <h3 class="text-3xl font-bold text-white mt-1"><?php echo number_format($total_products); ?></h3>
        </div>
        <div class="mt-4 flex items-center text-sm text-indigo-400">
            <i class="fas fa-plus mr-1"></i>
            <span>In Stock</span>
        </div>
    </div>

    <!-- Stat Card 3 -->
    <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fas fa-shopping-cart text-6xl text-pink-500"></i>
        </div>
        <div class="relative z-10">
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Total Orders</p>
            <h3 class="text-3xl font-bold text-white mt-1"><?php echo number_format($total_transactions); ?></h3>
        </div>
        <div class="mt-4 flex items-center text-sm text-green-400">
            <i class="fas fa-chart-line mr-1"></i>
            <span>+12% this week</span>
        </div>
    </div>

    <!-- Stat Card 4 -->
    <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fas fa-users text-6xl text-indigo-500"></i>
        </div>
        <div class="relative z-10">
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Total Users</p>
            <h3 class="text-3xl font-bold text-white mt-1"><?php echo number_format($total_users); ?></h3>
        </div>
        <div class="mt-4 flex items-center text-sm text-indigo-400">
            <i class="fas fa-user-plus mr-1"></i>
            <span>New users</span>
        </div>
    </div>

    <!-- Stat Card 5 (Revenue) -->
    <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fas fa-wallet text-6xl text-emerald-500"></i>
        </div>
        <div class="relative z-10">
            <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Total Revenue</p>
            <h3 class="text-2xl font-bold text-white mt-1">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h3>
        </div>
        <div class="mt-4 flex items-center text-sm text-emerald-400">
            <i class="fas fa-coins mr-1"></i>
            <span>Gross Income</span>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="glass-panel rounded-xl overflow-hidden">
    <div class="p-6 border-b border-slate-800 flex justify-between items-center">
        <h2 class="text-lg font-bold text-white">Recent Transactions</h2>
        <a href="/transactions/index.php" class="text-sm text-indigo-400 hover:text-indigo-300">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">Transaction ID</th>
                    <th class="p-4 font-medium">User</th>
                    <th class="p-4 font-medium">Amount</th>
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php if (count($recent_transactions) > 0): ?>
                    <?php foreach ($recent_transactions as $t): ?>
                    <tr class="hover:bg-slate-800/50 transition-colors">
                        <td class="p-4 text-white font-medium">#<?php echo htmlspecialchars($t['transaction_code']); ?></td>
                        <td class="p-4 text-slate-300"><?php echo htmlspecialchars($t['user_name'] ?? 'Guest'); ?></td>
                        <td class="p-4 text-white font-bold">Rp <?php echo number_format($t['total_amount'], 0, ',', '.'); ?></td>
                        <td class="p-4">
                            <?php 
                                $statusClass = match($t['status']) {
                                    'paid', 'completed' => 'bg-green-500/10 text-green-500 border-green-500/20',
                                    'pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                    'cancelled' => 'bg-red-500/10 text-red-500 border-red-500/20',
                                    default => 'bg-slate-500/10 text-slate-500 border-slate-500/20'
                                };
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium border <?php echo $statusClass; ?>">
                                <?php echo ucfirst($t['status']); ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-400"><?php echo date('d M Y', strtotime($t['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    <!-- Sales Chart -->
    <div class="glass-panel p-6 rounded-xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">Sales Statistics</h3>
            <select class="bg-slate-900 border border-slate-700 text-xs rounded px-2 py-1 text-slate-300 focus:outline-none">
                <option>This Week</option>
                <option>Last Week</option>
                <option>This Month</option>
            </select>
        </div>
        <div id="salesChart" class="w-full h-80"></div>
    </div>

    <!-- Visitors Chart (or Customer Demographics as per image inspiration) -->
    <div class="glass-panel p-6 rounded-xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">New Users Growth</h3>
            <button class="text-slate-400 hover:text-white"><i class="fas fa-ellipsis-v"></i></button>
        </div>
        <div id="visitorsChart" class="w-full h-80"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Sales Chart (Gradient Area)
    const salesOptions = {
        series: [{
            name: 'Revenue',
            data: <?php echo json_encode($chart_revenue); ?>
        }, {
            name: 'Sales',
            data: <?php echo json_encode($chart_sales); ?>
        }],
        chart: {
            height: 320,
            type: 'area',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent'
        },
        colors: ['#6366f1', '#a855f7'],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: <?php echo json_encode($chart_labels); ?>,
            labels: { style: { colors: '#94a3b8' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { 
                style: { colors: '#94a3b8' },
                formatter: function (value) {
                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                    if (value >= 1000) return (value / 1000).toFixed(1) + 'k';
                    return value;
                }
            }
        },
        grid: {
            borderColor: '#334155',
            strokeDashArray: 4,
            yaxis: { lines: { show: true } }
        },
        theme: { mode: 'dark' },
        tooltip: {
            theme: 'dark',
            style: { fontSize: '12px' },
            y: {
                formatter: function (val, { seriesIndex }) {
                    if (seriesIndex === 0) { // Revenue
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                    return val + " Orders";
                }
            }
        }
    };

    const salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
    salesChart.render();

    // Visitors/Users Chart (Bar Chart)
    const visitorsOptions = {
        series: [{
            name: 'New Users',
            data: <?php echo json_encode($visitor_data); ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent'
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 4,
                borderRadiusApplication: 'end', // Only round the top
            },
        },
        dataLabels: { enabled: false },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: <?php echo json_encode($visitor_labels); ?>,
            labels: { style: { colors: '#94a3b8' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { style: { colors: '#94a3b8' } }
        },
        fill: {
            opacity: 1,
            colors: ['#3b82f6'] // Blue
        },
        grid: {
            borderColor: '#334155',
            strokeDashArray: 4,
        },
        theme: { mode: 'dark' },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function (val) {
                    return val + " users"
                }
            }
        }
    };

    const visitorsChart = new ApexCharts(document.querySelector("#visitorsChart"), visitorsOptions);
    visitorsChart.render();
</script>
