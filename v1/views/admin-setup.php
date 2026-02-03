<?php
// admin-setup.php

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// 1. Verify Token
if (empty($token)) {
    die("Invalid access. Token missing.");
}

$stmt = $conn->prepare("SELECT * FROM admin_invites WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    die("This invitation link is invalid or has expired.");
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check availability
        $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = "Username already taken.";
        } else {
            // Create pending user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, status) VALUES (?, ?, 'pending')");
            if ($stmt->execute([$username, $hash])) {
                // Delete invite
                $conn->prepare("DELETE FROM admin_invites WHERE id = ?")->execute([$invite['id']]);
                $success = "Account created successfully! Please wait for a main admin to approve your account before logging in.";
            } else {
                $error = "Database error. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Space Grotesk', sans-serif; background: #0f172a; }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-indigo-600/20 blur-[100px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-purple-600/20 blur-[100px]"></div>
    </div>

    <div class="glass-card w-full max-w-md p-8 rounded-2xl relative z-10 mx-4 shadow-2xl">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">Setup Your Account</h2>
            <p class="text-slate-400 text-sm mt-1">Join the administrative team</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-200 p-4 rounded-lg text-center">
                <p class="font-medium"><?= $success ?></p>
                <a href="/admin_login" class="mt-4 inline-block text-sm text-green-400 hover:text-green-300 underline">Go to Login</a>
            </div>
        <?php elseif ($invite): ?>
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-200 text-sm p-3 rounded-lg mb-6 text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-5">
                    <label class="block text-slate-400 text-xs uppercase mb-2">Username</label>
                    <input type="text" name="username" class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-indigo-500" placeholder="Choose a username" required>
                </div>

                <div class="mb-5">
                    <label class="block text-slate-400 text-xs uppercase mb-2">Password</label>
                    <input type="password" name="password" class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-indigo-500" placeholder="••••••••" required>
                </div>

                <div class="mb-8">
                    <label class="block text-slate-400 text-xs uppercase mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-indigo-500" placeholder="••••••••" required>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-3 rounded-lg transition-transform active:scale-95">
                    Create Account
                </button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
