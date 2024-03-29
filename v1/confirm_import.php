<?php
session_start();

define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== ROLE_SUPER_ADMIN && $_SESSION['role'] !== ROLE_ADMIN)) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['papers_to_import']) || !isset($_SESSION['duplicate_titles'])) {
    header("Location: import.php");
    exit();
}

$papers_to_import = $_SESSION['papers_to_import'];
$duplicate_titles = $_SESSION['duplicate_titles'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $db_papers = new SQLite3('db/papers.db');

    foreach ($papers_to_import as $paper) {
        $fields = array_keys($paper);
        $placeholders = implode(', ', array_fill(0, count($paper), '?'));
        $stmt = $db_papers->prepare("INSERT INTO papers (" . implode(', ', $fields) . ") VALUES ($placeholders)");
        $bound_values = array_values($paper);
        foreach ($bound_values as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();
    }

    unset($_SESSION['papers_to_import']);
    unset($_SESSION['duplicate_titles']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Import</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        button[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirm Import</h1>
        <?php if (isset($_POST['confirm_import'])): ?>
            <div class="message success">Papers imported successfully!</div>
            <script>
                setTimeout(function() {
                    window.location.href = 'admin.php';
                }, 1000);
            </script>
        <?php else: ?>
            <form action="" method="post">
                <p>The following papers have duplicate titles:</p>
                <ul>
                    <?php foreach ($duplicate_titles as $title): ?>
                        <li><?= $title ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>These papers will be imported as new entries. Do you want to proceed?</p>
                <button type="submit" name="confirm_import">Confirm Import</button>
            </form>
            <p><a href="import.php">Cancel</a></p>
        <?php endif; ?>
    </div>
</body>
</html>