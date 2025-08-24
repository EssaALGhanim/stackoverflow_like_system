<?php
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Answer ID is required']);
    exit();
}

try {
    $answer_id = $_GET['id'];
    
    $query = "SELECT question_id FROM answers WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $answer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['question_id' => $row['question_id']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Answer not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch question ID']);
}
?> 