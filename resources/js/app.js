import './bootstrap';
import Tribute from 'tributejs';
import 'tributejs/dist/tribute.css';

function initMentions() {
    document.querySelectorAll('textarea.mention-input').forEach(el => {
        if (el._tributeAttached) return;
        el._tributeAttached = true;

        const tribute = new Tribute({
            trigger: '@',
            requireLeadingSpace: true,
            allowSpaces: false,
            menuContainer: document.body,
            lookup: 'name',
            fillAttr: 'name',
            values: function (text, callback) {
                fetch('/internal/users/search?q=' + encodeURIComponent(text), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                    .then(r => r.ok ? r.json() : [])
                    .then(callback)
                    .catch(() => callback([]));
            },
            menuItemTemplate: item => {
                // Escape name to prevent XSS in the dropdown
                const span = document.createElement('span');
                span.className = 'tribute-item-name';
                span.textContent = item.original.name;
                return span.outerHTML;
            },
            selectTemplate: item => {
                // Store mapping so Alpine can tokenize on submit
                el._mentionMap = el._mentionMap || {};
                el._mentionMap[item.original.name] = item.original.id;
                // Show only @Name in the textarea
                return '@' + item.original.name;
            },
            noMatchTemplate: () => '<span class="tribute-no-match">No users found</span>',
        });

        tribute.attach(el);
    });
}

document.addEventListener('DOMContentLoaded', initMentions);
document.addEventListener('livewire:initialized', initMentions);
document.addEventListener('livewire:navigated', initMentions);
document.addEventListener('livewire:update', () => setTimeout(initMentions, 150));

// Add data-label attributes to table cells for mobile card view
function labelTableCells() {
    document.querySelectorAll('.fi-ta-table').forEach(table => {
        const headers = [...table.querySelectorAll('thead th')].map(th => th.innerText.trim());
        table.querySelectorAll('tbody tr').forEach(row => {
            [...row.querySelectorAll('td')].forEach((td, i) => {
                if (headers[i]) td.setAttribute('data-label', headers[i]);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', labelTableCells);
document.addEventListener('livewire:navigated', labelTableCells);
document.addEventListener('livewire:update', () => setTimeout(labelTableCells, 100));

// MutationObserver to catch Livewire widget re-renders
const observer = new MutationObserver(() => setTimeout(labelTableCells, 150));
document.addEventListener('DOMContentLoaded', () => {
    observer.observe(document.body, { childList: true, subtree: true });
});
