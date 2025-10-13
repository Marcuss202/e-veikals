<?php
session_start();
include '../config/connect.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id']) || !isset($data['isAdmin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit;
}

$id = (int)$data['id'];
$isAdmin = $data['isAdmin'] ? 1 : 0;
// Prevent the admin from changing their own isAdmin flag
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot change your own admin status']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET isAdmin = ? WHERE id = ?');
    $stmt->execute([$isAdmin, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error updating user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

?>
