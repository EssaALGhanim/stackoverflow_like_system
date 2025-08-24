<?php
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Comments Table</h2>";

// Create comments table
$create_comments = "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('question', 'answer') NOT NULL,
    content_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_comments) === TRUE) {
    echo "✅ Comments table created successfully<br>";
} else {
    echo "❌ Error creating comments table: " . $conn->error . "<br>";
}

// Create indexes
$create_indexes = [
    "CREATE INDEX IF NOT EXISTS idx_comments_content ON comments(content_type, content_id)",
    "CREATE INDEX IF NOT EXISTS idx_comments_user ON comments(user_id)"
];

foreach ($create_indexes as $index) {
    if ($conn->query($index) === TRUE) {
        echo "✅ Index created successfully<br>";
    } else {
        echo "❌ Error creating index: " . $conn->error . "<br>";
    }
}

$conn->close();
?> 