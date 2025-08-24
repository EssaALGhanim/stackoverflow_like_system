<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Question ID is required']);
    exit();
}

try {
    $question_id = $_GET['id'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get question with vote count and user's vote, including tags
    $query = "
        SELECT 
            q.*,
            u.username as author_name,
            COALESCE(
                SUM(CASE WHEN v.vote_type = 'up' THEN 1 WHEN v.vote_type = 'down' THEN -1 ELSE 0 END),
                0
            ) as vote_count,
            (
                SELECT vote_type 
                FROM votes 
                WHERE user_id = ? AND content_type = 'question' AND content_id = q.id
            ) as user_vote
        FROM questions q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN votes v ON v.content_type = 'question' AND v.content_id = q.id
        WHERE q.id = ?
        GROUP BY q.id, q.title, q.body, q.user_id, q.created_at, u.username, q.tags";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $user_id, $question_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }
    
    if ($row = $result->fetch_assoc()) {
        // Convert comma-separated tags string to array
        $row['tags'] = !empty($row['tags']) ? array_map('trim', explode(',', $row['tags'])) : [];
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
    }
} catch (Exception $e) {
    error_log("Error in get_question.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch question: ' . $e->getMessage()]);
}
?> 