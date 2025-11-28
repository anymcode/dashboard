<?php
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists';
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role]);
                header("Location: index.php?msg=created");
                exit();
            } catch (PDOException $e) {
                $error = 'Error adding user: ' . $e->getMessage();
            }
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
        <h1 class="text-2xl font-bold text-white">Add New User</h1>
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
                <input type="text" name="name" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Email Address</label>
                <input type="email" name="email" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Role</label>
                <select name="role" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="flex justify-end gap-4">
                <a href="index.php" class="px-6 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600/20 hover:bg-indigo-600/30 backdrop-blur-md border border-indigo-500/30 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/10">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
