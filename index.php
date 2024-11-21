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

// 检查是否存在设置显示字段的GET请求
if (!empty($show_fields)) {
    setcookie('selectedFields', json_encode($show_fields), time() + (86400 * 30), "/"); // 设置cookie，有效期30天
}

// 检查是否存在cookie
if (empty($show_fields) && isset($_COOKIE['selectedFields'])) {
    $show_fields = json_decode($_COOKIE['selectedFields'], true);
}
if (isset($_COOKIE['newOrder'])) {
    $all_fields = json_decode($_COOKIE['newOrder'], true);
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
    $export_all_fields = isset($_GET['export_all']) && $_GET['export_all'] == '1';
    exportPapersToCSV($papers, $export_all_fields ? $all_fields : $show_fields);
}

echo renderTemplate($selected_tags, $show_fields, $all_tags, $tag_counts, $all_fields, $field_types, $papers, $search_query);
?>
