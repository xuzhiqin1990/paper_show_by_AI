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
?>