<?php
// search.php

$db = new SQLite3('db/papers.db');

$search = $_GET['q'];

$stmt = $db->prepare("SELECT * FROM papers WHERE title LIKE '%' || :search || '%'");
$stmt->bindValue(':search', $search, SQLITE3_TEXT);
$result = $stmt->execute();
$papers = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $papers[] = $row;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
</head>
<body>
    <h1>Search Results for "<?= $search ?>"</h1>
    
    <ul>
        <?php foreach ($papers as $paper): ?>
            <li>
                <a href="paper_detail.php?id=<?= $paper['id'] ?>"><?= $paper['title'] ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <p><a href="paper_list.php">Back to Paper List</a></p>
</body>
</html>