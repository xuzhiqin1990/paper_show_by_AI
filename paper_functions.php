<?php

function filterPapersByTags($papers, $selected_tags) {
    return array_filter($papers, function ($paper) use ($selected_tags) {
        if (!isset($paper['tags'])) return false;
        $paper_tags = explode(',', $paper['tags']);
        return count(array_intersect($paper_tags, $selected_tags)) > 0;
    });
}

function filterPapersWithoutTags($papers) {
    return array_filter($papers, function ($paper) {
        return !isset($paper['tags']) || empty(trim($paper['tags'])) || $paper['tags'] === 'NULL' || $paper['tags'] === 'null';
    });
}

function getTagCounts($papers) {
    $tag_counts = [];
    foreach ($papers as $paper) {
        if (isset($paper['tags'])) {
            $tags = explode(',', $paper['tags']);
            foreach ($tags as $tag) {
                if (!isset($tag_counts[$tag])) {
                    $tag_counts[$tag] = 0;
                }
                $tag_counts[$tag]++;
            }
        } else {
            if (!isset($tag_counts['None'])) {
                $tag_counts['None'] = 0;
            }
            $tag_counts['None']++;
        }
    }
    return $tag_counts;
}

function exportPapersToCSV($papers, $fields) {
    $filename = 'papers_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // 添加BOM以确保Excel正确识别UTF-8编码
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, $fields);

    foreach ($papers as $paper) {
        $row = [];
        foreach ($fields as $field) {
            $row[] = $paper[$field] ?? '';
        }
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}
/* function searchPapers($papers, $query) {
    $query = strtolower($query);
    return array_filter($papers, function($paper) use ($query) {
        return strpos(strtolower($paper['title']), $query) !== false ||
               strpos(strtolower($paper['authors']), $query) !== false ||
               strpos(strtolower($paper['abstract']), $query) !== false;
    });
} */

function searchPapers($papers, $query) {
    $query = strtolower($query);

    // 提取所有字段
    $fields = [];
    if (!empty($papers)) {
        $fields = array_keys($papers[0]);
    }

    return array_filter($papers, function($paper) use ($query, $fields) {
        foreach ($fields as $field) {
            if (isset($paper[$field])) {
                $field_value = is_array($paper[$field]) ? implode(' ', $paper[$field]) : $paper[$field];
                if (strpos(strtolower($field_value), $query) !== false) {
                    return true;
                }
            }
        }
        return false;
    });
}
/* function searchPapers($papers, $query) {
    $query = strtolower($query);
    return array_filter($papers, function($paper) use ($query) {
        // 检查标题
        if (strpos(strtolower($paper['title']), $query) !== false) {
            return true;
        }
        
        // 检查作者
        if (strpos(strtolower($paper['authors']), $query) !== false) {
            return true;
        }
        
        // 检查摘要
        if (strpos(strtolower($paper['abstract']), $query) !== false) {
            return true;
        }
        
        // 检查年份
        if (strpos(strtolower($paper['year']), $query) !== false) {
            return true;
        }
        
        // 检查标签
        if (isset($paper['tags'])) {
            $tags = is_array($paper['tags']) ? $paper['tags'] : explode(',', $paper['tags']);
            foreach ($tags as $tag) {
                if (strpos(strtolower(trim($tag)), $query) !== false) {
                    return true;
                }
            }
        }
        
        // 检查期刊
        if (isset($paper['journal']) && strpos(strtolower($paper['journal']), $query) !== false) {
            return true;
        }
        
        return false;
    });
} */
?>