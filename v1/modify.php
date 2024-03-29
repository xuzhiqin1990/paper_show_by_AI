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
    $paper_id = $_POST['paper_id'];
    
    $stmt = $db_papers->prepare("PRAGMA table_info(papers)");
    $result = $stmt->execute();
    $fields = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $fields[] = $row['name'];
    }
    
    $update_fields = [];
    $placeholders = [];
    $bound_params = [];
    
    foreach ($fields as $field) {
        if ($field === 'id') continue;
        
        if ($field === 'tags') {
            $selected_tags = isset($_POST['selected_tags']) ? $_POST['selected_tags'] : [];
            $new_tags = isset($_POST['new_tags']) ? explode(',', $_POST['new_tags']) : [];
            $tags = array_unique(array_filter(array_merge($selected_tags, $new_tags)));
            $tags_str = implode(',', $tags);
            
            if (!empty($tags_str)) {
                $update_fields[] = "tags = :tags";
                $placeholders[] = ":tags";
                $bound_params[":tags"] = $tags_str;
            } else {
                $update_fields[] = "tags = NULL";
            }
        } elseif ($field === 'pdf_url' && isset($_POST['pdf_url'])) {
            $pdf_url = filter_var($_POST['pdf_url'], FILTER_VALIDATE_URL);
            if ($pdf_url !== false) {
                $update_fields[] = "pdf_url = :pdf_url";
                $placeholders[] = ":pdf_url";
                $bound_params[":pdf_url"] = $pdf_url;
            }
        } elseif (isset($_POST[$field])) {
            $update_fields[] = "$field = :$field";
            $placeholders[] = ":$field";
            $bound_params[":$field"] = $_POST[$field];
        }
    }
    
    if (!empty($update_fields)) {
        $sql = "UPDATE papers SET " . implode(', ', $update_fields) . " WHERE id = :id";
        $stmt = $db_papers->prepare($sql);
        
        foreach ($bound_params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
        $stmt->execute();
    }

    exit('success');
}

$stmt = $db_papers->prepare("SELECT * FROM papers ORDER BY id DESC");
$result = $stmt->execute();
$papers = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $papers[] = $row;
}

$stmt = $db_papers->prepare("PRAGMA table_info(papers)");
$result = $stmt->execute();
$fields = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fields[] = $row['name'];
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

$all_tags = array_unique($all_tags);
sort($all_tags);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modify Papers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        fieldset {
            border: none;
            padding: 0;
            margin: 0;
        }
        legend {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .field-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .field-row label {
            flex: 1;
            margin-right: 10px;
            color: #666;
        }
        .field-row input[type="text"], .field-row textarea {
            flex: 2;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .tag-container {
            margin-bottom: 10px;
        }
        .tag-container span {
            display: inline-block;
            background-color: #f1f1f1;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .notification {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 16px;
            border-radius: 5px;
        }
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
        .admin-link {
            display: block;
            text-align: center;
            margin-bottom: 20px;
        }
        .admin-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div id="top-button">Top</div>
    <p class="admin-link"><a href="admin.php">Back to Admin Panel</a></p>

    <h1>Modify Papers</h1>
    <?php foreach ($papers as $paper): ?>
        <form action="" method="post" onsubmit="return confirmModification(event, this)">
            <input type="hidden" name="paper_id" value="<?= $paper['id'] ?>">
            <fieldset>
                <legend>Paper ID: <?= $paper['id'] ?></legend>
                <?php $field_count = 0; ?>
                <?php foreach ($fields as $field): ?>
                    <?php if ($field === 'id') continue; ?>
                    <?php if ($field_count % 3 === 0): ?>
                        <div class="field-row">
                    <?php endif; ?>
                    <label><?= ucfirst($field) ?>:</label>
                    <?php if ($field === 'abstract'): ?>
                        <textarea name="<?= $field ?>"><?= $paper[$field] ?? '' ?></textarea>
                    <?php elseif ($field === 'tags'): ?>
                        <div class="tag-container">
                            <?php foreach ($all_tags as $tag): ?>
                                <span>
                                    <input type="checkbox" name="selected_tags[]" value="<?= $tag ?>" id="tag-<?= $tag ?>-<?= $paper['id'] ?>" <?= isset($paper['tags']) && in_array($tag, explode(',', $paper['tags'])) ? 'checked' : '' ?>>
                                    <label for="tag-<?= $tag ?>-<?= $paper['id'] ?>"><?= $tag ?></label>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <label>New Tags (comma-separated):</label>
                        <input type="text" name="new_tags">
                    <?php else: ?>
                        <input type="text" name="<?= $field ?>" value="<?= $paper[$field] ?? '' ?>">
                    <?php endif; ?>
                    <?php $field_count++; ?>
                    <?php if ($field_count % 3 === 0 || $field_count === count($fields) - 1): ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Confirm Modification</button>
            </fieldset>
        </form>
    <?php endforeach; ?>

    <div id="notification" class="notification">
        Modification complete!
    </div>

    <script>
        function confirmModification(event, form) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                if (result === 'success') {
                    form.style.opacity = '0.5';
                    document.getElementById('notification').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('notification').style.display = 'none';
                        form.style.opacity = '1';
                    }, 1000);
                }
            });

            return false;
        }
        
        document.getElementById('top-button').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>