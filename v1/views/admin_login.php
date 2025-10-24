<?php
// session_start();

// Admin credentials (for demonstration; in production, use environment variables or a more secure method)
$admin_username = 'admin';
$admin_password = 'admin';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    //NOTE: session_start() must be uncommented for the login logic to work fully
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /dashboard');
        exit();
    } else {
        $error_message = 'Invalid username or password';
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Base styles for the body */
        body { 
            font-family: 'Poppins', sans-serif; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            position: relative;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Hide overflow from the panning effect */
            background-color: #2D3748; /* Fallback/base color */
        }

        /* -------------------------------------- */
        /* Single Background Image with Panning Animation */
        /* -------------------------------------- */
        body::before {
            content: "";
            position: absolute;
            top: -10%; /* Start slightly off-center for movement */
            left: -10%; /* Start slightly off-center for movement */
            width: 120%; /* Make it larger than viewport to allow panning */
            height: 120%; /* Make it larger than viewport to allow panning */
            
            /* 🚨 IMPORTANT: Replace with your single background image path */
            background-image: url('/images/4.jpg'); 
            background-size: cover; 
            background-position: center; /* Initial position */
            background-repeat: no-repeat; 
            
            z-index: -1; /* Place it behind the login card */
            
            /* Apply the continuous panning animation */
            animation: panBackground 60s infinite alternate ease-in-out; /* Longer duration, smoother */
        }
        
        @keyframes panBackground {
          0% {
            background-position: 0% 0%; /* Start top-left */
          }
          100% {
            background-position: 100% 100%; /* End bottom-right */
          }
        }
        
        /* -------------------------------------- */
        /* Glassmorphism Styles */
        /* -------------------------------------- */
        .login-card { 
            /* Glassmorphism Effect */
            background: rgba(255, 255, 255, 0.2); 
            backdrop-filter: blur(15px); 
            -webkit-backdrop-filter: blur(15px); 
            border: 1px solid rgba(255, 255, 255, 0.3); 
            
            /* Existing styles */
            border-radius: 1rem; 
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); 
            max-width: 400px; 
            width: 100%; 
            padding: 2.5rem; 
            z-index: 10; 
        }
        
        /* Ensure text and inputs contrast well */
        .login-card h1,
        .login-card label {
            color: #ffffff; 
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5); 
        }

        .form-input { 
            width: 100%; 
            padding: 0.75rem 1rem; 
            background-color: rgba(255, 255, 255, 0.85); 
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem; 
            transition: all 0.2s; 
            color: #1f2937; 
        }
        .form-input:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3); 
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1 class="text-2xl font-bold text-center mb-8 text-white">Admin Login</h1> 
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p><?= $error_message ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-6">
                <label class="block text-white text-sm font-medium mb-2" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            <div class="mb-6">
                <label class="block text-white text-sm font-medium mb-2" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-300">Login</button>
        </form>
    </div>

    </body>
</html>