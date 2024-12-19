<?php
function renderTemplate($selected_tags, $default_show_fields, $all_tags, $tag_counts, $all_fields, $field_types, $papers, $search_query, $sort_field = 'year', $sort_order = 'desc', $is_logged_in = false, $user_role = '', $username = '') {
    // 获取排序参数
    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : $sort_field;
    $sort_order = isset($_GET['order']) ? $_GET['order'] : $sort_order;

    // 根据排序参数对论文进行排序
    usort($papers, function($a, $b) use ($sort_field, $sort_order) {
        if ($a[$sort_field] == $b[$sort_field]) {
            return 0;
        }
        if ($sort_order === 'asc') {
            return $a[$sort_field] < $b[$sort_field] ? -1 : 1;
        } else {
            return $a[$sort_field] > $b[$sort_field] ? -1 : 1;
        }
    });

    if (isset($_GET['export'])) {
        exportToExcel($papers, $default_show_fields);
        exit;
    }

    $paper_count = count($papers);
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Paper List</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
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
        .sortable {
            cursor: pointer;
        }
        .sortable:after {
            content: ' \25B2'; /* Default to up arrow */
        }
        .sortable.desc:after {
            content: ' \25BC'; /* Down arrow */
        }
        .container {
            max-width: auto;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .header-row h1 {
            margin: 0;
            padding: 0;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-text {
            color: #6c757d;
        }

        .admin-btn {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .admin-btn:hover {
            background-color: #0056b3;
        }

        .logout-btn {
            padding: 8px 16px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .login-btn {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #218838;
        }

        /* 响应式设计 */
        @media (max-width: 1650px) {
            .container {
                max-width: 95%;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
                width: 100%;
            }
            
            .header-row {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .user-section {
                width: 100%;
                justify-content: center;
            }
        }

        .personal-page-link {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            align-items: center;
        }
    </style>
    <script>
        function filterPapers() {
            const selectedTags = Array.from(document.querySelectorAll('.tag-list input[type="checkbox"]:checked')).map(checkbox => checkbox.value);
            const selectedFields = Array.from(document.querySelectorAll('.field-list input[type="checkbox"]:checked')).map(checkbox => checkbox.value);

            const searchParams = new URLSearchParams();
            selectedTags.forEach(tag => searchParams.append('tags[]', tag));
            selectedFields.forEach(field => searchParams.append('show_fields[]', field));

            const searchQuery = document.getElementById('search-box').value;
            if (searchQuery) {
                searchParams.append('search', searchQuery);
            }

            window.location.search = searchParams.toString();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = document.querySelectorAll('.tag-list input[type="checkbox"], .field-list input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', filterPapers);
            });
            const searchBox = document.getElementById('search-box');
            //searchBox.addEventListener('input', filterPapers);

            searchBox.addEventListener('input', () => {
                const selectedTags = Array.from(document.querySelectorAll('.tag-list input[type="checkbox"]:checked')).map(checkbox => checkbox.value);
                const selectedFields = Array.from(document.querySelectorAll('.field-list input[type="checkbox"]:checked')).map(checkbox => checkbox.value);

                const searchParams = new URLSearchParams();
                selectedTags.forEach(tag => searchParams.append('tags[]', tag));
                selectedFields.forEach(field => searchParams.append('show_fields[]', field));

                const searchQuery = searchBox.value;
                if (searchQuery) {
                    searchParams.append('search', searchQuery);
                }

                const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
                history.pushState(null, '', newUrl);

                // Fetch the updated paper list without reloading the page
                fetch(newUrl)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newPaperList = doc.getElementById('paper-list');
                        document.getElementById('paper-list').innerHTML = newPaperList.innerHTML;
                    })
                    .catch(error => console.error('Error fetching paper list:', error));
            });

            const sortDescButton = document.getElementById('sort-desc');
            const sortAscButton = document.getElementById('sort-asc');

            sortDescButton.addEventListener('click', () => sortPapers('year', 'desc'));
            sortAscButton.addEventListener('click', () => sortPapers('year', 'asc'));
        });

        function sortPapers(field, order) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('sort', field);
            searchParams.set('order', order);

            const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
            window.location.href = newUrl;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 获取 top 按钮元素
            const topButton = document.getElementById('top-button');

            // 为按钮添加点击事件监听器
            topButton.addEventListener('click', function() {
                // 平滑滚动到页面顶部
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // 定义一个函数来控制按钮的显示和隐藏
            function toggleTopButton() {
                if (window.pageYOffset > 100) {
                    topButton.style.display = 'block';
                } else {
                    topButton.style.display = 'none';
                }
            }

            // 页面加载时立即调用一次，确保初始状态正确
            toggleTopButton();

            // 监听滚动事件，控制按钮的显示和隐藏
            window.addEventListener('scroll', toggleTopButton);
        });

        $(document).ready(function() {
            $("#field-list").sortable({
                update: function(event, ui) {
                    updatePaperList();
                }
            });

            function updatePaperList() {
                var showFields = Object.values(<?php echo json_encode($default_show_fields); ?>);
                var newOrder = $("#field-list").sortable("toArray", {attribute: "data-field"});
                var filteredNewOrder = newOrder.filter(function(field) {
                    return showFields.indexOf(field) !== -1;
                });
                // 设置cookie，有效期30天
                var newOrderString = JSON.stringify(newOrder);
                var date = new Date();
                date.setTime(date.getTime() + (30*24*60*60*1000));
                document.cookie = "newOrder=" + encodeURIComponent(newOrderString) + "; expires=" + date.toUTCString() + "; path=/";

                var $table = $("table");
                var $headers = $table.find("thead th").not(":first"); // 排除 ID 列
                var $rows = $table.find("tbody tr");

                // 重新排序表头
                $headers.each(function(index) {
                    var $header = $(this);
                    var newIndex = filteredNewOrder.indexOf($header.data("field"));
                    if (newIndex !== -1) {
                        $header.detach().insertAfter($table.find("thead th").eq(newIndex));
                    }
                });

                // 重新排序每一行的单元格
                $rows.each(function() {
                    var $row = $(this);
                    var $cells = $row.find("td").not(":first"); // 排除 ID 列
                    filteredNewOrder.forEach(function(field, index) {
                        var $cell = $cells.filter(function() {
                            return $(this).data("field") === field;
                        });
                        if ($cell.length) {
                            $cell.detach().insertAfter($row.find("td").eq(index));
                        }
                    });
                });
            }
        });

        function exportPapers() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
            `;

            modal.innerHTML = `
                <h3>Export Options</h3>
                <p>Choose what to export:</p>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button id="export-all">Export All</button>
                    <button id="export-selected">Export Selected</button>
                    <button id="cancel">Cancel</button>
                </div>
            `;

            // 导出所有数据
            const exportAllButton = modal.querySelector('#export-all');
            exportAllButton.onclick = function() {
                const exportUrl = new URL(window.location.href);
                exportUrl.searchParams.set('export', '1');
                exportUrl.searchParams.set('export_all', '1');
                window.location.href = exportUrl.toString();
                document.body.removeChild(modal);
            };

            // 导出选中的数据
            const exportSelectedButton = modal.querySelector('#export-selected');
            exportSelectedButton.onclick = function() {
                const selectedIds = Array.from(document.querySelectorAll('.paper-select:checked'))
                    .map(checkbox => checkbox.dataset.id);
                
                if (selectedIds.length === 0) {
                    alert('Please select at least one paper to export.');
                    return;
                }

                const exportUrl = new URL(window.location.href);
                exportUrl.searchParams.set('export', '1');
                exportUrl.searchParams.set('export_all', '0');
                exportUrl.searchParams.set('selected_ids', selectedIds.join(','));
                window.location.href = exportUrl.toString();
                document.body.removeChild(modal);
            };

            // 取消按钮
            const cancelButton = modal.querySelector('#cancel');
            cancelButton.onclick = function() {
                document.body.removeChild(modal);
            };

            document.body.appendChild(modal);
        }
    </script>
</head>
<body>
    <button id="top-button">Top</button>

    <div class="container">
        <div class="header-row">
            <h1>Zhi-Qin John Xu's Publication</h1>
            <?php if ($is_logged_in): ?>
                <div class="user-section">
                    <span class="welcome-text">Welcome, <?= htmlspecialchars($username) ?></span>
                    <a href="admin.php" class="admin-btn"><i class="fas fa-cog"></i> Admin Panel</a>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            <?php else: ?>
                <div class="user-section">
                    <a href="login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                </div>
            <?php endif; ?>
        </div>

       
        <div class="personal-page-link">
            <a href="https://ins.sjtu.edu.cn/people/xuzhiqin/" target="_blank" class="link-btn homepage-btn">
                <i class="fas fa-home"></i> Homepage
            </a>
            <a href="https://scholar.google.com/citations?user=EjLvG5cAAAAJ&hl=zh-CN" target="_blank" class="link-btn scholar-btn">
                <i class="fas fa-graduation-cap"></i> Google Scholar
            </a>
            <a href="javascript:void(0);" onclick="exportPapers()" class="link-btn export-btn">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>

    <h2>Filter by Tag</h2>
    <ul class="tag-list">
        <li>
            <input type="checkbox" id="tag-All" name="tags[]" value="All" <?= in_array('All', $selected_tags) ? 'checked' : '' ?>>
            <label for="tag-All">All</label>
        </li>
        <?php foreach ($all_tags as $tag): ?>
            <li>
                <input type="checkbox" id="tag-<?= $tag ?>" name="tags[]" value="<?= $tag ?>" <?= in_array($tag, $selected_tags) ? 'checked' : '' ?>>
                <label for="tag-<?= $tag ?>"><?= $tag ?> (<span id="tag-count-<?= $tag ?>"><?= $tag_counts[$tag] ?? 0 ?></span>)</label>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Show/Hide Fields</h2>
    <ul class="field-list" id="field-list">
        <?php foreach ($all_fields as $field): ?>
            <?php if ($field !== 'id'): ?>
                <li data-field="<?= $field ?>">
                    <input type="checkbox" id="field-<?= $field ?>" name="show_fields[]" value="<?= $field ?>" <?= in_array($field, $default_show_fields) ? 'checked' : '' ?>>
                    <label for="field-<?= $field ?>"><?= ucfirst($field) ?></label>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <form id="search-form" method="GET" action="index.php">
        <input type="text" name="search" id="search-box" placeholder="Search papers..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="button" id="sort-desc">Sort by Year Desc</button>
        <button type="button" id="sort-asc">Sort by Year Asc</button>
    </form>

    <table>
        <thead>
            <tr id="table-header">
                <th><input type="checkbox" id="select-all"></th>
                <th>ID</th>
                <?php foreach ($default_show_fields as $field): ?>
                    <?php if ($field !== 'id'): ?>
                        <th class="<?= $field === 'year' ? 'sortable' : '' ?>" data-field="<?= $field ?>" data-order="<?= $sort_field === $field && $sort_order === 'asc' ? 'desc' : 'asc' ?>">
                            <?= ucfirst(str_replace('_', ' ', $field)) ?>
                        </th>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="paper-list">
            <?php foreach ($papers as $index => $paper): ?>
                <tr>
                    <td><input type="checkbox" class="paper-select" data-id="<?= $index + 1 ?>"></td>
                    <td><?= $index + 1 ?></td>
                    <?php foreach ($default_show_fields as $field): ?>
                        <?php if ($field !== 'id'): ?>
                            <td data-field="<?= $field ?>">
                                <?php if (isset($field_types[$field]) && $field_types[$field]): ?>
                                    <?= !empty($paper[$field]) ? '<a href="' . $paper[$field] . '" target="_blank">' . $field . '</a>' : '' ?>
                                <?php else: ?>
                                    <?= $paper[$field] ?? '' ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Reset all tag counts to 0
        <?php foreach ($all_tags as $tag): ?>
            document.getElementById('tag-count-<?= $tag ?>').textContent = 0;
        <?php endforeach; ?>

        // Update tag counts with filtered papers
        const tag_counts = <?php echo json_encode($tag_counts); ?>;
        Object.keys(tag_counts).forEach(tag => {
            const tagCountElement = document.getElementById(`tag-count-${tag}`);
            if (tagCountElement) {
                tagCountElement.textContent = tag_counts[tag];
            }
        });

        // Update sorting icons
        const sortField = '<?= $sort_field ?>';
        const sortOrder = '<?= $sort_order ?>';
        document.querySelectorAll('.sortable').forEach(element => {
            const field = element.getAttribute('data-field');
            if (field === sortField) {
                element.classList.add(sortOrder === 'asc' ? '' : 'desc');
            } else {
                element.classList.remove('desc');
            }
        });

        // 添加全选/取消���选功能
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.paper-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>

    <style>
        /* 添加一��样式 */
        .paper-select {
            cursor: pointer;
        }
        
        #select-all {
            cursor: pointer;
        }
        
        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #007bff;
            color: white;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        #cancel {
            background: #6c757d;
        }
        
        #cancel:hover {
            background: #545b62;
        }
    </style>
</body>
</html>
<?php
    return ob_get_clean();
}

function exportToExcel($papers, $fields) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="papers.xls"');

    // Remove ID column from fields
    $fields = array_filter($fields, function($field) {
        return $field !== 'ID';
    });

    // Add ID column to fields
    array_unshift($fields, 'ID');

    echo implode("\t", $fields) . "\n";

    $paper_count = count($papers);
    for ($i = 0; $i < $paper_count; $i++) {
        $row = [$i + 1]; // ID column
        foreach ($fields as $field) {
            if ($field === 'ID') continue; // Skip ID field as it's already added
            $row[] = isset($papers[$i][$field]) ? $papers[$i][$field] : '';
        }
        echo implode("\t", $row) . "\n";
    }
}
?>