<?php
// admin-setup.php

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// 1. Verify Token
if (empty($token)) {
    die("Invalid access. Token missing.");
}

// Database connection assumed via router inclusion
// $conn...

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
            // Create pending user with 'admin' role (NOT super_admin)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, status, role) VALUES (?, ?, 'pending', 'admin')");
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
    <title>Admin Setup | <?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', 'brand-green': '#10B981' },
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
        }
        
        /* Input Styles */
        .input-underline {
            border-bottom: 1px solid #d1d5db;
            transition: border-color 0.3s ease;
        }
        .input-underline:focus-within {
            border-color: #1A1A1A;
        }
        
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
        
        /* Fade In Animation for Form */
        .fade-in { animation: fadeIn 0.8s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="font-sans text-brand-text h-screen overflow-hidden">

    <div class="split-screen">
        <!-- LEFT: Setup Form -->
        <div class="login-panel">
            <div class="w-full max-w-md mx-auto fade-in py-4" style="animation-delay: 0.1s;">
                
                <!-- Logo -->
                <div class="mb-6 flex justify-center">
                    <img src="/images/viennabg.png" alt="<?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?>" class="h-12 w-auto object-contain">
                </div>

                <div class="mb-6 text-center">
                    <h1 class="text-3xl font-serif font-semibold mb-1">Admin Setup</h1>
                    <p class="text-brand-gray text-xs">Create your administrative account.</p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-brand-green text-brand-green p-6 mb-8 text-sm fade-in rounded bg-opacity-10">
                        <p class="font-medium text-lg mb-2">Success!</p>
                        <p class="mb-4"><?= htmlspecialchars($success) ?></p>
                        <a href="/admin_login" class="inline-block bg-brand-green text-white px-6 py-2 rounded shadow hover:bg-opacity-90 transition">Go to Login</a>
                    </div>
                <?php else: ?>

                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-brand-red text-brand-red p-3 mb-4 text-xs fade-in">
                            <p class="font-medium">Error</p>
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div class="input-underline py-1">
                            <label for="username" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Username</label>
                            <input type="text" id="username" name="username" class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="Username" required autofocus autocomplete="username">
                        </div>

                        <div class="input-underline py-1">
                            <label for="password" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                            <input type="password" id="password" name="password" class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="••••••••" required autocomplete="new-password">
                        </div>
                        
                        <div class="input-underline py-1">
                            <label for="confirm_password" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 text-xs font-bold uppercase tracking-widest mt-6 shadow-lg">
                            Create Account
                        </button>
                    </form>
                <?php endif; ?>

                <div class="mt-6 text-center text-[10px] text-brand-gray">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?>. Secured.</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Image Slideshow -->
        <div class="image-panel" id="slideshow-container">
            <div class="absolute inset-0 bg-black/20 z-10"></div>
            
            <!-- Slides - Add more images here -->
            <img src="/images/FUJIFILM-0298.jpg" class="slide-image active" alt="Slide 1">
            <img src="/images/FUJIFILM-0354.jpg" class="slide-image" alt="Slide 2">
            <img src="/images/FUJIFILM-0461.jpg" class="slide-image" alt="Slide 3">
            <img src="/images/FUJIFILM-0524.jpg" class="slide-image" alt="Slide 4">
            <img src="/images/FUJIFILM-0539.jpg" class="slide-image" alt="Slide 5">
            
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
            
            <!-- Slide Indicators -->
            <div class="absolute bottom-12 right-12 z-20 flex space-x-2">
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-100 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
                <div class="w-2 h-2 rounded-full bg-white transition-opacity duration-300 opacity-50 indicator"></div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        // --- Slideshow Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.slide-image');
            const texts = document.querySelectorAll('.slide-text');
            const indicators = document.querySelectorAll('.indicator');
            let currentSlide = 0;
            const slideInterval = 5000; 

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
