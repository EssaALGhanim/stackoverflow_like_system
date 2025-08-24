<?php
include("database.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Test User</h2>";

// Test user credentials
$username = "testuser";
$email = "test@example.com";
$password = "password123"; // This will be hashed

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$check_user = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_user);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Test user already exists.<br>";
    echo "You can log in with:<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $password . "<br>";
} else {
    // Insert test user
    $insert_user = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_user);
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Test user created successfully!<br>";
        echo "You can now log in with:<br>";
        echo "Email: " . $email . "<br>";
        echo "Password: " . $password . "<br>";
    } else {
        echo "Error creating test user: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?> 