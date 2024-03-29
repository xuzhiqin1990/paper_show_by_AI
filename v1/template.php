<?php
function renderTemplate($selected_tags, $default_show_fields, $all_tags, $tag_counts, $all_fields, $field_types, $papers) {
    $paper_count = count($papers);
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Paper List</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
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
    </style>
</head>
<body>
    <div id="top-button">Top</div>

    <h1>Zhi-Qin John Xu's Paper List (<?= $paper_count ?> papers)</h1>

    <a href="login.php" class="login-button">Login</a>

    <div class="google-scholar-link">
        <a href="https://scholar.google.com/citations?user=EjLvG5cAAAAJ&hl=zh-CN" target="_blank">Google Scholar</a>
    </div>

    <div class="export-container">
        <a href="?export&<?= http_build_query(['show_fields' => implode(',', $default_show_fields), 'tags' => $selected_tags]) ?>">Export to Excel</a>
    </div>

    <h2>Filter by Tag</h2>
    <ul class="tag-list">
        <li>
            <input type="checkbox" id="tag-All" name="tags[]" value="All" <?= in_array('All', $selected_tags) ? 'checked' : '' ?> onchange="filterPapers()">
            <label for="tag-All">All</label>
        </li>
        <li>
            <input type="checkbox" id="tag-None" name="tags[]" value="None" <?= in_array('None', $selected_tags) ? 'checked' : '' ?> onchange="filterPapers()">
            <label for="tag-None">None (<?= $tag_counts['None'] ?? 0 ?>)</label>
        </li>
        <?php foreach ($all_tags as $tag): ?>
            <?php if ($tag === 'None' || $tag === 'All') continue; ?>
            <li>
                <input type="checkbox" id="tag-<?= $tag ?>" name="tags[]" value="<?= $tag ?>" <?= in_array($tag, $selected_tags) ? 'checked' : '' ?> onchange="filterPapers()">
                <label for="tag-<?= $tag ?>"><?= $tag ?> (<?= $tag_counts[$tag] ?? 0 ?>)</label>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Show/Hide Fields</h2>
    <ul class="field-list" id="field-list">
        <?php foreach ($all_fields as $field): ?>
            <li>
                <input type="checkbox" id="field-<?= $field ?>" name="show_fields[]" value="<?= $field ?>" <?= in_array($field, $default_show_fields) ? 'checked' : '' ?> onchange="filterPapers()">
                <label for="field-<?= $field ?>"><?= ucfirst($field) ?></label>
            </li>
        <?php endforeach; ?>
    </ul>

    <table>
        <thead>
            <tr id="table-header">
                <?php foreach ($default_show_fields as $field): ?>
                    <th><?= ucfirst(str_replace('_', ' ', $field)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="paper-list">
            <?php foreach ($papers as $paper): ?>
                <tr>
                    <?php foreach ($default_show_fields as $field): ?>
                        <?php if (isset($field_types[$field]) && $field_types[$field]): ?>
                            <td><?= !empty($paper[$field]) ? '<a href="' . $paper[$field] . '" target="_blank">' . $paper[$field] . '</a>' : '' ?></td>
                        <?php else: ?>
                            <td><?= $paper[$field] ?? '' ?></td>
                        <?php endif; ?> 
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
                    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="script.js"></script>
    <script>
        let selectedTags = <?= json_encode($selected_tags) ?>;
        let allPapers = <?= json_encode($papers) ?>;
        let allFields = <?= json_encode($all_fields) ?>;
        let showFields = <?= json_encode($default_show_fields) ?>;
        let fieldTypes = <?= json_encode($field_types) ?>;

        document.getElementById('top-button').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            loadFieldOrder();
            filterPapers();
        });
    </script>
</body>
</html>
<?php
    return ob_get_clean();
}
?>