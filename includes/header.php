<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check Authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /admin/login.php");
    exit();
}

// Fetch Current User Data (Optional, to keep sidebar up to date)
// We can use the session data or fetch fresh from DB
$current_user_name = $_SESSION['user_name'] ?? 'Admin';
$current_user_role = $_SESSION['user_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnymCode Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            900: '#0f172a', // Slate 900
                            800: '#1e293b', // Slate 800
                            700: '#334155', // Slate 700
                        },
                        accent: {
                            500: '#6366f1', // Indigo 500
                            600: '#4f46e5', // Indigo 600
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-link:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
            border-right: 3px solid #6366f1;
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(0,0,0,0) 100%);
            color: #818cf8;
            border-right: 3px solid #6366f1;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: #334155; 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569; 
        }
        /* Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
