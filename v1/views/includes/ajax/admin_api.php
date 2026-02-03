<?php
header('Content-Type: application/json');

// Security Check: Ensure main admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure table exists (Self-healing for existing sessions)
try {
    $conn->query("SELECT 1 FROM admin_users LIMIT 1");
} catch (PDOException $e) {
    // Table missing, create it
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
    // Seed default if needed
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $conn->prepare("INSERT INTO admin_users (username, password_hash, status) VALUES (?, ?, 'active')")
             ->execute(['admin', password_hash('admin', PASSWORD_DEFAULT)]);
    }
}

// Action might be set in router.php or GET
if (!isset($action)) {
    $action = $_GET['action'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'generate_invite') {
    try {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $conn->prepare("INSERT INTO admin_invites (token, expires_at) VALUES (?, ?)");
        $stmt->execute([$token, $expires]);
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $link = $protocol . $_SERVER['HTTP_HOST'] . "/admin-setup?token=" . $token;
        echo json_encode(['success' => true, 'link' => $link]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_pending') {
    try {
        $stmt = $conn->query("SELECT id, username, created_at FROM admin_users WHERE status = 'pending' ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_admin') {
    $id = $_POST['id'] ?? 0;
    if ($id) {
        try {
            $stmt = $conn->prepare("UPDATE admin_users SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
    exit;
}
// --- NEW ACTIONS ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check_session') {
    echo json_encode($_SESSION);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_all_admins') {
    try {
        $stmt = $conn->query("SELECT id, username, status, created_at FROM admin_users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users, 'current_user_id' => $_SESSION['admin_user_id'] ?? 0]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_admin') {
    $id = $_POST['id'] ?? 0;
    if ($id == $_SESSION['admin_user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }
    try {
        $conn->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'suspend_admin' || $action === 'reactivate_admin')) {
    $id = $_POST['id'] ?? 0;
    if ($id == $_SESSION['admin_user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own status.']);
        exit;
    }
    $newStatus = ($action === 'suspend_admin') ? 'suspended' : 'active';
    try {
        $conn->prepare("UPDATE admin_users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
