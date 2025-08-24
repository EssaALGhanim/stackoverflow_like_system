<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['content_type']) || !isset($data['content_id']) || !isset($data['vote_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$content_type = $data['content_type'];
$content_id = $data['content_id'];
$vote_type = $data['vote_type'];
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if user has already voted
    $check_query = "SELECT vote_type FROM votes WHERE user_id = ? AND content_type = ? AND content_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("isi", $user_id, $content_type, $content_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $existing_vote = $result->fetch_assoc();

    if ($existing_vote) {
        if ($existing_vote['vote_type'] === $vote_type) {
            // Remove vote if clicking the same button
            $delete_query = "DELETE FROM votes WHERE user_id = ? AND content_type = ? AND content_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("isi", $user_id, $content_type, $content_id);
            $delete_stmt->execute();
        } else {
            // Update vote if changing vote type
            $update_query = "UPDATE votes SET vote_type = ? WHERE user_id = ? AND content_type = ? AND content_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sisi", $vote_type, $user_id, $content_type, $content_id);
            $update_stmt->execute();
        }
    } else {
        // Insert new vote
        $insert_query = "INSERT INTO votes (user_id, content_type, content_id, vote_type) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isis", $user_id, $content_type, $content_id, $vote_type);
        $insert_stmt->execute();
    }

    // Get updated vote count
    $count_query = "SELECT 
        SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) - 
        SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) as vote_count 
        FROM votes 
        WHERE content_type = ? AND content_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("si", $content_type, $content_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $vote_count = $count_result->fetch_assoc()['vote_count'] ?? 0;

    // Get user's current vote
    $user_vote_query = "SELECT vote_type FROM votes WHERE user_id = ? AND content_type = ? AND content_id = ?";
    $user_vote_stmt = $conn->prepare($user_vote_query);
    $user_vote_stmt->bind_param("isi", $user_id, $content_type, $content_id);
    $user_vote_stmt->execute();
    $user_vote_result = $user_vote_stmt->get_result();
    $user_vote = $user_vote_result->fetch_assoc();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'vote_count' => $vote_count,
        'user_vote' => $user_vote ? $user_vote['vote_type'] : null
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process vote']);
}
?> 