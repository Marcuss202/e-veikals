<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'e-veikalsDB';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            header("Location: loginRegister.html?error=All fields are required");
            exit();
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->rowCount() > 0) {
                header("Location: loginRegister.html?error=User already exists");
                exit();
            }
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$username, $email, $hashedPassword]);
            
            if ($result) {
                // Check if the user was actually inserted
                $userId = $pdo->lastInsertId();
                if ($userId > 0) {
                    header("Location: loginRegister.html?success=Registration successful! Please login.");
                    exit();
                } else {
                    header("Location: loginRegister.html?error=User creation failed - no ID returned");
                    exit();
                }
            } else {
                header("Location: loginRegister.html?error=Registration failed - execute returned false");
                exit();
            }
        } catch(PDOException $e) {
            header("Location: loginRegister.html?error=Database error: " . urlencode($e->getMessage()));
            exit();
        }
    }

    if ($_POST['action'] == 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                header("Location: index.html");
                exit();
            } else {
                header("Location: loginRegister.html?error=Invalid email or password");
                exit();
            }
        } catch(PDOException $e) {
            header("Location: loginRegister.html?error=Database error: " . urlencode($e->getMessage()));
            exit();
        }
    }
}
?>