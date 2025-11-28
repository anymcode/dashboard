<?php
require_once '../config/db.php';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['midtrans_server_key', 'midtrans_client_key', 'midtrans_is_production'];
    
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            // Update or Insert
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
    }
    header("Location: midtrans.php?msg=saved");
    exit();
}

// Fetch Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'midtrans_%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white mb-2">Midtrans API Settings</h1>
        <p class="text-slate-400">Configure your Midtrans payment gateway integration.</p>
    </div>

    <div class="glass-panel p-8 rounded-xl">
        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Server Key</label>
                <input type="text" name="midtrans_server_key" value="<?php echo htmlspecialchars($settings['midtrans_server_key'] ?? ''); ?>"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Client Key</label>
                <input type="text" name="midtrans_client_key" value="<?php echo htmlspecialchars($settings['midtrans_client_key'] ?? ''); ?>"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 font-mono text-sm">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-400 mb-2">Environment</label>
                <select name="midtrans_is_production" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                    <option value="0" <?php echo ($settings['midtrans_is_production'] ?? '0') == '0' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                    <option value="1" <?php echo ($settings['midtrans_is_production'] ?? '0') == '1' ? 'selected' : ''; ?>>Production (Live)</option>
                </select>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
