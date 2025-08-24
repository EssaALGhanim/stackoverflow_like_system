<?php
session_start();
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug database connection
error_log("Database connection status: " . ($conn->connect_error ? "Failed" : "Success"));

// First, let's check all comments in the database
$all_comments_query = "SELECT COUNT(*) as total FROM comments";
$all_comments_result = $conn->query($all_comments_query);
if ($all_comments_result === false) {
    error_log("Error checking total comments: " . $conn->error);
} else {
    $total_comments = $all_comments_result->fetch_assoc()['total'];
    error_log("Total comments in database: " . $total_comments);
}

// Get parameters
$content_type = $_GET['content_type'] ?? '';
$content_id = $_GET['content_id'] ?? '';

// Debug input parameters
error_log("Received request for comments - Content Type: " . $content_type . ", Content ID: " . $content_id);

// Validate parameters
if (empty($content_type) || empty($content_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Get comments with user information
    $query = "SELECT c.*, u.username as author_name 
              FROM comments c 
              INNER JOIN users u ON c.user_id = u.id 
              WHERE c.content_type = ? AND c.content_id = ? 
              ORDER BY c.created_at DESC";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", $content_type, $content_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $comments = [];
    
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'content_type' => $row['content_type'],
            'content_id' => $row['content_id'],
            'body' => $row['body'],
            'created_at' => $row['created_at'],
            'author_name' => $row['author_name']
        ];
    }
    
    // Debug the results
    error_log("SQL Query: " . $query);
    error_log("Content Type: " . $content_type);
    error_log("Content ID: " . $content_id);
    error_log("Number of comments found: " . count($comments));
    error_log("Comments data: " . json_encode($comments));
    
    echo json_encode($comments);
    
} catch (Exception $e) {
    error_log("Error in get_comments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch comments']);
}

$stmt->close();
$conn->close();
?> 