<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
//require_once 'includes/db_connection.php'; // Make sure this path is correct

// If user is already logged in, redirect them to a dashboard/account page
if (isset($_SESSION['user_id'])) {
    header("Location: /user-dashboard"); // Create this page for logged-in users
    exit();
}

$site_name = 'Vienna by TNQ';
$logo_directory = 'images/favicon.png';

// This is where we will store the message for Toastify
$toast_message = null;
$toast_type = 'error'; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    // --- HANDLE REGISTRATION ---
    if ($_POST['action'] === 'register') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if (empty($full_name) || empty($email) || empty($password)) {
            $toast_message = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $toast_message = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $toast_message = "Password must be at least 8 characters long.";
        } elseif ($password !== $password_confirm) {
            $toast_message = "Passwords do not match.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $toast_message = "An account with this email already exists.";
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into the database
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
                if ($stmt->execute([$full_name, $email, $password_hash])) {
                    $toast_message = "Registration successful! Please sign in.";
                    $toast_type = 'success';
                } else {
                    $toast_message = "An error occurred. Please try again later.";
                }
            }
        }
    }

    // --- HANDLE LOGIN ---
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $toast_message = "Please enter both email and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, password_hash, role, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if user is suspended
                if (($user['status'] ?? 'active') === 'suspended') {
                    $toast_message = "Your account has been suspended. Please contact support.";
                    $toast_type = 'error';
                } else {
                    // Password is correct, start the session
                    session_regenerate_id(true); // Security measure
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    // Redirect based on role
                    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin') {
                        header("Location: /dashboard"); 
                    } else {
                        $redirect = $_POST['redirect'] ?? '';
                        // Basic security check to ensure it's a local URL (starts with /)
                        if ($redirect && strpos($redirect, '/') === 0) {
                            header("Location: " . $redirect);
                        } else {
                            header("Location: /user-dashboard");
                        }
                    }
                    exit();
                }
            } else {
                $toast_message = "Invalid email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($logo_directory) ?>" />
    <title>Account - <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: { "brand-bg": "#F9F6F2", "brand-text": "#1A1A1A", "brand-gray": "#6B7280", "brand-red": "#EF4444", },
            fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui"], serif: ["Cormorant Garamond", "serif"], },
          },
        },
      };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
      .form-input-auth { display: block; width: 100%; padding: 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.375rem; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
      .form-input-auth:focus { outline: none; border-color: #1a1a1a; box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.2); }
      .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

    <header class="bg-brand-bg border-b border-gray-200/60">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-center h-16"><a href="/home"><div class="text-2xl font-serif font-bold tracking-widest"><?= htmlspecialchars($site_name) ?></div></a></div></div>
    </header>

    <main class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-white p-8 md:p-10 rounded-xl shadow-sm overflow-hidden">

            <!-- Sign In Form Container -->
            <div id="login-form-container">
                <div>
                    <h1 class="text-center text-4xl font-serif font-bold text-brand-text">Welcome Back</h1>
                    <p class="mt-2 text-center text-sm text-brand-gray">Don't have an account? <a href="#" id="show-register-form" class="font-medium text-brand-text hover:underline">Sign up here</a></p>
                </div>
                <form class="mt-8 space-y-6" action="auth" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect'] ?? '') ?>">
                    <div class="space-y-4">
                        <div>
                            <label for="login-email" class="text-sm font-medium text-brand-gray">Email address</label>
                            <input id="login-email" name="email" type="email" autocomplete="email" required class="form-input-auth mt-1" placeholder="you@example.com">
                        </div>
                        <div>
                            <label for="login-password" class="text-sm font-medium text-brand-gray">Password</label>
                            <input id="login-password" name="password" type="password" autocomplete="current-password" required class="form-input-auth mt-1" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="flex items-center justify-end text-sm"><div class="font-medium"><a href="/forgot-password.php" class="text-brand-text hover:underline">Forgot password?</a></div></div>
                    <div><button type="submit" class="w-full flex justify-center py-3 px-4 text-sm font-semibold rounded-md text-white bg-brand-text hover:bg-gray-800 transition-colors">Sign In</button></div>
                </form>
            </div>

            <!-- Register Form Container (Initially Hidden) -->
            <div id="register-form-container" class="hidden">
                 <div>
                    <h1 class="text-center text-4xl font-serif font-bold text-brand-text">Create an Account</h1>
                    <p class="mt-2 text-center text-sm text-brand-gray">Already have an account? <a href="#" id="show-login-form" class="font-medium text-brand-text hover:underline">Sign in here</a></p>
                </div>
                <form class="mt-8 space-y-6" action="auth" method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect'] ?? '') ?>">
                    <div class="space-y-4">
                        <div>
                            <label for="full_name" class="text-sm font-medium text-brand-gray">Full Name</label>
                            <input id="full_name" name="full_name" type="text" autocomplete="name" required class="form-input-auth mt-1" placeholder="Jane Doe">
                        </div>
                        <div>
                            <label for="register-email" class="text-sm font-medium text-brand-gray">Email address</label>
                            <input id="register-email" name="email" type="email" autocomplete="email" required class="form-input-auth mt-1" placeholder="you@example.com">
                        </div>
                        <div>
                            <label for="register-password" class="text-sm font-medium text-brand-gray">Password</label>
                            <input id="register-password" name="password" type="password" autocomplete="new-password" required class="form-input-auth mt-1" placeholder="Minimum 8 characters">
                        </div>
                        <div>
                            <label for="password_confirm" class="text-sm font-medium text-brand-gray">Confirm Password</label>
                            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required class="form-input-auth mt-1" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="text-xs text-brand-gray text-center">By creating an account, you agree to our <a href="/terms-of-service" class="underline hover:text-brand-text">Terms of Service</a>.</div>
                    <div><button type="submit" class="w-full flex justify-center py-3 px-4 text-sm font-semibold rounded-md text-white bg-brand-text hover:bg-gray-800 transition-colors">Create Account</button></div>
                </form>
            </div>

        </div>
    </main>
    
    <footer class="bg-white border-t border-gray-200"><div class="p-6 text-center"><p class="text-xs text-brand-gray">© <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p></div></footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
    feather.replace();

    const loginContainer = document.getElementById('login-form-container');
    const registerContainer = document.getElementById('register-form-container');
    const showRegisterLink = document.getElementById('show-register-form');
    const showLoginLink = document.getElementById('show-login-form');

    const showToast = (text, type = "success") => {
        const backgroundColor = type === "success" 
            ? "linear-gradient(to right, #00b09b, #96c93d)" 
            : "linear-gradient(to right, #ff5f6d, #ffc371)";
        Toastify({ 
            text, 
            duration: 3000, 
            gravity: "top", 
            position: "right", 
            style: { background: backgroundColor }
        }).showToast();
    };

    const switchToRegister = (e) => {
        if(e) e.preventDefault();
        loginContainer.classList.add('hidden');
        registerContainer.classList.remove('hidden');
    };

    const switchToLogin = (e) => {
        if(e) e.preventDefault();
        registerContainer.classList.add('hidden');
        loginContainer.classList.remove('hidden');
    };

    showRegisterLink.addEventListener('click', switchToRegister);
    showLoginLink.addEventListener('click', switchToLogin);

    // This block of PHP will execute if there was a POST request
    <?php if ($toast_message): ?>
        showToast('<?= addslashes($toast_message) ?>', '<?= $toast_type ?>');
        
        // If registration was successful, switch to the login form
        <?php if ($toast_type === 'success' && $_POST['action'] === 'register'): ?>
            switchToLogin();
            // Pre-fill the email field for convenience
            document.getElementById('login-email').value = '<?= htmlspecialchars($_POST['email']) ?>';
        <?php endif; ?>

        // If registration failed, keep the register form visible
        <?php if ($toast_type === 'error' && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
            switchToRegister();
        <?php endif; ?>
    <?php endif; ?>

    // Check for URL param to show register form directly
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('form') === 'register') {
        switchToRegister();
    }
});
</script>
</body>
</html>