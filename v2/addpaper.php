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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($_POST as $key => $value) {
        if ($key === 'selected_tags') {
            $data['tags'] = is_array($value) ? implode(',', array_unique(array_filter($value))) : '';
        } elseif ($key === 'new_tags') {
            $new_tags = trim($value, ',');
            if (!empty($new_tags)) {
                $new_tags_array = array_unique(array_filter(explode(',', $new_tags)));
                $data['tags'] = isset($data['tags']) ? implode(',', array_unique(array_merge(explode(',', $data['tags']), $new_tags_array))) : implode(',', $new_tags_array);
            }
        } elseif ($key !== 'add_paper') {
            $data[$key] = $value;
        }
    }

    if (!empty($data['title'])) {
        $fields = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $db_papers->prepare("INSERT INTO papers (" . implode(', ', $fields) . ") VALUES ($placeholders)");
        $bound_values = array_values($data);
        foreach ($bound_values as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();
        $_SESSION['paper_added'] = true;
        header("Location: addpaper.php");
        exit();
    }
}

$stmt = $db_papers->prepare("PRAGMA table_info(papers)");
$result = $stmt->execute();
$fields = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fields[] = $row['name'];
}

$all_tags = [];
if (in_array('tags', $fields)) {
    $stmt = $db_papers->prepare("SELECT DISTINCT tags FROM papers");
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!empty($row['tags'])) {
            $paper_tags = array_filter(explode(',', $row['tags']));
            $all_tags = array_merge($all_tags, $paper_tags);
        }
    }

    $all_tags = array_unique($all_tags);
    sort($all_tags);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Paper</title>
    <link rel="stylesheet" href="style.css">
    <style>
        #top-button {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            padding: 10px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .notification.show {
            opacity: 1;
        }
        form {
            max-width: 800px;
            margin: 0 auto;
        }
        input[type="text"], textarea {
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
        .tag-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
        }
        .tag-list li {
            margin-right: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .tag-list label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tag-list label:hover {
            background-color: #f1f1f1;
        }
        .tag-list input[type="checkbox"]:checked + label {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .tag-list input[type="checkbox"] {
            display: none;
        }
    </style>
</head>
<body>
    <div id="top-button">Top</div>
    <p><a href="admin.php">Back to Admin Panel</a></p>

    <?php if (isset($_SESSION['paper_added'])): ?>
        <div class="notification show">Paper added successfully!</div>
        <?php unset($_SESSION['paper_added']); ?>
    <?php endif; ?>

    <h1>Add Paper</h1>
    <form action="" method="post">
        <?php foreach ($fields as $field): ?>
            <?php if ($field === 'id') continue; ?>
            <label><?= ucfirst($field) ?>:</label><br>
            <?php if ($field === 'tags'): ?>
                <ul class="tag-list">
                    <?php foreach ($all_tags as $tag): ?>
                        <li>
                            <input type="checkbox" name="selected_tags[]" value="<?= $tag ?>" id="tag-<?= $tag ?>">
                            <label for="tag-<?= $tag ?>"><?= $tag ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <label>New Tags (comma-separated):</label><br>
                <input type="text" name="new_tags"><br>
            <?php elseif ($field === 'abstract'): ?>
                <textarea name="<?= $field ?>" rows="6"></textarea><br>
            <?php else: ?>
                <input type="text" name="<?= $field ?>" <?= $field === 'title' ? 'required' : '' ?>><br>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="submit" name="add_paper">Add Paper</button>
    </form>

    <script>
        document.getElementById('top-button').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        const notification = document.querySelector('.notification');
        if (notification.classList.contains('show')) {
            setTimeout(() => {
                notification.classList.remove('show');
            }, 1000);
        }
    </script>
</body>
</html>