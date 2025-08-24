<?php
session_start();
include("database.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the start of the process
error_log("Registration process started");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Log POST data
    error_log("POST data: " . print_r($_POST, true));
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Log input values
    error_log("Username: $username, Email: $email");
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        error_log("Empty fields detected");
        header("Location: login.html?error=empty_fields&action=signup");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: $email");
        header("Location: login.html?error=invalid_email&action=signup");
        exit();
    }
    
    // Validate password strength
    $password_errors = [];
    
    if (strlen($password) < 8) {
        $password_errors[] = 'length';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $password_errors[] = 'uppercase';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $password_errors[] = 'lowercase';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $password_errors[] = 'number';
    }
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        $password_errors[] = 'special';
    }
    
    if (!empty($password_errors)) {
        error_log("Weak password detected: missing " . implode(', ', $password_errors));
        header("Location: login.html?error=weak_password&details=" . urlencode(implode(',', $password_errors)) . "&action=signup");
        exit();
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    if ($stmt === false) {
        error_log("Email check prepare failed: " . $conn->error);
        header("Location: login.html?error=database_error&action=signup");
        exit();
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Email check execute failed: " . $stmt->error);
        header("Location: login.html?error=database_error&action=signup");
        exit();
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        error_log("Email already exists: $email");
        header("Location: login.html?error=email_exists&action=signup");
        exit();
    }
    $stmt->close();
    
    // Check if username already exists
    $check_username = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_username);
    if ($stmt === false) {
        error_log("Username check prepare failed: " . $conn->error);
        header("Location: login.html?error=database_error&action=signup");
        exit();
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("Username check execute failed: " . $stmt->error);
        header("Location: login.html?error=database_error&action=signup");
        exit();
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        error_log("Username already exists: $username");
        header("Location: login.html?error=username_exists&action=signup");
        exit();
    }
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Insert new user
    $insert_user = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_user);
    if ($stmt === false) {
        error_log("User insert prepare failed: " . $conn->error);
        header("Location: login.html?error=database_error&action=signup");
        exit();
    }
    
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    if (!$stmt->execute()) {
        error_log("User insert execute failed: " . $stmt->error);
        header("Location: login.html?error=registration_failed&action=signup");
        exit();
    }
    
    // Get the new user's ID
    $user_id = $stmt->insert_id;
    error_log("New user created with ID: $user_id");
    
    // Start session and set user data
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    // Create a JavaScript snippet to set localStorage
    $userData = json_encode([
        'id' => $user_id,
        'username' => $username,
        'email' => $email
    ]);
    
    // Output JavaScript to set localStorage and redirect
    echo "<script>
        localStorage.setItem('currentUser', '" . addslashes($userData) . "');
        window.location.href = 'index.html';
    </script>";
    exit();
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header("Location: login.html?action=signup");
    exit();
}
?> 