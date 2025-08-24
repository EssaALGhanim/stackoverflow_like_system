<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Get all unique tags from questions
    $query = "SELECT DISTINCT TRIM(tag) as tag 
              FROM questions 
              CROSS JOIN LATERAL STRING_SPLIT(tags, ',') as tag 
              WHERE tag != '' 
              ORDER BY tag";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($tags);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 