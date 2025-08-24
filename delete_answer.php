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

if (!isset($data['answer_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing answer ID']);
    exit();
}

try {
    $answer_id = $data['answer_id'];
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
        echo json_encode(['error' => 'Not authorized to delete this answer']);
        exit();
    }

    // Delete the answer
    $delete_query = "DELETE FROM answers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $answer_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete answer");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 