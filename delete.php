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
    if (isset($_POST['delete_papers'])) {
        $paper_ids = $_POST['paper_ids'];
        $password = $_POST['password'];

        $stmt = $db_users->prepare("SELECT * FROM users WHERE role = :role");
        $stmt->bindValue(':role', ROLE_SUPER_ADMIN, SQLITE3_TEXT);
        $result = $stmt->execute();
        $super_admin = $result->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        if ($super_admin && password_verify($password, $super_admin['password_hash'])) {
            $deleted_papers = [];
            foreach ($paper_ids as $paper_id) {
                $stmt = $db_papers->prepare("SELECT title FROM papers WHERE id = :id");
                $stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $paper = $result->fetchArray(SQLITE3_ASSOC);
                $stmt->close();

                if ($paper) {
                    $deleted_papers[] = $paper['title'];
                }

                $stmt = $db_papers->prepare("DELETE FROM papers WHERE id = :id");
                $stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                
                if ($result) {
                    $stmt->close();
                } else {
                    $error_message = "Failed to delete paper with ID: " . $paper_id;
                    break;
                }
            }

            if (!isset($error_message)) {
                $_SESSION['deleted_papers'] = $deleted_papers;
            }
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    }
}

$stmt = $db_papers->prepare("SELECT * FROM papers");
$result = $stmt->execute();
$papers = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $papers[] = $row;
}
$stmt->close();

$stmt = $db_papers->prepare("PRAGMA table_info(papers)");
$result = $stmt->execute();
$fields = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fields[] = $row['name'];
}
$stmt->close();

$db_papers->close();
$db_users->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Papers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #45a049;
        }
        .success-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .success-popup.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <h1>Delete Papers</h1>
    <?php if (isset($error_message)): ?>
        <p class="error"><?= $error_message ?></p>
    <?php endif; ?>
    <form action="" method="post" id="delete-form">
        <table>
            <tr>
                <th></th>
                <?php foreach ($fields as $field): ?>
                    <th><?= ucfirst($field) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($papers as $paper): ?>
                <tr>
                    <td><input type="checkbox" name="paper_ids[]" value="<?= $paper['id'] ?>"></td>
                    <?php foreach ($fields as $field): ?>
                        <td><?= $paper[$field] ?? '' ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <label for="password">Super Admin Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit" name="delete_papers" class="button">Delete Selected</button>
    </form>
    <p><a href="admin.php" class="button">Back to Admin Panel</a></p>

    <?php if (isset($_SESSION['deleted_papers'])): ?>
        <div id="success-popup" class="success-popup">
            <p>Successfully deleted the following papers:</p>
            <ul>
                <?php foreach ($_SESSION['deleted_papers'] as $title): ?>
                    <li><?= htmlspecialchars($title) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['deleted_papers']); ?>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successPopup = document.getElementById('success-popup');
            if (successPopup) {
                successPopup.classList.add('show');
                setTimeout(function() {
                    successPopup.classList.remove('show');
                }, 2000);
            }
        });
    </script>
</body>
</html>