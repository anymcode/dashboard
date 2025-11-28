<?php
require_once '../config/db.php';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_title', 'site_description', 'site_keywords'];
    
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
    }
    // Handle File Uploads
    $upload_dir = '../uploads/settings/';
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'ico'];

    foreach (['logo', 'favicon'] as $file_key) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === 0) {
            $filename = $_FILES[$file_key]['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = $file_key . '_' . time() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_path)) {
                    // Save to DB
                    $db_key = 'site_' . $file_key;
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$db_key, $new_filename, $new_filename]);
                }
            }
        }
    }
    
    header("Location: web.php?msg=saved");
    exit();
}

// Fetch Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'site_%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white mb-2">Web Settings</h1>
        <p class="text-slate-400">General website configuration and SEO.</p>
    </div>

    <div class="glass-panel p-8 rounded-xl">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Site Title</label>
                <input type="text" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Meta Description</label>
                <textarea name="site_description" rows="3"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Meta Keywords</label>
                <input type="text" name="site_keywords" value="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500"
                    placeholder="Separate with commas">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Logo</label>
                    <?php if (!empty($settings['site_logo'])): ?>
                        <div class="mb-2 bg-slate-800 p-2 rounded-lg inline-block">
                            <img src="../uploads/settings/<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Logo" class="h-12 object-contain">
                        </div>
                    <?php endif; ?>
                    <div class="border-2 border-dashed border-slate-700 rounded-lg p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer relative">
                        <input type="file" name="logo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*">
                        <i class="fas fa-image text-2xl text-slate-500 mb-2"></i>
                        <p class="text-xs text-slate-400">Click to upload</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Favicon</label>
                    <?php if (!empty($settings['site_favicon'])): ?>
                        <div class="mb-2 bg-slate-800 p-2 rounded-lg inline-block">
                            <img src="../uploads/settings/<?php echo htmlspecialchars($settings['site_favicon']); ?>" alt="Favicon" class="h-8 w-8 object-contain">
                        </div>
                    <?php endif; ?>
                    <div class="border-2 border-dashed border-slate-700 rounded-lg p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer relative">
                        <input type="file" name="favicon" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*,.ico">
                        <i class="fas fa-star text-2xl text-slate-500 mb-2"></i>
                        <p class="text-xs text-slate-400">Click to upload</p>
                    </div>
                </div>
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
