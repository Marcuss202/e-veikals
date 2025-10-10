<?php
include 'connect.php';

header('Content-Type: application/json');

try {
    // Get all unique categories from items table
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
