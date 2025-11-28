<?php
session_start();
require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                $error = 'Access Denied: You do not have admin privileges.';
            } else {
                // Login Success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login if you had that column, but we don't, so just redirect
                // Redirect based on parameter or default to dashboard
                $redirect_url = $_REQUEST['redirect'] ?? 'index.php';
                header("Location: " . $redirect_url);
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BlackBox Admin</title>
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
                    <i class="fas fa-cube text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Welcome Back</h1>
                <p class="text-slate-400 text-sm mt-2">Sign in to access your dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?php if (isset($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="email" name="email" required 
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-11 pr-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-600"
                            placeholder="admin@anymcode.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="password" name="password" required 
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-11 pr-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-600"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-300">
                        <input type="checkbox" class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="text-indigo-400 hover:text-indigo-300">Forgot password?</a>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-indigo-500/25">
                    Sign In
                </button>
            </form>

            <div class="mt-8 text-center text-xs text-slate-500">
                &copy; <?php echo date('Y'); ?> AnymCode Admin. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
