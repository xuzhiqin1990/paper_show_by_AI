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

// Check if the field_types table exists, if not, create it
$result = $db_papers->query("SELECT name FROM sqlite_master WHERE type='table' AND name='field_types'");
if (!$result->fetchArray()) {
    $db_papers->exec("CREATE TABLE field_types (
        field_name TEXT PRIMARY KEY,
        is_link INTEGER
    )");
}

$error_message = '';
$success_message = '';
$protected_fields = ['id', 'title', 'authors'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_field'])) {
        $field_name = trim($_POST['field_name']);
        $is_link = isset($_POST['is_link']) ? 1 : 0;

        // Validate field name
        if (empty($field_name)) {
            $error_message = "Field name cannot be empty.";
        } else {
            // Check if the column already exists
            $stmt = $db_papers->prepare("PRAGMA table_info(papers)");
            $result = $stmt->execute();
            $existing_columns = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $existing_columns[] = $row['name'];
            }

            if (!in_array($field_name, $existing_columns)) {
                $stmt = $db_papers->prepare("ALTER TABLE papers ADD COLUMN [" . str_replace("'", "''", $field_name) . "] TEXT");
                $stmt->execute();
                $stmt = $db_papers->prepare("UPDATE papers SET [" . str_replace("'", "''", $field_name) . "] = ''");
                $stmt->execute();
                $stmt = $db_papers->prepare("INSERT OR REPLACE INTO field_types (field_name, is_link) VALUES (:field_name, :is_link)");
                $stmt->bindValue(':field_name', $field_name, SQLITE3_TEXT);
                $stmt->bindValue(':is_link', $is_link, SQLITE3_INTEGER);
                $stmt->execute();
                $success_message = "Field '$field_name' added successfully.";
            } else {
                $error_message = "Column '$field_name' already exists in the papers table.";
            }
        }
    } elseif (isset($_POST['delete_field'])) {
        $field_name = $_POST['field_name'];
        $password = $_POST['password'];

        $stmt = $db_users->prepare("SELECT * FROM users WHERE role = :role");
        $stmt->bindValue(':role', ROLE_SUPER_ADMIN, SQLITE3_TEXT);
        $result = $stmt->execute();
        $super_admin = $result->fetchArray(SQLITE3_ASSOC);

        if ($super_admin && password_verify($password, $super_admin['password_hash'])) {
            if (in_array($field_name, $protected_fields)) {
                $error_message = "Cannot delete the '$field_name' field as it is a required field.";
            } else {
                $stmt = $db_papers->prepare("ALTER TABLE papers DROP COLUMN [$field_name]");
                $stmt->execute();
                $stmt = $db_papers->prepare("DELETE FROM field_types WHERE field_name = :field_name");
                $stmt->bindValue(':field_name', $field_name, SQLITE3_TEXT);
                $stmt->execute();
                $success_message = "Field '$field_name' deleted successfully.";
            }
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    }
}

$stmt = $db_papers->prepare("PRAGMA table_info(papers)");
$result = $stmt->execute();
$fields = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $field_name = $row['name'];
    if ($field_name !== 'id') {
        $fields[] = $field_name;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Fields</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Fields</h1>
        <h2>Add Field</h2>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <label for="field_name">Field Name:</label>
            <input type="text" name="field_name" id="field_name" required>
            <label>
                <input type="checkbox" name="is_link" id="is_link">
                Is Link
            </label>
            <button type="submit" name="add_field">Add Field</button>
        </form>

        <h2>Delete Field</h2>
        <form action="" method="post">
            <label for="field_name">Field Name:</label>
            <select name="field_name" id="field_name" required>
                <?php foreach ($fields as $field): ?>
                    <option value="<?= $field ?>" <?= in_array($field, $protected_fields) ? 'disabled' : '' ?>><?= ucfirst($field) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="password">Super Admin Password:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit" name="delete_field" onclick="return confirm('Are you sure you want to delete this field?')">Delete Field</button>
        </form>

        <p><a href="admin.php">Back to Admin Panel</a></p>
    </div>
</body>
</html>