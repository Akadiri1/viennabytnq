<?php
header('Content-Type: application/json');

// Security Check: Enable bridge for both Legacy Admins and Role-Based Admins
$isLegacy = isset($_SESSION['admin_logged_in']);
$isRoleBased = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');

if (!$isLegacy && !$isRoleBased) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Normalize ID for API operations
$currentApiUserId = $_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? 0;

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

// --- NEW ACTIONS ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check_session') {
    echo json_encode($_SESSION);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_all_admins') {
    try {
        $sql = "
            SELECT id, username, status, created_at, 'legacy' as source, role FROM admin_users
            UNION ALL
            SELECT id, full_name as username, status, created_at, 'role' as source, role FROM users 
            WHERE role IN ('admin', 'super_admin')
            ORDER BY created_at DESC
        ";
        $stmt = $conn->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true, 
            'users' => $users, 
            'current_user_id' => $currentApiUserId,
            'is_legacy' => $isLegacy
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Helper function to get table name from source
function getTableName($source) {
    return ($source === 'role') ? 'users' : 'admin_users';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_admin') {
    $id = $_POST['id'] ?? 0;
    $source = $_POST['source'] ?? 'legacy';
    $table = getTableName($source);
    
    $stmt = $conn->prepare("UPDATE $table SET status = 'active' WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'suspend_admin' || $action === 'reactivate_admin')) {
    $id = $_POST['id'] ?? 0;
    $source = $_POST['source'] ?? 'legacy';
    $table = getTableName($source);
    
    if ($id == $currentApiUserId && $isLegacy == ($source === 'legacy')) {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own status.']);
        exit;
    }

    // Protect Super Admins from suspension
    $check = $conn->prepare("SELECT role FROM $table WHERE id = ?");
    $check->execute([$id]);
    $targetRole = $check->fetchColumn();
    if (strpos($targetRole, 'super') !== false) {
         echo json_encode(['success' => false, 'message' => 'Action Denied: Cannot suspend a Super Admin.']);
         exit;
    }

    $newStatus = ($action === 'suspend_admin') ? 'suspended' : 'active';
    $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_admin') {
    $id = $_POST['id'] ?? 0;
    $source = $_POST['source'] ?? 'legacy';
    $table = getTableName($source);

    if ($id == $currentApiUserId && $isLegacy == ($source === 'legacy')) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }
    
    // Protect Super Admins from deletion
    $check = $conn->prepare("SELECT role FROM $table WHERE id = ?");
    $check->execute([$id]);
    $targetRole = $check->fetchColumn();
    if (strpos($targetRole, 'super') !== false) {
         echo json_encode(['success' => false, 'message' => 'Action Denied: Cannot delete a Super Admin.']);
         exit;
    }

    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete.']);
    }
    exit;
}
?>
