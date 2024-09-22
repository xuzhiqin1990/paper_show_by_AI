<?php
session_start();

require_once 'database.php';
require_once 'paper_functions.php';
require_once 'template.php';

$selected_tags = isset($_GET['tags']) ? (is_array($_GET['tags']) ? $_GET['tags'] : explode(',', $_GET['tags'])) : [];
$show_fields = isset($_GET['show_fields']) ? (is_array($_GET['show_fields']) ? $_GET['show_fields'] : explode(',', $_GET['show_fields'])) : [];
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$all_papers = getAllPapers();
$all_tags = getAllTags();
$field_types = getFieldTypes();
$all_fields = getAllFields();

$default_show_fields = ['title', 'authors', 'journal', 'year', 'tags'];

if (empty($show_fields) && isset($_COOKIE['selectedFields'])) {
    $show_fields = json_decode($_COOKIE['selectedFields'], true);
}

$show_fields = array_intersect($all_fields, $show_fields ?: $default_show_fields);

$papers = $all_papers;
if (!empty($search_query)) {
    $papers = searchPapers($papers, $search_query);
}
if (in_array('None', $selected_tags) && count($selected_tags) === 1) {
    $papers = filterPapersWithoutTags($papers);
} elseif (!empty($selected_tags) && !in_array('All', $selected_tags)) {
    $papers = filterPapersByTags($papers, $selected_tags);
} elseif (in_array('All', $selected_tags)) {
    $papers = $all_papers;
}

$tag_counts = getTagCounts($all_papers);

if (isset($_GET['export'])) {
    exportPapersToCSV($papers, $show_fields);
}

echo renderTemplate($selected_tags, $show_fields, $all_tags, $tag_counts, $all_fields, $field_types, $papers, $search_query);
?>