<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get questions count
    $questions_query = "SELECT COUNT(*) as count FROM questions WHERE user_id = ?";
    $questions_stmt = $conn->prepare($questions_query);
    $questions_stmt->bind_param("i", $user_id);
    $questions_stmt->execute();
    $questions_result = $questions_stmt->get_result();
    $questions_count = $questions_result->fetch_assoc()['count'];
    
    // Get answers count
    $answers_query = "SELECT COUNT(*) as count FROM answers WHERE user_id = ?";
    $answers_stmt = $conn->prepare($answers_query);
    $answers_stmt->bind_param("i", $user_id);
    $answers_stmt->execute();
    $answers_result = $answers_stmt->get_result();
    $answers_count = $answers_result->fetch_assoc()['count'];
    
    // Calculate reputation score
    $reputation = ($questions_count * 100) + ($answers_count * 200);
    
    // Get recent questions with vote counts
    $questions_activity_query = "
        SELECT 
            q.id,
            q.title,
            q.created_at,
            u.username as author_name,
            COALESCE(
                SUM(CASE WHEN v.vote_type = 'up' THEN 1 WHEN v.vote_type = 'down' THEN -1 ELSE 0 END),
                0
            ) as vote_count
        FROM questions q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN votes v ON v.content_type = 'question' AND v.content_id = q.id
        WHERE q.user_id = ?
        GROUP BY q.id
        ORDER BY q.created_at DESC
        LIMIT 5";
    
    $questions_activity_stmt = $conn->prepare($questions_activity_query);
    $questions_activity_stmt->bind_param("i", $user_id);
    $questions_activity_stmt->execute();
    $questions_activity = $questions_activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent answers with vote counts
    $answers_activity_query = "
        SELECT 
            a.id,
            a.body,
            a.created_at,
            u.username as author_name,
            COALESCE(
                SUM(CASE WHEN v.vote_type = 'up' THEN 1 WHEN v.vote_type = 'down' THEN -1 ELSE 0 END),
                0
            ) as vote_count
        FROM answers a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN votes v ON v.content_type = 'answer' AND v.content_id = a.id
        WHERE a.user_id = ?
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT 5";
    
    $answers_activity_stmt = $conn->prepare($answers_activity_query);
    $answers_activity_stmt->bind_param("i", $user_id);
    $answers_activity_stmt->execute();
    $answers_activity = $answers_activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine and format activities
    $activities = [];
    
    foreach ($questions_activity as $question) {
        $activities[] = [
            'id' => $question['id'],
            'type' => 'question',
            'title' => $question['title'],
            'created_at' => $question['created_at'],
            'author_name' => $question['author_name'],
            'vote_count' => $question['vote_count']
        ];
    }
    
    foreach ($answers_activity as $answer) {
        $activities[] = [
            'id' => $answer['id'],
            'type' => 'answer',
            'body' => $answer['body'],
            'created_at' => $answer['created_at'],
            'author_name' => $answer['author_name'],
            'vote_count' => $answer['vote_count']
        ];
    }
    
    // Sort activities by date
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Take only the 5 most recent activities
    $activities = array_slice($activities, 0, 5);
    
    echo json_encode([
        'questions_count' => $questions_count,
        'answers_count' => $answers_count,
        'reputation' => $reputation,
        'activities' => $activities
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch statistics']);
}
?> 