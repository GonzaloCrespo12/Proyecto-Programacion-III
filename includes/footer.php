<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function() {
        const toggle = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');

        function applyTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            } else {
                document.documentElement.classList.remove('dark-theme');
                if (icon) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }

        // Inicializar desde localStorage o preferencia del sistema
        const saved = localStorage.getItem('crm_theme');
        if (saved) {
            applyTheme(saved);
        } else {
            applyTheme('light');
        }

        if (toggle) {
            toggle.addEventListener('click', function() {
                const isDark = document.documentElement.classList.contains('dark-theme');
                const next = isDark ? 'light' : 'dark';
                applyTheme(next);
                localStorage.setItem('crm_theme', next);
            });
        }
    })();
</script>
<script>
    function confirmarSalida() { // Le cambié el nombre a algo más real
        return confirm('¿Vas a salir sin guardar? Se perderán los cambios.');
    }
</script>
</body>

</html>