<?php
session_start();
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug incoming request
error_log("POST data received: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to perform this action']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        $content_id = $_POST['content_id'] ?? '';
        $body = $_POST['body'] ?? '';
        $content_type = $_POST['content_type'] ?? '';

        error_log("Adding comment - Content ID: $content_id, Content Type: $content_type");

        if (empty($content_id) || empty($body) || empty($content_type)) {
            error_log("Missing required fields - Content ID: $content_id, Content Type: $content_type, Body length: " . strlen($body));
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            // Validate content type
            if (!in_array($content_type, ['question', 'answer'])) {
                throw new Exception("Invalid content type. Must be 'question' or 'answer'");
            }
            
            // Verify content exists
            $table = $content_type === 'question' ? 'questions' : 'answers';
            $query = "SELECT id FROM $table WHERE id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare content check statement: " . $conn->error);
            }
            $stmt->bind_param("i", $content_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute content check: " . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Invalid content ID: Content not found in $table");
            }
            
            error_log("Content verified - Type: $content_type, ID: $content_id");
            
            // Insert comment
            $query = "INSERT INTO comments (user_id, content_type, content_id, body) VALUES (?, ?, ?, ?)";
            error_log("Insert query: $query");
            error_log("Parameters: user_id=$user_id, content_type=$content_type, content_id=$content_id, body=" . substr($body, 0, 100));
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            // Ensure content_type is a string
            $content_type = (string)$content_type;
            
            // Bind parameters with correct types
            // i = integer (user_id)
            // s = string (content_type)
            // i = integer (content_id)
            // s = string (body)
            $stmt->bind_param("isis", $user_id, $content_type, $content_id, $body);
            
            if (!$stmt->execute()) {
                throw new Exception("Database execute error: " . $stmt->error);
            }

            // Get the newly created comment with user info
            $comment_id = $conn->insert_id;
            $query = "SELECT c.*, u.username as author_name 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comment = $result->fetch_assoc();
            
            error_log("Comment added successfully: " . print_r($comment, true));
            
            echo json_encode([
                'success' => true,
                'comment' => $comment
            ]);
        } catch (Exception $e) {
            error_log("Comment error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add comment: ' . $e->getMessage()]);
        }
        break;

    case 'edit':
        $comment_id = $_POST['comment_id'] ?? '';
        $body = $_POST['body'] ?? '';

        if (empty($comment_id) || empty($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            // Check if user owns the comment
            $query = "SELECT user_id FROM comments WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comment = $result->fetch_assoc();

            if (!$comment || $comment['user_id'] != $user_id) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to edit this comment']);
                exit;
            }

            // Update comment
            $query = "UPDATE comments SET body = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $body, $comment_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Database execute error: " . $stmt->error);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Comment error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to edit comment: ' . $e->getMessage()]);
        }
        break;

    case 'delete':
        $comment_id = $_POST['comment_id'] ?? '';

        if (empty($comment_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing comment ID']);
            exit;
        }

        try {
            // Check if user owns the comment
            $query = "SELECT user_id FROM comments WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comment = $result->fetch_assoc();

            if (!$comment || $comment['user_id'] != $user_id) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to delete this comment']);
                exit;
            }

            // Delete comment
            $query = "DELETE FROM comments WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $comment_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Database execute error: " . $stmt->error);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Comment error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete comment: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$stmt->close();
$conn->close();
?> 