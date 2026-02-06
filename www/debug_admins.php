<?php
session_start();
define("D_PATH", dirname(dirname(__FILE__)));
define("APP_PATH", D_PATH."/v1");
include D_PATH."/.env/config.php";

echo "<h2>Admin Debug</h2>";
echo "<h3>Session:</h3><pre>";
print_r($_SESSION);
echo "</pre>";

try {
    $stmt = $conn->query("SELECT id, username, status, role FROM admin_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Admin Users Table:</h3><pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
