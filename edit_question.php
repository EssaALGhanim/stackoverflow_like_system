<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['question_id']) || !isset($data['title']) || !isset($data['body'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $question_id = $data['question_id'];
    $title = $data['title'];
    $body = $data['body'];
    $user_id = $_SESSION['user_id'];

    // Check if user owns the question
    $check_query = "SELECT user_id FROM questions WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $question_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $question = $result->fetch_assoc();

    if (!$question || $question['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to edit this question']);
        exit();
    }

    // Update the question
    $update_query = "UPDATE questions SET title = ?, body = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $title, $body, $question_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update question");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 