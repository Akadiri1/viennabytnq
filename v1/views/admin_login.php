<?php
session_start();

// Database connection is needed here.
// Assuming this is included via router, $conn should be available. 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // --- SELF-HEALING: Create Table & Seed if missing (Migration Logic) ---
    try {
        // Ensure table exists
        $conn->query("SELECT 1 FROM admin_users LIMIT 1");
    } catch (Exception $e) {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `admin_users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password_hash` varchar(255) NOT NULL,
              `status` enum('active','pending','suspended') DEFAULT 'pending',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            CREATE TABLE IF NOT EXISTS `admin_invites` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `token` varchar(64) NOT NULL UNIQUE,
              `expires_at` datetime NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $passHash = password_hash('admin', PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO admin_users (username, password_hash, status) VALUES (?, ?, 'active')")->execute(['admin', $passHash]);
    }
    // ----------------------------------------------------------------------

    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'active') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: /dashboard');
            exit;
        } elseif ($user['status'] === 'pending') {
            $error_message = 'Account pending approval by a main admin.';
        } else {
            $error_message = 'Account suspended.';
        }
    } else {
        $error_message = 'Incorrect credentials';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #0f172a;
            overflow: hidden;
        }

        /* Animated Mesh Gradient Background */
        .aurora-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150vw;
            height: 150vh;
            background: radial-gradient(circle at 50% 50%, rgba(76, 29, 149, 0.4), transparent 50%),
                        radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.4), transparent 50%),
                        radial-gradient(circle at 100% 100%, rgba(236, 72, 153, 0.4), transparent 50%);
            filter: blur(60px);
            z-index: -1;
            animation: spin 20s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Glass Card */
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            transform: translateY(-20px) scale(0.85);
            color: #818cf8;
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center relative">

    <div class="aurora-bg"></div>

    <div class="glass-card w-full max-w-sm p-8 rounded-2xl relative z-10 mx-4">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-500/10 border border-indigo-500/30 mb-4 text-indigo-400 text-2xl animate-pulse">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">System Access</h2>
            <p class="text-slate-400 text-sm mt-1">Authenticate to continue</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-200 text-sm p-3 rounded-lg mb-6 text-center">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-5 relative input-group">
                <input type="text" id="username" name="username" class="peer w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 pt-5 pb-2 focus:outline-none focus:border-indigo-500 focus:bg-slate-800 transition-all placeholder-transparent" placeholder="Username" required autocomplete="off">
                <label for="username" class="absolute left-4 top-3.5 text-slate-400 text-sm transition-all pointer-events-none">Username</label>
            </div>

            <div class="mb-8 relative input-group">
                <input type="password" id="password" name="password" class="peer w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 pt-5 pb-2 focus:outline-none focus:border-indigo-500 focus:bg-slate-800 transition-all placeholder-transparent" placeholder="Password" required>
                <label for="password" class="absolute left-4 top-3.5 text-slate-400 text-sm transition-all pointer-events-none">Password</label>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-3 rounded-lg shadow-lg shadow-indigo-500/30 transition-all transform active:scale-95">
                Login
            </button>
        </form>
    </div>

</body>
</html>