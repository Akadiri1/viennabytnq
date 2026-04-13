<?php
// Admin login logic (same as v1)
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit();
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, email, password_hash FROM admin_login WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: /dashboard');
            exit();
        } else {
            $errorMessage = 'Invalid email or password.';
        }
    }
}

$v2_page_title = 'Login | ' . $site_name;
$v2_page_id = 'login';
$v2_header_transparent = false;
include APP_PATH."/views/includes/header_v2.php";
?>

<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding-top: var(--v2-header-height);">
  <div style="width: 100%; max-width: 400px; padding: var(--v2-space-xl);">
    <div style="text-align: center; margin-bottom: var(--v2-space-2xl);">
      <img src="<?=$logo_directory?>" alt="<?= htmlspecialchars($site_name) ?>" style="height: 48px; margin: 0 auto var(--v2-space-md);">
      <h1 style="font-size: 1.5rem;">Sign In</h1>
    </div>

    <?php if ($errorMessage): ?>
      <div style="background: #FEE2E2; color: var(--v2-error); padding: var(--v2-space-md); margin-bottom: var(--v2-space-lg); font-size: 0.8125rem; text-align: center;">
        <?= htmlspecialchars($errorMessage) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login">
      <div class="v2-form-group">
        <label class="v2-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="v2-input" required placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="v2-form-group">
        <label class="v2-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="v2-input" required placeholder="Password">
      </div>
      <button type="submit" class="v2-btn v2-btn--primary v2-btn--full" style="margin-top: var(--v2-space-md);">Sign In</button>
    </form>

    <p style="text-align: center; margin-top: var(--v2-space-xl); font-size: 0.8125rem; color: var(--v2-text-light);">
      Don't have an account? <a href="/register" style="color: var(--v2-text); font-weight: 500; text-decoration: underline;">Create one</a>
    </p>
  </div>
</main>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
