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
</head>
<body>
    <h1>Admin Panel</h1>
    <p>Welcome, <?= $_SESSION['username'] ?>!</p>
    <ul>
        <li><a href="addpaper.php">Add Paper</a></li>
        <li><a href="import.php">Import Papers</a></li>
        <li><a href="fields.php">Manage Fields</a></li>
        <li><a href="tags.php">Manage Tags</a></li>
        <li><a href="delete.php">Delete Papers</a></li>
        <li><a href="modify.php">Modify Papers</a></li>
    </ul>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>