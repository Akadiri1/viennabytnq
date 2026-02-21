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
            fontFamily: { sans: ["Lato", "ui-sans-serif", "system-ui"], serif: ["Playfair Display", "serif"], },
          },
        },
      };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
      .form-input-auth { display: block; width: 100%; padding: 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.375rem; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
      .form-input-auth:focus { outline: none; border-color: #1a1a1a; box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.2); }
      .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
    </style>
    <style>
      .form-input-auth { display: block; width: 100%; padding: 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.375rem; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
      .form-input-auth:focus { outline: none; border-color: #1a1a1a; box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.2); }
      .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
      body { overflow: hidden; height: 100vh; }
      .split-screen { height: 100vh; display: flex; overflow: hidden; }
      .login-panel { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 2rem; background-color: #F9F6F2; position: relative; z-index: 20; overflow-y: auto; }
      .login-panel::-webkit-scrollbar { width: 4px; }
      .login-panel::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
      .image-panel { flex: 1; background-color: #1A1A1A; display: none; position: relative; overflow: hidden; }
      @media (min-width: 1024px) { .image-panel { display: block; } }
      .input-underline { border-bottom: 1px solid #d1d5db; transition: border-color 0.3s ease; }
      .input-underline:focus-within { border-color: #1A1A1A; }
      .btn-primary { background-color: #1A1A1A; color: white; transition: all 0.3s ease; }
      .btn-primary:hover { background-color: #333; transform: translateY(-1px); }
      .slide-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 2s ease-in-out, transform 10s ease-out; transform: scale(1.1); z-index: 0; }
      .slide-image.active { opacity: 1; transform: scale(1); z-index: 1; }
      .slide-text { position: absolute; bottom: 3rem; left: 3rem; z-index: 20; color: white; max-width: 32rem; opacity: 0; transform: translateY(20px); transition: opacity 1s ease-out, transform 1s ease-out; pointer-events: none; }
      .slide-text.active { opacity: 1; transform: translateY(0); }
      .fade-in { animation: fadeIn 0.8s ease-out forwards; opacity: 0; transform: translateY(10px); }
      @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body class="font-sans text-brand-text h-screen overflow-hidden">
    <div class="split-screen">
        <!-- LEFT: Form Panel -->
        <div class="login-panel">
            <div class="w-full max-w-md mx-auto fade-in py-4" style="animation-delay: 0.1s;">
                <!-- Logo -->
                <div class="mb-6 flex justify-center">
                    <a href="/home"><img src="/images/viennabg.png" alt="<?= htmlspecialchars($site_name) ?>" class="h-12 w-auto object-contain"></a>
                </div>

                <!-- Sign In Form Container -->
                <div id="login-form-container">
                    <div class="mb-6 text-center">
                        <h1 class="text-3xl font-serif font-semibold mb-1">Welcome Back</h1>
                        <p class="text-brand-gray text-xs">Don't have an account? <a href="#" id="show-register-form" class="font-bold text-brand-text hover:underline">Sign up here</a></p>
                    </div>
                    <form class="space-y-4" action="auth" method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect'] ?? '') ?>">
                        
                        <div class="input-underline py-1">
                            <label for="login-email" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Email address</label>
                            <input id="login-email" name="email" type="email" autocomplete="email" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="you@example.com">
                        </div>
                        
                        <div class="input-underline py-1">
                            <label for="login-password" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                            <input id="login-password" name="password" type="password" autocomplete="current-password" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="">
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center text-[10px] text-brand-gray cursor-pointer">
                                <input type="checkbox" class="form-checkbox h-3 w-3 text-brand-text border-gray-300 rounded focus:ring-brand-text">
                                <span class="ml-1.5">Remember me</span>
                            </label>
                            <a href="/forgot-password.php" class="text-[10px] font-bold text-brand-text hover:underline">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 text-xs font-bold uppercase tracking-widest mt-6 shadow-lg">
                            Sign In
                        </button>
                    </form>
                </div>

                <!-- Register Form Container (Initially Hidden) -->
                <div id="register-form-container" class="hidden">
                     <div class="mb-6 text-center">
                        <h1 class="text-3xl font-serif font-semibold mb-1">Create an Account</h1>
                        <p class="text-brand-gray text-xs">Already have an account? <a href="#" id="show-login-form" class="font-bold text-brand-text hover:underline">Sign in here</a></p>
                    </div>

                    <form class="space-y-4" action="auth" method="POST">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect'] ?? '') ?>">
                        
                        <div class="input-underline py-1">
                            <label for="full_name" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Full Name</label>
                            <input id="full_name" name="full_name" type="text" autocomplete="name" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="Jane Doe">
                        </div>
                        
                        <div class="input-underline py-1">
                            <label for="register-email" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Email address</label>
                            <input id="register-email" name="email" type="email" autocomplete="email" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="you@example.com">
                        </div>
                        
                        <div class="input-underline py-1">
                            <label for="register-password" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                            <input id="register-password" name="password" type="password" autocomplete="new-password" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="Minimum 8 characters">
                        </div>
                        
                        <div class="input-underline py-1">
                            <label for="password_confirm" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Confirm Password</label>
                            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="">
                        </div>

                        <div class="text-[10px] text-brand-gray text-center pt-2">
                            By creating an account, you agree to our <br><a href="/terms-of-service" class="font-bold text-brand-text underline">Terms of Service</a>.
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 text-xs font-bold uppercase tracking-widest mt-6 shadow-lg">
                            Create Account
                        </button>
                    </form>
                </div>
                <!-- Footer within panel -->
                <div class="mt-8 text-center text-[10px] text-brand-gray">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Image Slideshow -->
        <div class="image-panel" id="slideshow-container">
            <div class="absolute inset-0 bg-black/20 z-10"></div>
            
            <img src="/images/setup-01.jpg" class="slide-image active" alt="Slide 1">
            <img src="/images/setup-02.jpg" class="slide-image" alt="Slide 2">
            <img src="/images/setup-03.jpg" class="slide-image" alt="Slide 3">
            <img src="/images/setup-04.jpg" class="slide-image" alt="Slide 4">
            
            <div class="slide-text active">
                <p class="font-serif text-3xl italic leading-tight mb-4">"Join the exclusive world of Vienna by TNQ."</p>
                <p class="text-white/80 text-sm tracking-widest uppercase"> Discover Elegance</p>
            </div>
            <div class="slide-text">
                <p class="font-serif text-3xl italic leading-tight mb-4">"Curated collections mapped perfectly to your refined taste."</p>
                <p class="text-white/80 text-sm tracking-widest uppercase"> Tailored For You</p>
            </div>
            <div class="slide-text">
                <p class="font-serif text-3xl italic leading-tight mb-4">"Experience seamless shopping with priority access."</p>
                <p class="text-white/80 text-sm tracking-widest uppercase"> VIP Treatment</p>
            </div>
            <div class="slide-text">
                <p class="font-serif text-3xl italic leading-tight mb-4">"Where timeless style meets contemporary luxury."</p>
                <p class="text-white/80 text-sm tracking-widest uppercase"> Welcome Home</p>
            </div>

            <div class="absolute bottom-12 right-12 z-20 flex space-x-2">
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-100 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            </div>
        </div>
    </div>

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

    <?php if ($toast_message): ?>
        showToast('<?= addslashes($toast_message) ?>', '<?= $toast_type ?>');
        
        <?php if ($toast_type === 'success' && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
            switchToLogin();
            document.getElementById('login-email').value = '<?= htmlspecialchars($_POST['email']) ?>';
        <?php endif; ?>

        <?php if ($toast_type === 'error' && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
            switchToRegister();
        <?php endif; ?>
    <?php endif; ?>

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('form') === 'register') {
        switchToRegister();
    }

    const slides = document.querySelectorAll('.slide-image');
    const texts = document.querySelectorAll('.slide-text');
    const indicators = document.querySelectorAll('.indicator');
    let currentSlide = 0;
    const slideInterval = 5000;

    if (slides.length > 1) {
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            if(texts[currentSlide]) texts[currentSlide].classList.remove('active');
            if(indicators[currentSlide]) indicators[currentSlide].classList.replace('opacity-100', 'opacity-50');

            currentSlide = (currentSlide + 1) % slides.length;

            slides[currentSlide].classList.add('active');
            if(texts[currentSlide]) texts[currentSlide].classList.add('active');
            if(indicators[currentSlide]) indicators[currentSlide].classList.replace('opacity-50', 'opacity-100');
        }, slideInterval);
    }
});
</script>
</body>
</html>
