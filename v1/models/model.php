<?php
define("DBNAME", getenv('DB_NAME') ?: 'vienna');
define("DBUSER", getenv('DB_USER') ?: 'root');
define("DBPASS", getenv('DB_PASSWORD') ?: '');

try {
    $conn = new PDO("mysql:host=localhost;dbname=" . DBNAME, DBUSER, DBPASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to " . DBNAME;
} catch (PDOException $e) {
    echo "DB Connection failed: " . $e->getMessage();
}
?>
