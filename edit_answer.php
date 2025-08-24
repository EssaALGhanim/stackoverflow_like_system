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

if (!isset($data['answer_id']) || !isset($data['body'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $answer_id = $data['answer_id'];
    $body = $data['body'];
    $user_id = $_SESSION['user_id'];

    // Check if user owns the answer
    $check_query = "SELECT user_id FROM answers WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $answer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $answer = $result->fetch_assoc();

    if (!$answer || $answer['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to edit this answer']);
        exit();
    }

    // Update the answer
    $update_query = "UPDATE answers SET body = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $body, $answer_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update answer");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 