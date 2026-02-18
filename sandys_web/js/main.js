    $(document).ready(function() {

        // 🎯 INICIO DE LA CORRECCIÓN 🎯

        // Manejar menú desplegable de escritorio
        $('#userDropdown').on('click', function(e) {
            e.preventDefault(); // Previene que el enlace '#' navegue
            e.stopPropagation(); // Evita que el clic se propague y cierre el menú inmediatamente
            $('#userMenu').toggleClass('show');
        });

        // Manejar menú desplegable móvil
        $('#userDropdownMobile').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#userMenuMobile').toggleClass('show');
        });

        // Cerrar los menús si se hace clic en cualquier otro lugar de la página
        $(document).on('click', function(e) {
            // Si el clic NO fue dentro del dropdown
            if (!$('.dropdown').is(e.target) && $('.dropdown').has(e.target).length === 0) {
                $('.dropdown-menu').removeClass('show');
            }
        });

        // 🎯 FIN DE LA CORRECCIÓN 🎯

    });