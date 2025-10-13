<?php
include '../config/connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['hashtags'] ?? '');

    // handle file upload
    $image_url = '';
    if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing file upload");
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
            error_log("Created uploads directory: " . $uploadsDir);
        }
        $tmp = $_FILES['image_file']['tmp_name'];
        $name = basename($_FILES['image_file']['name']);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9-_]/', '_', $base);
        $targetName = $safeBase . '_' . time() . '.' . $ext;
        $target = $uploadsDir . '/' . $targetName;
        
        error_log("Moving file from $tmp to $target");
        if (!move_uploaded_file($tmp, $target)) {
            error_log("Failed to move uploaded file from $tmp to $target");
            throw new Exception('Failed to move uploaded file');
        }
        
        chmod($target, 0644);
        
        $image_url = 'uploads/' . $targetName;
        error_log("File uploaded successfully: " . $image_url);
    } else {
        // Check for upload errors
        if (!empty($_FILES['image_file'])) {
            $error = $_FILES['image_file']['error'];
            error_log("File upload error code: " . $error);
            switch($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    error_log("File too large");
                    break;
                case UPLOAD_ERR_PARTIAL:
                    error_log("File upload was interrupted");
                    break;
                case UPLOAD_ERR_NO_FILE:
                    error_log("No file was uploaded");
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    error_log("Missing temporary folder");
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    error_log("Failed to write file to disk");
                    break;
                case UPLOAD_ERR_EXTENSION:
                    error_log("File upload stopped by extension");
                    break;
            }
        }
        $image_url = trim($_POST['image_url'] ?? '');
        error_log("Using image URL: " . $image_url);
    }
    
    if (empty($title) || empty($description) || empty($category)) {
        throw new Exception('All fields except image are required');
    }
    
    // Insert new item into database
    $stmt = $pdo->prepare("INSERT INTO items (title, description, image_url, category) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $image_url, $category]);
    
    echo json_encode(['success' => true, 'message' => 'Product added to database']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
