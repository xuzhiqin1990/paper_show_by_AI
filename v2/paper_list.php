<?php
// paper_list.php

$db = new SQLite3('db/papers.db');

$tag = isset($_GET['tag']) ? $_GET['tag'] : '';

if ($tag) {
    $stmt = $db->prepare("SELECT * FROM papers WHERE tags LIKE '%' || :tag || '%'");
    $stmt->bindValue(':tag', $tag, SQLITE3_TEXT);
} else {
    $stmt = $db->prepare("SELECT * FROM papers");
}

$result = $stmt->execute();
$papers = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $papers[] = $row;
}

$stmt = $db->prepare("SELECT DISTINCT tags FROM papers");
$result = $stmt->execute();
$tags = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tags = array_merge($tags, explode(',', $row['tags']));
}

$tags = array_unique($tags);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Paper List</title>
</head>
<body>
    <h1>Paper List</h1>
    
    <form action="search.php" method="get">
        <input type="text" name="q" placeholder="Search...">
        <button type="submit">Search</button>
    </form>
    
    <ul>
        <?php foreach ($tags as $tag): ?>
            <li><a href="?tag=<?= urlencode($tag) ?>"><?= $tag ?></a></li>
        <?php endforeach; ?>
    </ul>
    
    <ul>
        <?php foreach ($papers as $paper): ?>
            <li>
                <a href="paper_detail.php?id=<?= $paper['id'] ?>"><?= $paper['title'] ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>