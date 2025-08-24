<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
    // Get all questions with their author usernames
    $query = "SELECT q.*, u.username 
              FROM questions q 
              JOIN users u ON q.user_id = u.id 
              ORDER BY q.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch questions']);
}
?> 