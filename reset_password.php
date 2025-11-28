<?php
session_start();
require_once 'config/db.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';

// Verify Token
if (empty($token) || empty($email)) {
    $error = 'Invalid reset link.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'Invalid or expired reset token.';
    }
}

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        $success = 'Password has been reset successfully! You can now login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AnymCode Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #e2e8f0; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-[url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center relative">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-slate-900/90"></div>

    <div class="relative z-10 w-full max-w-md p-6">
        <div class="glass-panel p-8 rounded-2xl shadow-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-500/20 text-indigo-500 mb-4">
                    <i class="fas fa-lock-open text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Set New Password</h1>
                <p class="text-slate-400 text-sm mt-2">Create a strong password for your account.</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-500/10 border border-green-500/20 text-green-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <a href="login.php" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg text-center transition-all shadow-lg shadow-indigo-500/25">
                    Go to Login
                </a>
            <?php elseif ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="forgot_password.php" class="block mt-4 text-center text-indigo-400 hover:text-indigo-300 text-sm">
                    Request a new link
                </a>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">New Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="password" name="password" required 
                                class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-11 pr-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-600"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Confirm Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="password" name="confirm_password" required 
                                class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-11 pr-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-600"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-indigo-500/25">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
