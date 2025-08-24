<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_GET['question_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Question ID is required']);
    exit();
}

try {
    $question_id = $_GET['question_id'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get answers with vote counts and user's votes
    $query = "
        SELECT 
            a.*,
            u.username as author_name,
            COALESCE(
                SUM(CASE WHEN v.vote_type = 'up' THEN 1 WHEN v.vote_type = 'down' THEN -1 ELSE 0 END),
                0
            ) as vote_count,
            (
                SELECT vote_type 
                FROM votes 
                WHERE user_id = ? AND content_type = 'answer' AND content_id = a.id
            ) as user_vote
        FROM answers a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN votes v ON v.content_type = 'answer' AND v.content_id = a.id
        WHERE a.question_id = ?
        GROUP BY a.id
        ORDER BY a.created_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $answers = [];
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    
    echo json_encode($answers);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch answers']);
}
?> 