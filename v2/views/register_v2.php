<?php
if (session_status() == PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /user-dashboard");
    exit();
}

$toast_message = null;
$toast_type = 'error';
$active_tab = 'register';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

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
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $toast_message = "An account with this email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
                if ($stmt->execute([$full_name, $email, $password_hash])) {
                    $toast_message = "Registration successful! Please sign in.";
                    $toast_type = 'success';
                    $active_tab = 'signin';
                } else {
                    $toast_message = "An error occurred. Please try again.";
                }
            }
        }
    }

    if ($_POST['action'] === 'signin') {
        $active_tab = 'signin';
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $toast_message = "Please fill in all fields.";
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                header("Location: /user-dashboard");
                exit();
            } else {
                $toast_message = "Invalid email or password.";
            }
        }
    }
}

$v2_page_title = 'Account | ' . $site_name;
$v2_page_id = 'register';
$v2_header_transparent = false;
include APP_PATH."/views/includes/header_v2.php";
?>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding-top: var(--v2-header-height);">
  <div style="width: 100%; max-width: 420px; padding: var(--v2-space-xl);">
    <div style="text-align: center; margin-bottom: var(--v2-space-2xl);">
      <img src="<?=$logo_directory?>" alt="<?= htmlspecialchars($site_name) ?>" style="height: 48px; margin: 0 auto var(--v2-space-md);">
    </div>

    <!-- Tab Switcher -->
    <div style="display: flex; border-bottom: 1px solid var(--v2-border); margin-bottom: var(--v2-space-xl);">
      <button class="auth-tab <?= $active_tab === 'signin' ? 'active' : '' ?>" data-tab="signin" style="flex:1; padding: var(--v2-space-md) 0; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent; color: var(--v2-text-muted);">Sign In</button>
      <button class="auth-tab <?= $active_tab === 'register' ? 'active' : '' ?>" data-tab="register" style="flex:1; padding: var(--v2-space-md) 0; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent; color: var(--v2-text-muted);">Create Account</button>
    </div>

    <!-- Sign In Form -->
    <div id="tab-signin" class="auth-panel" style="<?= $active_tab !== 'signin' ? 'display:none;' : '' ?>">
      <form method="POST" action="/register">
        <input type="hidden" name="action" value="signin">
        <div class="v2-form-group">
          <label class="v2-label" for="signin-email">Email</label>
          <input type="email" id="signin-email" name="email" class="v2-input" required placeholder="your@email.com">
        </div>
        <div class="v2-form-group">
          <label class="v2-label" for="signin-password">Password</label>
          <input type="password" id="signin-password" name="password" class="v2-input" required placeholder="Password">
        </div>
        <button type="submit" class="v2-btn v2-btn--primary v2-btn--full" style="margin-top: var(--v2-space-md);">Sign In</button>
      </form>
    </div>

    <!-- Register Form -->
    <div id="tab-register" class="auth-panel" style="<?= $active_tab !== 'register' ? 'display:none;' : '' ?>">
      <form method="POST" action="/register">
        <input type="hidden" name="action" value="register">
        <div class="v2-form-group">
          <label class="v2-label" for="reg-name">Full Name</label>
          <input type="text" id="reg-name" name="full_name" class="v2-input" required placeholder="Your full name">
        </div>
        <div class="v2-form-group">
          <label class="v2-label" for="reg-email">Email</label>
          <input type="email" id="reg-email" name="email" class="v2-input" required placeholder="your@email.com">
        </div>
        <div class="v2-form-group">
          <label class="v2-label" for="reg-password">Password</label>
          <input type="password" id="reg-password" name="password" class="v2-input" required placeholder="Min 8 characters" minlength="8">
        </div>
        <div class="v2-form-group">
          <label class="v2-label" for="reg-password-confirm">Confirm Password</label>
          <input type="password" id="reg-password-confirm" name="password_confirm" class="v2-input" required placeholder="Confirm password">
        </div>
        <button type="submit" class="v2-btn v2-btn--primary v2-btn--full" style="margin-top: var(--v2-space-md);">Create Account</button>
      </form>
    </div>
  </div>
</main>

<style>
.auth-tab.active { color: var(--v2-text) !important; border-bottom-color: var(--v2-text) !important; }
</style>

<script>
document.querySelectorAll('.auth-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-panel').forEach(p => p.style.display = 'none');
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
  });
});

<?php if ($toast_message): ?>
Toastify({
  text: "<?= addslashes($toast_message) ?>",
  duration: 4000, gravity: "top", position: "right",
  style: { background: "<?= $toast_type === 'success' ? '#1A1A1A' : '#8B0000' ?>" }
}).showToast();
<?php endif; ?>
</script>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
