<?php
include 'connect.php';

try {
    // Fetch all items from the database
    $stmt = $pdo->prepare("SELECT * FROM items ORDER BY created_at DESC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($items);
} catch(PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
