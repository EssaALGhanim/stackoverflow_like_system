<?php
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Question ID is required']);
    exit();
}

$question_id = $_GET['id'];

try {
    // Get question details with author username
    $query = "SELECT q.*, u.username as author_name 
              FROM questions q 
              JOIN users u ON q.user_id = u.id 
              WHERE q.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
        exit();
    }
    
    $question = $result->fetch_assoc();
    
    // Get answers with author usernames
    $query = "SELECT a.*, u.username as author_name 
              FROM answers a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.question_id = ? 
              ORDER BY a.created_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $answers = [];
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    
    echo json_encode([
        'question' => $question,
        'answers' => $answers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch question details']);
}
?> 