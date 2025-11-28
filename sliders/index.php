<?php
require_once '../config/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM sliders WHERE id = ?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch();
    
    if ($slider && file_exists("../uploads/sliders/" . $slider['image'])) {
        unlink("../uploads/sliders/" . $slider['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM sliders WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Handle Toggle Active
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE sliders SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=toggled");
    exit();
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $link = $_POST['link'] ?? '#';
    $sort_order = $_POST['sort_order'] ?? 0;
    $image_name = '';
    
    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/sliders/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = 'slider_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
    }
    
    if ($id) {
        // Update
        if ($image_name) {
            $stmt = $pdo->prepare("UPDATE sliders SET title = ?, image = ?, link = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$title, $image_name, $link, $sort_order, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE sliders SET title = ?, link = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$title, $link, $sort_order, $id]);
        }
    } else {
        // Create
        if ($image_name) {
            $stmt = $pdo->prepare("INSERT INTO sliders (title, image, link, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $image_name, $link, $sort_order]);
        }
    }
    
    header("Location: index.php?msg=saved");
    exit();
}

// Fetch All Sliders
$stmt = $pdo->query("SELECT * FROM sliders ORDER BY sort_order ASC, id DESC");
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Hero Sliders</h1>
        <p class="text-slate-400">Manage homepage slider images.</p>
    </div>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
        class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
        <i class="fas fa-plus"></i> Add New Slider
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-green-500/10 border border-green-500/20 text-green-500 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
    <i class="fas fa-check-circle"></i> 
    <?php 
        echo match($_GET['msg']) {
            'saved' => 'Slider saved successfully!',
            'deleted' => 'Slider deleted successfully!',
            'toggled' => 'Slider status updated!',
            default => 'Action completed!'
        };
    ?>
</div>
<?php endif; ?>

<div class="glass-panel rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800 bg-slate-900/50">
                    <th class="p-4 font-medium">Preview</th>
                    <th class="p-4 font-medium">Title</th>
                    <th class="p-4 font-medium">Link</th>
                    <th class="p-4 font-medium">Order</th>
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-800">
                <?php foreach ($sliders as $s): ?>
                <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="p-4">
                        <div class="w-24 h-16 rounded-lg bg-slate-800 overflow-hidden border border-slate-700">
                            <img src="../uploads/sliders/<?php echo htmlspecialchars($s['image']); ?>" class="w-full h-full object-cover">
                        </div>
                    </td>
                    <td class="p-4 text-white font-medium"><?php echo htmlspecialchars($s['title'] ?? '-'); ?></td>
                    <td class="p-4 text-slate-400 text-xs"><?php echo htmlspecialchars($s['link']); ?></td>
                    <td class="p-4 text-slate-400"><?php echo $s['sort_order']; ?></td>
                    <td class="p-4">
                        <a href="?toggle=<?php echo $s['id']; ?>" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium border transition-colors
                            <?php echo $s['is_active'] ? 'bg-green-500/10 text-green-500 border-green-500/20 hover:bg-green-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20 hover:bg-slate-500/20'; ?>">
                            <i class="fas fa-circle text-[6px]"></i>
                            <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                        </a>
                    </td>
                    <td class="p-4 text-right space-x-2">
                        <button onclick="editSlider(<?php echo htmlspecialchars(json_encode($s)); ?>)" 
                            class="text-slate-400 hover:text-white transition-colors" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $s['id']; ?>" 
                            onclick="return confirm('Delete this slider?')" 
                            class="text-slate-400 hover:text-red-400 transition-colors" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($sliders)): ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-slate-500">No sliders found. Add your first one!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-panel rounded-2xl max-w-lg w-full p-8 relative">
        <button onclick="document.getElementById('createModal').classList.add('hidden')" 
            class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 class="text-2xl font-bold text-white mb-6" id="modalTitle">Add New Slider</h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" id="sliderId">
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Title (Optional)</label>
                <input type="text" name="title" id="sliderTitle" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Image <span class="text-red-500">*</span></label>
                <input type="file" name="image" id="sliderImage" accept="image/*" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Recommended: 1200x400px</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Link (Optional)</label>
                <input type="text" name="link" id="sliderLink" value="#" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="sliderOrder" value="0" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>
            
            <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                Save Slider
            </button>
        </form>
    </div>
</div>

<script>
function editSlider(slider) {
    document.getElementById('modalTitle').textContent = 'Edit Slider';
    document.getElementById('sliderId').value = slider.id;
    document.getElementById('sliderTitle').value = slider.title || '';
    document.getElementById('sliderLink').value = slider.link || '#';
    document.getElementById('sliderOrder').value = slider.sort_order || 0;
    document.getElementById('sliderImage').removeAttribute('required');
    document.getElementById('createModal').classList.remove('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
