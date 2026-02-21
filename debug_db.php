<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=viennabytnq", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if host column exists
    try {
        $conn->query("SELECT host FROM site_visits LIMIT 1");
        echo "Host column exists.\n";
    } catch(Exception $e) {
        echo "Host column missing!\n";
    }

    // Show host distribution
    $stmt = $conn->query("SELECT host, COUNT(*) as count FROM site_visits GROUP BY host");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Host counts:\n";
    foreach($results as $row) {
        echo ($row['host'] ?? 'NULL') . ": " . $row['count'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
