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
            $stmt = $conn->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, start the session
                session_regenerate_id(true); // Security measure
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Redirect to the dashboard
                $redirect = $_POST['redirect'] ?? '';
                if ($redirect && strpos($redirect, '/') === 0) {
                    header("Location: " . $redirect);
                } else {
                    header("Location: /user-dashboard");
                }
                exit();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($logo_directory) ?>" />
    <title>Account - <?= htmlspecialchars($site_name) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', },
                    fontFamily: { 'sans': ['Lato', 'ui-sans-serif', 'system-ui'], 'serif': ['Playfair Display', 'serif'], }
                }
            }
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <style>
        body {
            overflow: hidden; /* Lock scroll */
            height: 100vh;
        }
        .split-screen {
            height: 100vh;
            display: flex;
            overflow: hidden;
        }
        .login-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            background-color: #F9F6F2; /* brand-bg */
            position: relative;
            z-index: 20;
            overflow-y: auto; 
        }
        /* Custom Scrollbar for panel */
        .login-panel::-webkit-scrollbar { width: 4px; }
        .login-panel::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

        .image-panel {
            flex: 1;
            background-color: #1A1A1A;
            display: none;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 1024px) {
            .image-panel { display: block; }
            .login-panel { flex: 0 0 500px; padding: 3rem; }
        }
        
        /* Input Styles */
        .input-underline {
            border-bottom: 1px solid #d1d5db;
            transition: border-color 0.3s ease;
            background: transparent;
        }
        .input-underline:focus-within {
            border-color: #1A1A1A;
        }
        .form-input-auth { 
            display: block; 
            width: 100%; 
            padding: 0.75rem 0; 
            font-size: 0.875rem; 
            border: none; 
            background: transparent;
        }
        .form-input-auth:focus { outline: none; box-shadow: none; }
        
        .btn-primary {
            background-color: #1A1A1A;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #333;
            transform: translateY(-1px);
        }
        
        /* Slideshow Animation */
        .slide-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 2s ease-in-out, transform 10s ease-out;
            transform: scale(1.1); /* Zoom effect */
            z-index: 0;
        }
        
        .slide-image.active {
            opacity: 1;
            transform: scale(1);
            z-index: 1;
        }

        /* Text Animation */
        .slide-text {
            position: absolute;
            bottom: 3rem;
            left: 3rem;
            z-index: 20;
            color: white;
            max-width: 32rem;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 1s ease-out, transform 1s ease-out;
            pointer-events: none;
        }
        .slide-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 100%);
            z-index: 10;
        }
        
        /* Toastify */
        .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }

        .hidden-form { display: none; }

        /* Fade In Animation for Form */
        .fade-in { animation: fadeIn 0.8s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text h-screen overflow-hidden">

<div class="split-screen" id="app-container">
    
    <!-- Left Panel: Forms -->
    <div class="login-panel shadow-2xl">
        
        <!-- Back to Home Link -->
        <div class="mb-8">
            <a href="/home" class="inline-flex items-center text-xs font-bold uppercase tracking-widest text-brand-gray hover:text-brand-text transition-colors">
                <i data-feather="arrow-left" class="w-3 h-3 mr-2"></i>
                Back to Website
            </a>
        </div>

        <div class="w-full max-w-sm mx-auto fade-in" style="animation-delay: 0.1s;">
            <!-- Logo -->
            <div class="mb-10 text-center">
                <img src="/images/viennabg.png" alt="<?= htmlspecialchars($site_name) ?>" class="h-12 w-auto object-contain mx-auto">
            </div>

            <!-- Toast Message display built-in block -->
            <?php if ($toast_message): ?>
                <div class="mb-4 text-center text-xs p-3 font-medium <?= $toast_type === 'success' ? 'bg-green-50 justify-center text-green-600' : 'bg-red-50 text-red-600' ?>">
                    <?= htmlspecialchars($toast_message) ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login-form-container">
                <div class="mb-6">
                    <h2 class="text-3xl font-serif font-semibold text-brand-text mb-1 text-center">Welcome Back</h2>
                    <p class="text-brand-gray text-xs text-center tracking-wide">Please enter your details to sign in.</p>
                </div>
                
                <form class="space-y-4" action="register" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect'] ?? '') ?>">
                    
                    <div class="space-y-4">
                        <div class="input-underline py-1">
                            <label class="sr-only" for="login-email">Email Address</label>
                            <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Email</label>
                            <div class="flex items-center">
                                <i data-feather="mail" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="login-email" name="email" type="email" required class="form-input-auth py-1" placeholder="Email Address">
                            </div>
                        </div>

                        <div class="input-underline relative py-1">
                        <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                            <label class="sr-only" for="login-password">Password</label>
                            <div class="flex items-center">
                                <i data-feather="lock" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="login-password" name="password" type="password" required class="form-input-auth py-1 pr-8" placeholder="Password">
                                <button type="button" class="absolute right-0 bottom-2 text-gray-400 hover:text-brand-text focus:outline-none" onclick="togglePasswordVisibility('login-password', 'login-eye-icon')">
                                    <i id="login-eye-icon" data-feather="eye" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center text-[10px] mt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" class="form-checkbox h-3 w-3 rounded border-gray-300 text-brand-text focus:ring-brand-text">
                            <span class="ml-1.5 text-brand-gray">Remember me</span>
                        </label>
                    </div>

                    <div>
                        <button type="submit" id="btn-login-submit" class="w-full flex justify-center items-center py-3 px-4 shadow-lg text-xs font-bold tracking-widest uppercase btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-text mt-6 transition-all duration-200">
                            <span>Sign In</span>
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center text-xs">
                    <p class="text-brand-gray">
                        Don't have an account? 
                        <a href="#" id="show-register-form" class="font-bold text-brand-text uppercase hover:underline transition-colors">Create one</a>
                    </p>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register-form-container" class="hidden-form">
                <div class="mb-6 text-center">
                    <h2 class="text-3xl font-serif font-semibold text-brand-text mb-1 text-center">Create Account</h2>
                    <p class="text-brand-gray text-xs tracking-wide text-center">Join <?= htmlspecialchars($site_name) ?> today.</p>
                </div>

                <form class="space-y-4" action="register" method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="space-y-3">
                        <div class="input-underline py-1">
                            <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Full Name</label>
                            <div class="flex items-center">
                                <i data-feather="user" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="register-name" name="full_name" type="text" required class="form-input-auth py-1" placeholder="Full Name">
                            </div>
                        </div>

                        <div class="input-underline py-1">
                            <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Email</label>
                            <div class="flex items-center">
                                <i data-feather="mail" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="register-email" name="email" type="email" required class="form-input-auth py-1" placeholder="Email Address">
                            </div>
                        </div>

                        <div class="input-underline relative py-1">
                        <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                            <div class="flex items-center">
                                <i data-feather="lock" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="register-password" name="password" type="password" required minlength="8" class="form-input-auth py-1 pr-8" placeholder="Create Password">
                                <button type="button" class="absolute right-0 bottom-2 text-gray-400 hover:text-brand-text focus:outline-none" onclick="togglePasswordVisibility('register-password', 'register-eye-icon')">
                                    <i id="register-eye-icon" data-feather="eye" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-underline relative py-1">
                        <label class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Confirm Password</label>
                            <div class="flex items-center">
                                <i data-feather="check-circle" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <input id="register-password-confirm" name="password_confirm" type="password" required minlength="8" class="form-input-auth py-1 pr-8" placeholder="Confirm Password">
                            </div>
                        </div>
                    </div>

                    <div class="text-[10px] leading-tight text-brand-gray mt-2">
                        By creating an account, you agree to our <a href="#" class="underline hover:text-brand-text">Terms of Service</a> and <a href="#" class="underline hover:text-brand-text">Privacy Policy</a>.
                    </div>

                    <div>
                        <button type="submit" id="btn-register-submit" class="w-full flex justify-center items-center py-3 px-4 shadow-lg text-xs font-bold tracking-widest uppercase btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-text mt-5 transition-all duration-200">
                            <span>Create Account</span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs">
                    <p class="text-brand-gray">
                        Already have an account? 
                        <a href="#" id="show-login-form" class="font-bold text-brand-text uppercase hover:underline transition-colors">Sign in</a>
                    </p>
                </div>
            </div>

            <!-- Footer info -->
            <div class="mt-8 text-center text-[10px] text-brand-gray">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>.
            </div>
            
        </div>
    </div>

    <!-- Right Panel: Image Slider (Same as Admin Login) -->
    <div class="image-panel" id="slideshow-container">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/20 z-10"></div>
        
        <!-- Slides -->
        <img src="/images/1.jpg" class="slide-image active" alt="Slide 1">
        <img src="/images/2.jpg" class="slide-image" alt="Slide 2">
        <img src="/images/3.jpg" class="slide-image" alt="Slide 3">
        <img src="/images/4.jpg" class="slide-image" alt="Slide 4">
        <img src="/images/5.jpg" class="slide-image" alt="Slide 5">
        <img src="/images/IMG_5015.JPG" class="slide-image" alt="Slide 6">
        <img src="/images/IMG_5021.JPG" class="slide-image" alt="Slide 7">
        <img src="/images/P_ST5149-Recovered.jpg" class="slide-image" alt="Slide 8">
        <img src="/images/P_ST5192.jpg" class="slide-image" alt="Slide 9">
        <img src="/images/P_ST5496.jpg" class="slide-image" alt="Slide 10">
        
        <!-- Dynamic Text Overlay -->
        <div class="slide-text active">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Style is a way to say who you are without having to speak."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Rachel Zoe</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Fashion is the armor to survive the reality of everyday life."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Bill Cunningham</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Elegance is not standing out, but being remembered."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Giorgio Armani</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Create your own style... let it be unique for yourself and yet identifiable for others."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Anna Wintour</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Fashion fades, only style remains the same."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Coco Chanel</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"You can have anything you want in life if you dress for it."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Edith Head</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Clothes mean nothing until someone lives in them."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Marc Jacobs</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Simplicity is the ultimate sophistication."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Leonardo da Vinci</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Fashion is like eating, you shouldn't stick to the same menu."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Kenzo Takada</p>
        </div>
        <div class="slide-text">
            <p class="font-serif text-3xl italic leading-tight mb-4">"Elegance is the only beauty that never fades."</p>
            <p class="text-white/80 text-sm tracking-widest uppercase">— Audrey Hepburn</p>
        </div>
        
        <!-- Slide Indicators -->
        <div class="absolute bottom-12 right-12 z-20 flex space-x-2">
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-100 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
        </div>
    </div>

</div>

<script>
    feather.replace();

    // Setup Toastify if message is set
    <?php if ($toast_message): ?>
        Toastify({
            text: <?= json_encode($toast_message) ?>,
            duration: 4000,
            gravity: "top", // `top` or `bottom`
            position: "right", // `left`, `center` or `right`
            backgroundColor: <?= $toast_type === 'success' ? '"#10B981"' : '"#EF4444"' ?>, // Green or Red
            stopOnFocus: true, // Prevents dismissing of toast on hover
        }).showToast();
    <?php endif; ?>

    // Form Processing Animations
    const loginForm = document.querySelector('#login-form-container form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Browser HTML5 validation runs before this. If it reaches here, form is valid.
            const btn = document.getElementById('btn-login-submit');
            if (btn) {
                btn.innerHTML = '<i data-feather="loader" class="w-4 h-4 mr-2 animate-spin"></i> <span>Processing...</span>';
                btn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                feather.replace();
            }
        });
    }

    const regForm = document.querySelector('#register-form-container form');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            // Check password match before processing
            const pass = document.getElementById('register-password').value;
            const confirmPass = document.getElementById('register-password-confirm').value;
            if (pass !== confirmPass) {
                e.preventDefault();
                Toastify({
                    text: "Passwords do not match.",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#EF4444",
                    stopOnFocus: true,
                }).showToast();
                return;
            }

            const btn = document.getElementById('btn-register-submit');
            if (btn) {
                btn.innerHTML = '<i data-feather="loader" class="w-4 h-4 mr-2 animate-spin"></i> <span>Processing...</span>';
                btn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                feather.replace();
            }
        });
    }

    // Form Toggle Logic
    const loginContainer = document.getElementById('login-form-container');
    const registerContainer = document.getElementById('register-form-container');
    const showRegisterBtn = document.getElementById('show-register-form');
    const showLoginBtn = document.getElementById('show-login-form');

    showRegisterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        loginContainer.classList.add('hidden-form');
        registerContainer.classList.remove('hidden-form');
    });

    showLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        registerContainer.classList.add('hidden-form');
        loginContainer.classList.remove('hidden-form');
    });

    // Password Visibility Toggle
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            icon.setAttribute('data-feather', 'eye-off');
        } else {
            input.type = "password";
            icon.setAttribute('data-feather', 'eye');
        }
        feather.replace(); // Re-render icons
    }

    // --- Slideshow Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const slides = document.querySelectorAll('.slide-image');
        const texts = document.querySelectorAll('.slide-text');
        const indicators = document.querySelectorAll('.indicator');
        let currentSlide = 0;
        const slideInterval = 5000; // 5 seconds per slide

        if (slides.length > 1) {
            setInterval(() => {
                // Fade out current
                slides[currentSlide].classList.remove('active');
                if(texts[currentSlide]) texts[currentSlide].classList.remove('active');
                if(indicators[currentSlide]) indicators[currentSlide].classList.replace('opacity-100', 'opacity-50');

                // Move to next
                currentSlide = (currentSlide + 1) % slides.length;

                // Fade in next
                slides[currentSlide].classList.add('active');
                if(texts[currentSlide]) texts[currentSlide].classList.add('active');
                if(indicators[currentSlide]) indicators[currentSlide].classList.replace('opacity-50', 'opacity-100');
            }, slideInterval);
        }
    });

</script>

</body>
</html>