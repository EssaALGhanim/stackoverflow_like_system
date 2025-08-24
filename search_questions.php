<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$tag_filter = isset($_GET['tag']) ? $_GET['tag'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$vote_filter = isset($_GET['votes']) ? $_GET['votes'] : '';

try {
    $query = "
        SELECT 
            q.*,
            u.username as author_name,
            COALESCE(
                SUM(CASE WHEN v.vote_type = 'up' THEN 1 WHEN v.vote_type = 'down' THEN -1 ELSE 0 END),
                0
            ) as vote_count
        FROM questions q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN votes v ON v.content_type = 'question' AND v.content_id = q.id
        WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($search_term)) {
        $query .= " AND (q.title LIKE ? OR q.body LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }

    if (!empty($tag_filter)) {
        $query .= " AND q.tags LIKE ?";
        $tag_param = "%$tag_filter%";
        $params[] = $tag_param;
        $types .= "s";
    }

    $query .= " GROUP BY q.id, q.title, q.body, q.user_id, q.created_at, u.username, q.tags";

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $query .= " HAVING DATE(q.created_at) = CURDATE()";
                break;
            case 'week':
                $query .= " HAVING q.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $query .= " HAVING q.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $query .= " HAVING q.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
    }

    if (!empty($vote_filter)) {
        switch ($vote_filter) {
            case 'most':
                $query .= " ORDER BY vote_count DESC";
                break;
            case 'least':
                $query .= " ORDER BY vote_count ASC";
                break;
        }
    } else {
        $query .= " ORDER BY q.created_at DESC";
    }

    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        // Convert tags string to array
        $row['tags'] = !empty($row['tags']) ? array_map('trim', explode(',', $row['tags'])) : [];
        $questions[] = $row;
    }

    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 