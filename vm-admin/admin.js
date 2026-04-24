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

    // Highlight active sidebar link based on current URL
    var path = window.location.pathname;
    var segments = path.replace(/\/+$/, '').split('/');
    var currentPage = segments[segments.length - 1] || 'home';

    var allLinks = document.querySelectorAll('#sidebar nav a[href], #myNav .overlay-content a[href]');
    allLinks.forEach(function (link) {
        var href = link.getAttribute('href') || '';
        var linkPage = href.replace(/\/+$/, '').split('/').pop();
        if (linkPage && linkPage === currentPage) {
            link.classList.remove('text-gray-400');
            link.classList.add('bg-purple-600', 'text-white');
        }
    });
});