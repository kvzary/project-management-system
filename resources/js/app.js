import './bootstrap';

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
