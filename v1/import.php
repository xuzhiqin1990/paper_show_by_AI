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

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_papers'])) {
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, 'r');

        // Check if the uploaded file is a valid CSV
        if ($handle === false || $_FILES['file']['type'] !== 'text/csv') {
            $error_message = "Invalid CSV file. Please upload a valid CSV file.";
        } else {
            $fields = fgetcsv($handle, 0, ',');

            $stmt = $db_papers->prepare("PRAGMA table_info(papers)");
            $result = $stmt->execute();
            $db_fields = [];

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $db_fields[] = $row['name'];
            }

            $existing_titles = [];
            $stmt = $db_papers->prepare("SELECT title FROM papers");
            $result = $stmt->execute();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $existing_titles[] = $row['title'];
            }

            $duplicate_titles = [];
            $papers_to_import = [];

            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $data = array_combine($fields, $data);
                if (isset($data['title']) && in_array($data['title'], $existing_titles)) {
                    $duplicate_titles[] = $data['title'];
                }
                $paper_data = [];
                foreach ($db_fields as $field) {
                    if ($field === 'id') continue;
                    if ($field === 'tags' && isset($data[$field])) {
                        $tags = explode(',', $data[$field]);
                        $paper_data[$field] = implode(',', array_unique(array_filter($tags)));
                    } else {
                        $paper_data[$field] = isset($data[$field]) ? $data[$field] : '';
                    }
                }
                $papers_to_import[] = $paper_data;
            }
            fclose($handle);

            if (!empty($duplicate_titles)) {
                $_SESSION['papers_to_import'] = $papers_to_import;
                $_SESSION['duplicate_titles'] = $duplicate_titles;
                header("Location: confirm_import.php");
                exit();
            } else {
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
                $success_message = "Papers imported successfully.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Papers</title>
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
        input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        <h1>Import Papers</h1>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="file">Select CSV File:</label>
            <input type="file" name="file" id="file" accept=".csv" required>
            <button type="submit" name="import_papers">Import</button>
        </form>
        <p><a href="admin.php">Back to Admin Panel</a></p>
    </div>
</body>
</html>