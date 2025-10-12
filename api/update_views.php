<?php
include '../config/connect.php';

header('Content-Type: application/json');

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

try {
    $stmt = $pdo->prepare("UPDATE items SET views = views + 1 WHERE id = ?");
    $stmt->execute([$itemId]);
    
    // Get updated view count
    $stmt = $pdo->prepare("SELECT views FROM items WHERE id = ?");
    $stmt->execute([$itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode(['success' => true, 'views' => $result['views']]);
    } else {
        echo json_encode(['error' => 'Item not found']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
