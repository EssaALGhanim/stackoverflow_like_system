<?php
session_start();
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to post an answer']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_id = $_POST['question_id'];
    $answer_body = trim($_POST['answer_body']);
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($answer_body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Answer cannot be empty']);
        exit();
    }

    try {
        // Insert answer into database
        $query = "INSERT INTO answers (question_id, user_id, body) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("iis", $question_id, $user_id, $answer_body);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }

        // Get the newly created answer with user info
        $answer_id = $conn->insert_id;
        $query = "SELECT a.*, u.username as author_name 
                 FROM answers a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $answer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $answer = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'answer' => $answer
        ]);
    } catch (Exception $e) {
        error_log("Answer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to post answer: ' . $e->getMessage()]);
    }
    
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?> 