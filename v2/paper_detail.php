<?php
// paper_detail.php

$db = new SQLite3('db/papers.db');

$paper_id = $_GET['id'];

$stmt = $db->prepare("SELECT * FROM papers WHERE id = :id");
$stmt->bindValue(':id', $paper_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$paper = $result->fetchArray(SQLITE3_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $paper['title'] ?></title>
</head>
<body>
    <h1><?= $paper['title'] ?></h1>
    <p>Authors: <?= $paper['authors'] ?></p>
    <p>Abstract: <?= $paper['abstract'] ?></p>
    <p>Tags: <?= $paper['tags'] ?></p>
    <p><a href="papers/<?= $paper['pdf_url'] ?>" target="_blank">Download PDF</a></p>
</body>
</html>