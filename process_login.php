<?php
session_start();
include("database.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the start of the process
error_log("Login process started");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Log POST data
    error_log("POST data: " . print_r($_POST, true));
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Log input values
    error_log("Login attempt for email: $email");
    
    // Validate input
    if (empty($email) || empty($password)) {
        error_log("Empty fields detected");
        header("Location: login.html?error=empty_fields");
        exit();
    }
    
    // Prepare and execute the query
    $query = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        error_log("Login prepare failed: " . $conn->error);
        header("Location: login.html?error=database_error");
        exit();
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Login execute failed: " . $stmt->error);
        header("Location: login.html?error=database_error");
        exit();
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("User found with ID: " . $user['id']);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            error_log("Password verified successfully");
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            
            // Create a JavaScript snippet to set localStorage
            $userData = json_encode([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $email
            ]);
            
            // Output JavaScript to set localStorage and redirect
            echo "<script>
                localStorage.setItem('currentUser', '" . addslashes($userData) . "');
                window.location.href = 'index.html';
            </script>";
            exit();
        } else {
            error_log("Invalid password for user: $email");
            // Invalid password
            header("Location: login.html?error=invalid_password");
            exit();
        }
    } else {
        error_log("User not found: $email");
        // User not found
        header("Location: login.html?error=user_not_found");
        exit();
    }
    
    $stmt->close();
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    // If not POST request, redirect to login page
    header("Location: login.html");
    exit();
}

$conn->close();
?> 