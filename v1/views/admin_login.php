<?php
// session_start();
// Database connection is needed here.
// Assuming this is included via router, $conn should be available. 

$error_message = '';

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

// Handle suspension redirect message
if (isset($_GET['error']) && $_GET['error'] === 'suspended') {
    $error_message = "Your account has been suspended. Please contact the main administrator.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?></title>
    
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
        <!-- LEFT: Login Form -->
        <div class="login-panel">
            <div class="w-full max-w-md mx-auto fade-in py-4" style="animation-delay: 0.1s;">
                
                <!-- Logo -->
                <div class="mb-6 flex justify-center">
                    <img src="/images/viennabg.png" alt="<?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?>" class="h-12 w-auto object-contain">
                </div>

                <div class="mb-6 text-center">
                    <h1 class="text-3xl font-serif font-semibold mb-1">Welcome Back</h1>
                    <p class="text-brand-gray text-xs">Please enter your details to access.</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="bg-red-50 border-l-4 border-brand-red text-brand-red p-3 mb-4 text-xs fade-in">
                        <p class="font-medium">Access Denied</p>
                        <p><?= htmlspecialchars($error_message) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div class="input-underline py-1">
                        <label for="username" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Username</label>
                        <input type="text" id="username" name="username" class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="Username" required autofocus autocomplete="username">
                    </div>

                    <div class="input-underline py-1">
                        <label for="password" class="block text-[10px] font-bold text-brand-gray uppercase tracking-widest mb-0.5">Password</label>
                        <input type="password" id="password" name="password" class="w-full bg-transparent border-none p-1.5 text-base focus:ring-0 placeholder-gray-300" placeholder="••••••••" required autocomplete="current-password">
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center text-[10px] text-brand-gray cursor-pointer">
                            <input type="checkbox" class="form-checkbox h-3 w-3 text-brand-text border-gray-300 rounded focus:ring-brand-text">
                            <span class="ml-1.5">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3 text-xs font-bold uppercase tracking-widest mt-6 shadow-lg">
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center text-[10px] text-brand-gray">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name ?? 'Vienna by TNQ') ?>. Secured.</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Image Slideshow -->
        <div class="image-panel" id="slideshow-container">
            <div class="absolute inset-0 bg-black/20 z-10"></div>
            
            <!-- Slides - Add more images here -->
            <img src="/images/FUJIFILM-0132.jpg" class="slide-image active" alt="Slide 1">
            <img src="/images/FUJIFILM-0307.jpg" class="slide-image" alt="Slide 2">
            <img src="/images/FUJIFILM-0474.jpg" class="slide-image" alt="Slide 3">
            <img src="/images/FUJIFILM-0213.jpg" class="slide-image" alt="Slide 4">
            <img src="/images/FUJIFILM-0265.jpg" class="slide-image" alt="Slide 5">
            
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