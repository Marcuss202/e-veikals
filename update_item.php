<?php
include 'connect.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Only POST allowed');

    // Accept JSON or form POST
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) throw new Exception('Invalid id');

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = $data['price'] ?? null;
    $category = trim($data['category'] ?? '');

    if ($title === '' || $description === '' || $category === '') throw new Exception('Missing required fields');

    // handle uploaded file if present in $_FILES
    $image_url = null;
    if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $tmp = $_FILES['image_file']['tmp_name'];
        $name = basename($_FILES['image_file']['name']);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9-_]/', '_', $base);
        $targetName = $safeBase . '_' . time() . '.' . $ext;
        $target = $uploadsDir . '/' . $targetName;
        if (!move_uploaded_file($tmp, $target)) throw new Exception('Failed to move uploaded file');
        $image_url = 'uploads/' . $targetName;
    }

    if ($image_url !== null) {
        $stmt = $pdo->prepare('UPDATE items SET title = ?, description = ?, image_url = ?, price = ?, category = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$title, $description, $image_url, $price, $category, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE items SET title = ?, description = ?, price = ?, category = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$title, $description, $price, $category, $id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

