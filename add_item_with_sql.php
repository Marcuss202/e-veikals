<?php
include 'connect_silent.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($image_url) || empty($category)) {
        throw new Exception('All fields are required');
    }
    
    // Insert new item into database
    $stmt = $pdo->prepare("INSERT INTO items (title, description, image_url, price, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $image_url, $price, $category]);
    
    // Now update the SQL file
    updateSQLFile($title, $description, $image_url, $price, $category);
    
    echo json_encode(['success' => true, 'message' => 'Product added to database and SQL file updated']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateSQLFile($title, $description, $image_url, $price, $category) {
    $sqlFile = 'database_setup.sql';
    
    // Read the current SQL file
    $content = file_get_contents($sqlFile);
    
    // Escape single quotes for SQL
    $title = str_replace("'", "''", $title);
    $description = str_replace("'", "''", $description);
    $image_url = str_replace("'", "''", $image_url);
    $category = str_replace("'", "''", $category);
    
    // Create the new INSERT statement
    $newInsert = ",\n('$title', '$description', '$image_url', 0, 0, $price, '$category')";
    
    // Find the last VALUES entry and add the new item
    // Look for the pattern that ends with the last item before the closing semicolon
    $pattern = "/('Gaming Chair', 'Ergonomic chair designed for long gaming sessions', 'https:\/\/images\.unsplash\.com\/photo-1586953208448-b95a79798f07\?w=400', 134, 789, 349\.99, 'Furniture')\);/";
    
    if (preg_match($pattern, $content)) {
        // Replace the last item's closing with the new item
        $replacement = "$1" . $newInsert . ");";
        $content = preg_replace($pattern, $replacement, $content);
    } else {
        // Fallback: add to the end before the final semicolon
        $content = str_replace(");", $newInsert . ");", $content);
    }
    
    // Write back to the file
    file_put_contents($sqlFile, $content);
}
?>
