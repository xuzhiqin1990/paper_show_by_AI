<?php
// login.php

session_start();

define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

$db = new SQLite3('db/users.db');

// 检查是否已有cookie存储的登录信息
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $cookie_data = json_decode($_COOKIE['remember_user'], true);
    if ($cookie_data) {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $cookie_data['username'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user && password_verify($cookie_data['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: admin.php");
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // 如果选择了"记住我"，设置cookie
        if ($remember) {
            $cookie_data = [
                'username' => $username,
                'password' => $password
            ];
            setcookie(
                'remember_user',
                json_encode($cookie_data),
                time() + (30 * 24 * 60 * 60), // 30天过期
                '/',
                '',
                true, // 只通过HTTPS发送
                true  // 仅HTTP访问
            );
        }

        header("Location: admin.php");
        exit();
    } else {
        $error_message = "Invalid username or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .remember-me {
            margin: 15px 0;
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }

        .login-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .login-btn:hover {
            background-color: #0056b3;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            color: #6c757d;
            text-decoration: none;
        }

        .back-link a:hover {
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="remember-me">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    Remember me for 30 days
                </label>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="back-link">
            <a href="index.php">Back to Paper List</a>
        </div>
    </div>
</body>
</html>