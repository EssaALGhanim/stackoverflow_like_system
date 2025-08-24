<?php
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // First, let's check the current state of comments
    $check_query = "SELECT * FROM comments WHERE content_type = '' OR content_type IS NULL";
    $result = $conn->query($check_query);
    
    if ($result === false) {
        throw new Exception("Error checking comments: " . $conn->error);
    }
    
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($comments) . " comments with empty content_type<br>";
    
    // Update comments based on their content_id
    // If content_id exists in questions table, it's a question comment
    // If content_id exists in answers table, it's an answer comment
    foreach ($comments as $comment) {
        // Check if it's a question comment
        $question_check = "SELECT id FROM questions WHERE id = ?";
        $stmt = $conn->prepare($question_check);
        $stmt->bind_param("i", $comment['content_id']);
        $stmt->execute();
        $question_result = $stmt->get_result();
        
        if ($question_result->num_rows > 0) {
            // It's a question comment
            $update_query = "UPDATE comments SET content_type = 'question' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $comment['id']);
            $update_stmt->execute();
            echo "Updated comment {$comment['id']} to question type<br>";
        } else {
            // Check if it's an answer comment
            $answer_check = "SELECT id FROM answers WHERE id = ?";
            $stmt = $conn->prepare($answer_check);
            $stmt->bind_param("i", $comment['content_id']);
            $stmt->execute();
            $answer_result = $stmt->get_result();
            
            if ($answer_result->num_rows > 0) {
                // It's an answer comment
                $update_query = "UPDATE comments SET content_type = 'answer' WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $comment['id']);
                $update_stmt->execute();
                echo "Updated comment {$comment['id']} to answer type<br>";
            } else {
                echo "Warning: Comment {$comment['id']} has invalid content_id {$comment['content_id']}<br>";
            }
        }
    }
    
    echo "Update completed!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 