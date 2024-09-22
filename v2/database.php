<?php
$db_papers = new SQLite3('db/papers.db');

function getAllPapers() {
    global $db_papers;
    $stmt = $db_papers->prepare("SELECT * FROM papers ORDER BY id DESC");
    $result = $stmt->execute();
    $papers = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $papers[] = $row;
    }
    return $papers;
}

 function getAllTags() {
    global $db_papers;
    $stmt = $db_papers->prepare("SELECT DISTINCT tags FROM papers");
    $result = $stmt->execute();
    $tags = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!empty($row['tags'])) {
            $paper_tags = explode(',', $row['tags']);
            $tags = array_merge($tags, $paper_tags);
        }
    }
    $tags = array_unique($tags);
    sort($tags);
    return $tags;
} 

function getFieldTypes() {
    global $db_papers;
    $stmt = $db_papers->prepare("SELECT field_name, is_link FROM field_types");
    $result = $stmt->execute();
    $field_types = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $field_types[$row['field_name']] = $row['is_link'];
    }
    return $field_types;
}

function getAllFields() {
    global $db_papers;
    $stmt = $db_papers->prepare("PRAGMA table_info(papers)");
    $result = $stmt->execute();
    $fields = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $fields[] = $row['name'];
    }
    return $fields;
}
?>