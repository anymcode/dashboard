<?php
session_start();
require_once 'config/db.php';

$message = '';
$error = '';
$demo_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate Token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $update->execute([$token, $expiry, $user['id']]);

            // Simulate Email Sending
            $message = 'We have sent a password reset link to your email.';
            
            // FOR DEMO PURPOSES ONLY: Show the link directly
            $demo_link = "reset_password.php?token=" . $token . "&email=" . urlencode($email);
        } else {
            // For security, we usually don't reveal if email exists, but for this demo we can say "Email not found" or generic message
            $error = 'We could not find an account with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AnymCode Admin</title>
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
                    <i class="fas fa-key text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Forgot Password?</h1>
                <p class="text-slate-400 text-sm mt-2">No worries, we'll send you reset instructions.</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-500/10 border border-green-500/20 text-green-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <?php if ($demo_link): ?>
                    <div class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 px-4 py-3 rounded-lg mb-6 text-sm">
                        <p class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Demo Mode:</p>
                        <p>Since email is not configured, click here to reset:</p>
                        <a href="<?php echo $demo_link; ?>" class="block mt-2 text-white bg-indigo-600 hover:bg-indigo-700 text-center py-2 rounded transition-colors">Reset Password Now</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="email" name="email" required 
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-11 pr-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-600"
                            placeholder="Enter your email">
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-indigo-500/25">
                    Send Reset Link
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="login.php" class="text-sm text-slate-400 hover:text-white transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
