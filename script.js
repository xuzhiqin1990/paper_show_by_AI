// script.js

let allPapers = [];
let selectedTags = [];
let showFields =  ['title', 'authors', 'journal', 'year', 'tags'];
let fieldTypes = {};



function filterPapers() {
    const checkedCheckboxes = Array.from(document.querySelectorAll('input[name="tags[]"]:checked'));
    selectedTags = checkedCheckboxes.map(checkbox => checkbox.value);

    const checkedFields = Array.from(document.querySelectorAll('input[name="show_fields[]"]:checked'));
    const checkedFieldValues = checkedFields.map(checkbox => checkbox.value);

    showFields = checkedFieldValues.length > 0 ? checkedFieldValues : showFields;

    const paperList = document.getElementById('paper-list');
    paperList.innerHTML = '';

    const tableHeader = document.getElementById('table-header');
    tableHeader.innerHTML = '';
    showFields.forEach(field => {
        const th = document.createElement('th');
        th.textContent = field.charAt(0).toUpperCase() + field.slice(1);
        tableHeader.appendChild(th);
    });

    const filteredPapers = allPapers.filter(paper => {
        const paperTags = (paper.tags || '').split(',').map(tag => tag.trim());
        if (selectedTags.includes('None')) {
            return paperTags.length === 0 || paperTags.includes('');
        } else {
            return selectedTags.length === 0 || selectedTags.includes('All') || selectedTags.some(tag => paperTags.includes(tag));
        }
    });

    filteredPapers.forEach(paper => {
        const row = document.createElement('tr');
        const rowData = showFields.map(field => {
            if (fieldTypes[field]) {
                return paper[field] ? `<td><a href="${paper[field]}" target="_blank">${field.charAt(0).toUpperCase() + field.slice(1)}</a></td>` : '<td></td>';
            } else {
                return `<td>${paper[field] || ''}</td>`;
            }
        });
        row.innerHTML = rowData.join('');
        paperList.appendChild(row);
    });

    saveFieldOrder();
}

/* function saveFieldOrder() {
    const fieldList = document.getElementById('field-list');
    const fieldOrder = Array.from(fieldList.children).map(li => li.querySelector('input').value);
    document.cookie = `fieldOrder=${JSON.stringify(fieldOrder)}; path=/`;

    const selectedFields = Array.from(document.querySelectorAll('input[name="show_fields[]"]:checked')).map(cb => cb.value);
    document.cookie = `selectedFields=${JSON.stringify(selectedFields)}; path=/`;
} */

function saveFieldOrder() {
    const fieldOrder = Array.from(document.querySelectorAll('#field-list li')).map(li => li.querySelector('input').value);
    document.cookie = `fieldOrder=${JSON.stringify(fieldOrder)}; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/`;

    const selectedFields = Array.from(document.querySelectorAll('#field-list input:checked')).map(input => input.value);
    document.cookie = `selectedFields=${JSON.stringify(selectedFields)}; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/`;
}

function loadFieldOrder() {
    const cookies = document.cookie.split('; ');
    const fieldOrderCookie = cookies.find(cookie => cookie.startsWith('fieldOrder='));
    if (fieldOrderCookie) {
        const fieldOrder = JSON.parse(fieldOrderCookie.substring('fieldOrder='.length));
        const fieldList = document.getElementById('field-list');

        fieldOrder.forEach(field => {
            const li = fieldList.querySelector(`input[value="${field}"]`).parentNode;
            fieldList.appendChild(li);
        });
    }

    const selectedFieldsCookie = cookies.find(cookie => cookie.startsWith('selectedFields='));
    if (selectedFieldsCookie) {
        const selectedFields = JSON.parse(selectedFieldsCookie.substring('selectedFields='.length));
        selectedFields.forEach(field => {
            document.getElementById(`field-${field}`).checked = true;
        });
        showFields = selectedFields;
    }
}


function loadPapers() {
    $.ajax({
        url: 'get_papers.php',
        method: 'GET',
        success: function(data) {
            allPapers = JSON.parse(data);
            filterPapers();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadFieldOrder();
    loadPapers();
});

$(function() {
    $('#field-list').sortable({
        stop: filterPapers,
    });
});

const checkboxes = document.querySelectorAll('input[name="tags[]"], input[name="show_fields[]"]');
checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', filterPapers);
});

$(document).ready(function() {
    $('#search-box').on('input', function() {
        var query = $(this).val().toLowerCase();
        const filteredPapers = allPapers.filter(paper => {
            return paper.title.toLowerCase().includes(query) ||
                   paper.authors.toLowerCase().includes(query) ||
                   paper.abstract.toLowerCase().includes(query);
        });
        const paperList = document.getElementById('paper-list');
        paperList.innerHTML = '';

        filteredPapers.forEach(paper => {
            const row = document.createElement('tr');
            const rowData = showFields.map(field => {
                if (fieldTypes[field]) {
                    return paper[field] ? `<td><a href="${paper[field]}" target="_blank">${field.charAt(0).toUpperCase() + field.slice(1)}</a></td>` : '<td></td>';
                } else {
                    return `<td>${paper[field] || ''}</td>`;
                }
            });
            row.innerHTML = rowData.join('');
            paperList.appendChild(row);
        });
    });
});