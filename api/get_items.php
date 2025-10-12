<?php
session_start();
include '../config/connect.php';

try {
    // Check if requesting a single item
    if (isset($_GET['id'])) {
        $itemId = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Product not found']);
            exit;
        }
        
        // Check if current user has liked this item
        if (isset($_SESSION['user_id'])) {
            try {
                // Create user_likes table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_likes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    item_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_item (user_id, item_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_item_id (item_id)
                )");
                
                $stmt = $pdo->prepare("SELECT id FROM user_likes WHERE user_id = ? AND item_id = ?");
                $stmt->execute([$_SESSION['user_id'], $itemId]);
                $item['userLiked'] = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                $item['userLiked'] = false;
                error_log('Failed to check user like status: ' . $e->getMessage());
            }
        } else {
            $item['userLiked'] = false;
        }
        
        header('Content-Type: application/json');
        echo json_encode($item);
        exit;
    }
    
    // Check if requesting by category with exclusions and limit
    if (isset($_GET['category'])) {
        $category = $_GET['category'];
        $excludeId = isset($_GET['exclude']) ? (int)$_GET['exclude'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $stmt = $pdo->prepare("SELECT * FROM items WHERE category = ? AND id != ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$category, $excludeId, $limit]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add user liked status for each item
        if (isset($_SESSION['user_id'])) {
            try {
                // Create user_likes table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_likes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    item_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_item (user_id, item_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_item_id (item_id)
                )");
            } catch (Exception $e) {
                error_log('Failed to create user_likes table: ' . $e->getMessage());
            }
            
            foreach ($items as &$item) {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM user_likes WHERE user_id = ? AND item_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $item['id']]);
                    $item['userLiked'] = $stmt->rowCount() > 0;
                } catch (Exception $e) {
                    $item['userLiked'] = false;
                    error_log('Failed to check user like status: ' . $e->getMessage());
                }
            }
        } else {
            foreach ($items as &$item) {
                $item['userLiked'] = false;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }
    
    // Default: Fetch all items
    $stmt = $pdo->prepare("SELECT * FROM items ORDER BY created_at DESC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add user liked status for each item
    if (isset($_SESSION['user_id'])) {
        // Create user_likes table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                item_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_item (user_id, item_id),
                INDEX idx_user_id (user_id),
                INDEX idx_item_id (item_id)
            )");
        } catch (Exception $e) {
            // Table creation failed, continue without user liked status
            error_log('Failed to create user_likes table: ' . $e->getMessage());
        }
        
        foreach ($items as &$item) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM user_likes WHERE user_id = ? AND item_id = ?");
                $stmt->execute([$_SESSION['user_id'], $item['id']]);
                $item['userLiked'] = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                // If query fails, default to not liked
                $item['userLiked'] = false;
                error_log('Failed to check user like status: ' . $e->getMessage());
            }
        }
    } else {
        foreach ($items as &$item) {
            $item['userLiked'] = false;
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($items);
} catch(PDOException $e) {
    error_log('Database error in get_items.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log('General error in get_items.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
