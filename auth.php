<?php
session_start();

// Database configuration - use same settings as connect.php
$host = '127.0.0.1';
$port = '3307';
$dbname = 'e-veikalsDB';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }
    if (strlen($username) > 20) {
        $errors[] = 'Username must be no more than 20 characters long';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    if (preg_match('/\s/', $username)) {
        $errors[] = 'Username cannot contain spaces';
    }
    
    return $errors;
}

function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    if (preg_match('/\s/', $password)) {
        $errors[] = 'Password cannot contain spaces';
    }
    if (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+$/', $password)) {
        $errors[] = 'Password contains invalid characters';
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        $errors = [];
        
        // Basic validation
        if (empty($username)) {
            $errors[] = 'Username is required';
        }
        if (empty($email)) {
            $errors[] = 'Email is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        if (empty($confirmPassword)) {
            $errors[] = 'Please confirm your password';
        }
        
        // Username validation
        if (!empty($username)) {
            $usernameErrors = validateUsername($username);
            $errors = array_merge($errors, $usernameErrors);
        }
        
        // Email validation
        if (!empty($email) && !validateEmail($email)) {
            $errors[] = 'Please enter a valid email address';
        }
        
        // Password validation
        if (!empty($password)) {
            $passwordErrors = validatePassword($password);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        // Confirm password validation
        if (!empty($password) && !empty($confirmPassword) && $password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            $errorMessage = implode('. ', $errors);
            header("Location: loginRegister.html?error=" . urlencode($errorMessage));
            exit();
        }
        
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                if ($existingUser['email'] === $email) {
                    header("Location: loginRegister.html?error=" . urlencode("Email address is already registered"));
                } else {
                    header("Location: loginRegister.html?error=" . urlencode("Username is already taken"));
                }
                exit();
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$username, $email, $hashedPassword]);
            
            if ($result) {
                $userId = $pdo->lastInsertId();
                if ($userId > 0) {
                    header("Location: loginRegister.html?success=" . urlencode("Registration successful! Please login with your credentials."));
                    exit();
                } else {
                    header("Location: loginRegister.html?error=" . urlencode("Registration failed. Please try again."));
                    exit();
                }
            } else {
                header("Location: loginRegister.html?error=" . urlencode("Registration failed. Please try again."));
                exit();
            }
        } catch(PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            
            // Check for specific database errors
            if ($e->getCode() == 23000) {
                header("Location: loginRegister.html?error=" . urlencode("Email or username already exists"));
            } else {
                header("Location: loginRegister.html?error=" . urlencode("Database error occurred. Please try again."));
            }
            exit();
        }
    }

    if ($_POST['action'] == 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        $errors = [];
        
        // Basic validation
        if (empty($email)) {
            $errors[] = 'Email is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        // Email format validation
        if (!empty($email) && !validateEmail($email)) {
            $errors[] = 'Please enter a valid email address';
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            $errorMessage = implode('. ', $errors);
            header("Location: loginRegister.html?error=" . urlencode($errorMessage));
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['isAdmin'] = $user['isAdmin'];
                
                header("Location: index.html");
                exit();
            } else {
                header("Location: loginRegister.html?error=" . urlencode("Invalid email or password. Please check your credentials and try again."));
                exit();
            }
        } catch(PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            header("Location: loginRegister.html?error=" . urlencode("Database error occurred. Please try again."));
            exit();
        }
    }
}
?>