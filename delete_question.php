<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to delete a question']);
    exit();
}

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['question_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Question ID is required']);
    exit();
}

try {
    $question_id = $data['question_id'];
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    // First verify that the user owns the question
    $check_query = "SELECT user_id FROM questions WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $question_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['user_id'] != $user_id) {
            throw new Exception('You can only delete your own questions');
        }
    } else {
        throw new Exception('Question not found');
    }

    // Delete votes for the question
    $delete_votes_query = "DELETE FROM votes WHERE content_type = 'question' AND content_id = ?";
    $delete_votes_stmt = $conn->prepare($delete_votes_query);
    $delete_votes_stmt->bind_param("i", $question_id);
    $delete_votes_stmt->execute();

    // Delete votes for all answers to this question
    $delete_answer_votes_query = "
        DELETE v FROM votes v
        JOIN answers a ON v.content_id = a.id
        WHERE v.content_type = 'answer' AND a.question_id = ?";
    $delete_answer_votes_stmt = $conn->prepare($delete_answer_votes_query);
    $delete_answer_votes_stmt->bind_param("i", $question_id);
    $delete_answer_votes_stmt->execute();

    // Delete all answers to this question
    $delete_answers_query = "DELETE FROM answers WHERE question_id = ?";
    $delete_answers_stmt = $conn->prepare($delete_answers_query);
    $delete_answers_stmt->bind_param("i", $question_id);
    $delete_answers_stmt->execute();

    // Finally, delete the question
    $delete_question_query = "DELETE FROM questions WHERE id = ?";
    $delete_question_stmt = $conn->prepare($delete_question_query);
    $delete_question_stmt->bind_param("i", $question_id);
    $delete_question_stmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Error in delete_question.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 