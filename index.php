<?php
session_start();

require_once 'database.php';
require_once 'paper_functions.php';
require_once 'template.php';

// 检查是否已有cookie存储的登录信息
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $cookie_data = json_decode($_COOKIE['remember_user'], true);
    if ($cookie_data) {
        $db_users = new SQLite3('db/users.db');
        $stmt = $db_users->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $cookie_data['username'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user && password_verify($cookie_data['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
        }
    }
}

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

$tag_counts = array();
if (!empty($papers)) {  // $papers 是查询结果的论文列表
    foreach($papers as $paper) {
        if (!empty($paper['tags'])) {
            $tags = explode(',', $paper['tags']);
            foreach($tags as $tag) {
                $tag = trim($tag);
                if(!empty($tag)) {
                    if(!isset($tag_counts[$tag])) {
                        $tag_counts[$tag] = 0;
                    }
                    $tag_counts[$tag]++;
                }
            }
        }
    }
}

if (isset($_GET['export'])) {
    $export_all = isset($_GET['export_all']) && $_GET['export_all'] == '1';
    $selected_ids = isset($_GET['selected_ids']) ? array_map('intval', explode(',', $_GET['selected_ids'])) : null;
    
    if (!$export_all && $selected_ids === null) {
        die('No papers selected for export');
    }
    
    exportPapersToCSV($papers, $show_fields, $export_all ? null : $selected_ids);
}

// 添加登录状态到模板渲染
$is_logged_in = isset($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

echo renderTemplate(
    $selected_tags, 
    $show_fields, 
    $all_tags, 
    $tag_counts, 
    $all_fields, 
    $field_types, 
    $papers, 
    $search_query,
    'year',
    'desc',
    $is_logged_in,
    $user_role,
    $username
);
?>
