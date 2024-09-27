<?php
session_start();

define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== ROLE_SUPER_ADMIN && $_SESSION['role'] !== ROLE_ADMIN)) {
    header("Location: index.php");
    exit();
}

$db_papers = new SQLite3('db/papers.db');
$db_users = new SQLite3('db/users.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_tag'])) {
        $tag = $_POST['tag'];
        $password = $_POST['password'];

        $stmt = $db_users->prepare("SELECT * FROM users WHERE role = :role");
        $stmt->bindValue(':role', ROLE_SUPER_ADMIN, SQLITE3_TEXT);
        $result = $stmt->execute();
        $super_admin = $result->fetchArray(SQLITE3_ASSOC);

        if ($super_admin && password_verify($password, $super_admin['password_hash'])) {
            $stmt = $db_papers->prepare("SELECT * FROM papers");
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $paper_id = $row['id'];
                $tags = $row['tags'] ? explode(',', $row['tags']) : [];
                $updated_tags = array_diff($tags, [$tag]);
                $updated_tags_str = implode(',', $updated_tags);
                $updated_tags_str = trim($updated_tags_str, ',');

                if (!empty($updated_tags_str)) {
                    $stmt = $db_papers->prepare("UPDATE papers SET tags = :tags WHERE id = :id");
                    $stmt->bindValue(':tags', $updated_tags_str, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
                    $stmt->execute();
                } else {
                    $stmt = $db_papers->prepare("UPDATE papers SET tags = NULL WHERE id = :id");
                    $stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
                    $stmt->execute();
                }
            }

            header("Location: tags.php");
            exit();
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    }
}

$stmt = $db_papers->prepare("SELECT DISTINCT tags FROM papers");
$result = $stmt->execute();
$all_tags = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($row['tags']) {
        $tags = explode(',', $row['tags']);
        $all_tags = array_merge($all_tags, $tags);
    }
}

$all_tags = array_filter($all_tags);
$all_tags = array_unique($all_tags);
sort($all_tags);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Tags</title>
</head>
<body>
    <h1>Manage Tags</h1>
    <h2>Delete Tag</h2>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?= $error_message ?></p>
    <?php endif; ?>
    <form action="" method="post">
        <label>Tag:</label><br>
        <select name="tag" required>
            <?php foreach ($all_tags as $tag): ?>
                <option value="<?= $tag ?>"><?= $tag ?></option>
            <?php endforeach; ?>
        </select><br>
        <label>Super Admin Password:</label><br>
        <input type="password" name="password" required><br>
        <button type="submit" name="delete_tag" onclick="return confirm('Are you sure you want to delete this tag?')">Delete Tag</button>
    </form>
    <p><a href="admin.php">Back to Admin Panel</a></p>
</body>
</html>