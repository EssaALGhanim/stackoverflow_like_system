<?php
require_once 'database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get questions for the logged-in user
    $query = "SELECT q.*, u.username 
              FROM questions q 
              JOIN users u ON q.user_id = u.id 
              WHERE q.user_id = ? 
              ORDER BY q.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user questions']);
}
?> 