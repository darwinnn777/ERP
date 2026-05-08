</main><!-- cierre de #main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var toggle  = document.getElementById('sidebarToggle');
    var body    = document.body;
    var isMobile = window.innerWidth <= 768;

    // En móvil el sidebar empieza cerrado; en escritorio, abierto
    if (isMobile) {
        body.classList.remove('sidebar-open');
    } else {
        // Restaurar preferencia guardada en localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
        }
    }

    toggle.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(
                'sidebarCollapsed',
                body.classList.contains('sidebar-collapsed')
            );
        }
    });

    // Marcar el enlace activo del sidebar según la URL actual
    var currentHref = window.location.href;
    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && href !== 'dashboard' && currentHref.indexOf(href) !== -1) {
            link.classList.add('active');
        }
    });
})();
</script>
</body>
</html>
