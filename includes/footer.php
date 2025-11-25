</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function() {
        // Elementos del DOM para el control de temas
        const toggle = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');

        /**
         * Aplica el tema seleccionado al documento y actualiza el icono.
         * {string} theme 'dark' o 'light'
         */
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

        // Persistencia: Recuperar preferencia del usuario desde localStorage
        const saved = localStorage.getItem('crm_theme');
        if (saved) {
            applyTheme(saved);
        } else {
            applyTheme('light'); // Tema por defecto
        }

        // Event Listener para el boton de cambio
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
    /**
     * Confirmacion simple para navegacion (Links GET).
     * No utilizar para acciones criticas de borrado (usar POST).
     */
    function confirmarSalida() {
        return confirm('¿Vas a salir sin guardar? Se perderan los cambios.');
    }
</script>

</body>

</html>