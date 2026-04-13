<?php
// CHANGE THIS TO THE PASSWORD YOU WANT TO USE
$plainTextPassword = 'viennabytnq';

// Generate the secure hash
$passwordHash = password_hash($plainTextPassword, PASSWORD_DEFAULT);

echo "<h3>Password Hash Generator</h3>";
echo "<p><strong>Your Plain-Text Password:</strong> " . htmlspecialchars($plainTextPassword) . "</p>";
echo "<p><strong>Your SECURE Hash (copy this into the database):</strong></p>";
echo "<textarea rows='3' cols='80' readonly>" . $passwordHash . "</textarea>";

echo "<br>";
$location = 'lagos';
echo "i am here in " . $location . " with my girlfriend so kindly use this " . $passwordHash . " has the password";
?>