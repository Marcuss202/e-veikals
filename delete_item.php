<?php
include 'connect.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['id'])) throw new Exception('Invalid input');
    $id = (int)$data['id'];
    if ($id <= 0) throw new Exception('Invalid id');

    // fetch image path to delete file
    $stmt = $pdo->prepare('SELECT image_url FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $image = $row['image_url'];
        if ($image) {
            $path = __DIR__ . '/' . $image;
            if (file_exists($path)) @unlink($path);
        }
    }

    $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
