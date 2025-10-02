<?php
include 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$itemId = (int)$input['item_id'];
$action = $input['action']; // 'like' or 'unlike'

try {
    if ($action === 'like') {
        $stmt = $pdo->prepare("UPDATE items SET likes = likes + 1 WHERE id = ?");
    } else if ($action === 'unlike') {
        $stmt = $pdo->prepare("UPDATE items SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
    } else {
        throw new Exception('Invalid action');
    }
    
    $stmt->execute([$itemId]);
    
    // Get updated like count
    $stmt = $pdo->prepare("SELECT likes FROM items WHERE id = ?");
    $stmt->execute([$itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode(['success' => true, 'likes' => $result['likes']]);
    } else {
        echo json_encode(['error' => 'Item not found']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
