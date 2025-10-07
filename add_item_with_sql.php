<?php
include 'connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $category = trim($_POST['category'] ?? '');

    // handle file upload (optional)
    $image_url = '';
    if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $tmp = $_FILES['image_file']['tmp_name'];
        $name = basename($_FILES['image_file']['name']);
        // sanitize filename
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9-_]/', '_', $base);
        $targetName = $safeBase . '_' . time() . '.' . $ext;
        $target = $uploadsDir . '/' . $targetName;
        if (!move_uploaded_file($tmp, $target)) {
            throw new Exception('Failed to move uploaded file');
        }
        // accessible URL path relative to project
        $image_url = 'uploads/' . $targetName;
    } else {
        // optional: allow image URL field if provided
        $image_url = trim($_POST['image_url'] ?? '');
    }
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($category)) {
        throw new Exception('All fields except image are required');
    }
    
    // Insert new item into database
    $stmt = $pdo->prepare("INSERT INTO items (title, description, image_url, price, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $image_url, $price, $category]);
    
    echo json_encode(['success' => true, 'message' => 'Product added to database']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
