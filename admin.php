<?php
session_start();

define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== ROLE_SUPER_ADMIN && $_SESSION['role'] !== ROLE_ADMIN)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .welcome-message {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 1.2em;
        }

        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 0;
            list-style: none;
        }

        .admin-menu li {
            margin-bottom: 10px;
        }

        .admin-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background-color: #fff;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .admin-menu a:hover {
            background-color: #3498db;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .admin-menu i {
            margin-right: 10px;
            font-size: 1.2em;
            width: 25px;
            text-align: center;
        }

        .logout-container {
            text-align: center;
            margin-top: 30px;
        }

        .logout-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .admin-menu {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
        }

        .header-buttons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .home-btn, .logout-btn {
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            height: 36px;
            line-height: 20px;
        }

        .home-btn {
            background-color: #28a745;
        }

        .home-btn:hover {
            background-color: #218838;
        }

        .logout-btn {
            background-color: #dc3545;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .logout-container {
            display: inline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-buttons">
            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <h1>Admin Panel</h1>
        <p class="welcome-message">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        
        <ul class="admin-menu">
            <li>
                <a href="addpaper.php">
                    <i class="fas fa-plus-circle"></i>
                    Add Paper
                </a>
            </li>
            <li>
                <a href="import.php">
                    <i class="fas fa-file-import"></i>
                    Import Papers
                </a>
            </li>
            <li>
                <a href="fields.php">
                    <i class="fas fa-columns"></i>
                    Manage Fields
                </a>
            </li>
            <li>
                <a href="tags.php">
                    <i class="fas fa-tags"></i>
                    Manage Tags
                </a>
            </li>
            <li>
                <a href="delete.php">
                    <i class="fas fa-trash-alt"></i>
                    Delete Papers
                </a>
            </li>
            <li>
                <a href="modify.php">
                    <i class="fas fa-edit"></i>
                    Modify Papers
                </a>
            </li>
        </ul>
    </div>
</body>
</html>