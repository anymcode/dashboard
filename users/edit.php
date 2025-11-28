<?php
require_once '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email)) {
        $error = 'Name and Email are required';
    } else {
        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $hashed_password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $id]);
            }
            header("Location: index.php?msg=updated");
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating user: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="index.php" class="text-slate-400 hover:text-white mb-4 inline-block transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Users
        </a>
        <h1 class="text-2xl font-bold text-white">Edit User</h1>
    </div>

    <div class="glass-panel p-8 rounded-xl">
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Password (Leave blank to keep current)</label>
                <input type="password" name="password"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Role</label>
                <select name="role" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="flex justify-end gap-4">
                <a href="index.php" class="px-6 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
