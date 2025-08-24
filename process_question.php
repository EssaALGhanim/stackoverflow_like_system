<?php
session_start();
include("database.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($title) || empty($body)) {
        header("Location: ask.html?error=empty_fields");
        exit();
    }

    // Insert question into database
    $query = "INSERT INTO questions (user_id, title, body, tags) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        error_log("Question insert prepare failed: " . $conn->error);
        header("Location: ask.html?error=database_error");
        exit();
    }
    
    $stmt->bind_param("isss", $user_id, $title, $body, $tags);
    
    if ($stmt->execute()) {
        header("Location: questions.html?success=question_posted");
        exit();
    } else {
        error_log("Question insert failed: " . $stmt->error);
        header("Location: ask.html?error=question_failed");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: ask.html");
    exit();
}

$conn->close();
?> 