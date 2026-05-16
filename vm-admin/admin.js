document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarOpen = document.getElementById('sidebarOpen');
    const sidebarClose = document.getElementById('sidebarClose');

    if (sidebarOpen && sidebar) {
        sidebarOpen.addEventListener('click', function () {
            sidebar.classList.remove('-translate-x-full');
        });
    }

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function () {
            sidebar.classList.add('-translate-x-full');
        });
    }
});