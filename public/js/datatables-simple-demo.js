window.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('datatablesSimple');
    if (!table || typeof simpleDatatables === 'undefined') {
        return;
    }

    new simpleDatatables.DataTable(table);
});
