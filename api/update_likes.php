<?php
session_start();
include '../config/connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User must be logged in to like items']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id parameter']);
    exit;
}

$itemId = (int)$input['id'];
$userId = (int)$_SESSION['user_id'];
$action = $input['action'] ?? 'like';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_item (user_id, item_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
    )");
    
    if ($action === 'like') {
        $stmt = $pdo->prepare("SELECT id FROM user_likes WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['error' => 'Item already liked by user']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO user_likes (user_id, item_id) VALUES (?, ?)");
        $stmt->execute([$userId, $itemId]);
        
        $stmt = $pdo->prepare("UPDATE items SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$itemId]);
        
    } else if ($action === 'unlike') {
        $stmt = $pdo->prepare("DELETE FROM user_likes WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
        
        $stmt = $pdo->prepare("UPDATE items SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
        $stmt->execute([$itemId]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
    $stmt = $pdo->prepare("SELECT likes FROM items WHERE id = ?");
    $stmt->execute([$itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id FROM user_likes WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$userId, $itemId]);
    $userLiked = $stmt->rowCount() > 0;
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'likes' => $result['likes'],
            'userLiked' => $userLiked
        ]);
    } else {
        echo json_encode(['error' => 'Item not found']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
